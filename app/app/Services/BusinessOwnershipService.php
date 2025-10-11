<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessOwnershipService
{
    /**
     * Role hierarchy for ownership transfer
     * Higher index = higher priority
     */
    private const ROLE_HIERARCHY = [
        'staff' => 1,
        'administrator' => 2,
        // business-owner excluded as they're being replaced
        // business-investigator excluded - cannot become owner
    ];

    /**
     * Transfer business ownership to the next eligible user
     *
     * @param Business $business
     * @param User $currentOwner
     * @param string $reason
     * @return array ['success' => bool, 'new_owner' => User|null, 'message' => string]
     */
    public function transferOwnership(Business $business, User $currentOwner, string $reason = 'Owner account deletion'): array
    {
        try {
            DB::beginTransaction();

            // Find the next eligible successor
            $successor = $this->findEligibleSuccessor($business, $currentOwner);

            if (!$successor) {
                DB::rollBack();
                return [
                    'success' => false,
                    'new_owner' => null,
                    'message' => 'No eligible successor found. Business has no administrators or staff members to take over ownership.',
                ];
            }

            // Get the business-owner role
            $ownerRole = Role::where('name', 'business-owner')->first();

            if (!$ownerRole) {
                DB::rollBack();
                Log::error('Business owner role not found in database');
                return [
                    'success' => false,
                    'new_owner' => null,
                    'message' => 'System error: Business owner role not found.',
                ];
            }

            // Store previous owner info for logging
            $previousOwnerName = $currentOwner->name;
            $previousOwnerId = $currentOwner->id;

            // Transfer ownership
            $business->user_id = $successor->id;
            $business->transferred_from_user_id = $previousOwnerId;
            $business->ownership_transferred_at = now();
            $business->transfer_reason = $reason;
            $business->save();

            // Update successor's role to business-owner
            $successor->role_id = $ownerRole->id;
            $successor->save();

            // Ensure successor is in the business_user pivot if not already
            if (!$business->users()->where('user_id', $successor->id)->exists()) {
                $business->users()->attach($successor->id, ['joined_at' => now()]);
            }

            // Demote previous owner if they still exist and haven't been deleted
            if ($reason !== 'Owner account deletion' && $currentOwner->exists) {
                $adminRole = Role::where('name', 'administrator')->first();
                if ($adminRole) {
                    $currentOwner->role_id = $adminRole->id;
                    $currentOwner->save();
                }
            }

            // Log the ownership transfer
            $this->logOwnershipTransfer(
                $business,
                $previousOwnerId,
                $previousOwnerName,
                $successor,
                $reason
            );

            DB::commit();

            Log::info("Business ownership transferred", [
                'business_id' => $business->id,
                'business_name' => $business->business_name,
                'previous_owner' => $previousOwnerName,
                'new_owner' => $successor->name,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'new_owner' => $successor,
                'message' => "Ownership successfully transferred to {$successor->name}.",
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to transfer business ownership', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'new_owner' => null,
                'message' => 'Failed to transfer ownership: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find the most eligible successor based on role hierarchy
     * Priority: Administrator > Staff
     * Excludes: Business Investigator, Current Owner
     *
     * @param Business $business
     * @param User $excludeUser
     * @return User|null
     */
    public function findEligibleSuccessor(Business $business, User $excludeUser): ?User
    {
        // Get all users in the business with their roles
        $businessUsers = $business->users()
            ->with('userRole')
            ->where('user_id', '!=', $excludeUser->id)
            ->where('is_active', true)
            ->get();

        if ($businessUsers->isEmpty()) {
            return null;
        }

        $bestCandidate = null;
        $highestPriority = 0;

        foreach ($businessUsers as $user) {
            if (!$user->userRole) {
                continue;
            }

            $roleName = $user->userRole->name;

            // Skip business-investigator - they cannot become owners
            if ($roleName === 'business-investigator') {
                continue;
            }

            // Skip current business owners (shouldn't happen but safety check)
            if ($roleName === 'business-owner') {
                continue;
            }

            // Check if role is in hierarchy
            if (isset(self::ROLE_HIERARCHY[$roleName])) {
                $priority = self::ROLE_HIERARCHY[$roleName];

                if ($priority > $highestPriority) {
                    $highestPriority = $priority;
                    $bestCandidate = $user;
                }
            }
        }

        return $bestCandidate;
    }

    /**
     * Check if a business has eligible successors
     *
     * @param Business $business
     * @param User $currentOwner
     * @return bool
     */
    public function hasEligibleSuccessor(Business $business, User $currentOwner): bool
    {
        return $this->findEligibleSuccessor($business, $currentOwner) !== null;
    }

    /**
     * Get list of eligible successors with their role priorities
     *
     * @param Business $business
     * @param User $excludeUser
     * @return array
     */
    public function getEligibleSuccessors(Business $business, User $excludeUser): array
    {
        $businessUsers = $business->users()
            ->with('userRole')
            ->where('user_id', '!=', $excludeUser->id)
            ->where('is_active', true)
            ->get();

        $successors = [];

        foreach ($businessUsers as $user) {
            if (!$user->userRole) {
                continue;
            }

            $roleName = $user->userRole->name;

            if ($roleName === 'business-investigator' || $roleName === 'business-owner') {
                continue;
            }

            if (isset(self::ROLE_HIERARCHY[$roleName])) {
                $successors[] = [
                    'user' => $user,
                    'role' => $roleName,
                    'priority' => self::ROLE_HIERARCHY[$roleName],
                ];
            }
        }

        // Sort by priority descending (highest first)
        usort($successors, function ($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        return $successors;
    }

    /**
     * Log ownership transfer to activity log
     *
     * @param Business $business
     * @param int $previousOwnerId
     * @param string $previousOwnerName
     * @param User $newOwner
     * @param string $reason
     * @return void
     */
    private function logOwnershipTransfer(
        Business $business,
        int $previousOwnerId,
        string $previousOwnerName,
        User $newOwner,
        string $reason
    ): void {
        ActivityLog::create([
            'business_id' => $business->id,
            'user_id' => $newOwner->id,
            'type' => 'ownership_transfer',
            'title' => 'Business Ownership Transferred',
            'description' => "Business ownership automatically transferred from {$previousOwnerName} to {$newOwner->name}. Reason: {$reason}",
            'metadata' => [
                'previous_owner_id' => $previousOwnerId,
                'previous_owner_name' => $previousOwnerName,
                'new_owner_id' => $newOwner->id,
                'new_owner_name' => $newOwner->name,
                'new_owner_role' => $newOwner->userRole->display_name ?? 'Unknown',
                'transfer_reason' => $reason,
                'transfer_timestamp' => now()->toDateTimeString(),
            ],
            'icon' => 'bi-arrow-left-right',
            'color' => 'warning',
        ]);
    }

    /**
     * Handle automatic transfer on owner account deletion
     *
     * @param User $owner
     * @return array ['businesses_transferred' => int, 'businesses_deleted' => int, 'details' => array]
     */
    public function handleOwnerDeletion(User $owner): array
    {
        $ownedBusinesses = $owner->ownedBusinesses;

        $transferred = 0;
        $deleted = 0;
        $details = [];

        foreach ($ownedBusinesses as $business) {
            $result = $this->transferOwnership($business, $owner, 'Owner account deletion');

            if ($result['success']) {
                $transferred++;
                $details[] = [
                    'business_id' => $business->id,
                    'business_name' => $business->business_name,
                    'status' => 'transferred',
                    'new_owner' => $result['new_owner']->name,
                ];
            } else {
                // No eligible successor - business will be orphaned or need special handling
                // You might want to soft-delete the business or mark it as inactive
                $deleted++;
                $details[] = [
                    'business_id' => $business->id,
                    'business_name' => $business->business_name,
                    'status' => 'no_successor',
                    'message' => $result['message'],
                ];

                Log::warning("Business has no eligible successor on owner deletion", [
                    'business_id' => $business->id,
                    'business_name' => $business->business_name,
                    'owner_id' => $owner->id,
                ]);
            }
        }

        return [
            'businesses_transferred' => $transferred,
            'businesses_deleted' => $deleted,
            'details' => $details,
        ];
    }
}
