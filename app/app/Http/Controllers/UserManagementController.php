<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    /**
     * Display users for the authenticated user's business
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->canManageUsers()) {
            abort(403, 'Unauthorized access.');
        }

        return view('dashboard-users.index');
    }

    /**
     * Get users data for DataTables
     */
    public function getUsersData(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get business for the current user
        $business = $user->isBusinessOwner()
            ? $user->ownedBusinesses()->first()
            : $user->businesses()->first();

        if (!$business) {
            return response()->json(['data' => []]);
        }

        // Get all users associated with the business
        $users = collect();

        // Add business owner
        if ($business->owner) {
            $users->push($business->owner);
        }

        // Add other business users
        $businessUsers = $business->users()->with('userRole')->get();
        $users = $users->merge($businessUsers)->unique('id');

        $data = $users->map(function ($u) use ($user) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->userRole ? $u->userRole->display_name : 'Unknown',
                'role_name' => $u->userRole ? $u->userRole->name : '',
                'joined_at' => $u->created_at->format('d M Y'),
                'is_active' => $u->is_active,
                'can_promote' => $user->canPromoteUsers() && $u->isStaff() && $u->id !== $user->id,
                'can_delete' => $user->canDeleteUsers() && !$u->isBusinessOwner() && $u->id !== $user->id,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Promote a staff member to administrator
     */
    public function promote(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->canPromoteUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$user->isStaff()) {
            return response()->json(['error' => 'Only staff members can be promoted to administrator'], 400);
        }

        if ($user->promoteTo('administrator')) {
            Log::info('User promoted to administrator', [
                'promoted_user_id' => $user->id,
                'promoted_by' => $currentUser->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User successfully promoted to Administrator'
            ]);
        }

        return response()->json(['error' => 'Failed to promote user'], 500);
    }

    /**
     * Remove a user from the business
     */
    public function remove(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->canDeleteUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->isBusinessOwner()) {
            return response()->json(['error' => 'Cannot remove business owner'], 400);
        }

        // Get the business
        $business = $currentUser->isBusinessOwner()
            ? $currentUser->ownedBusinesses()->first()
            : $currentUser->businesses()->first();

        if ($business && $business->removeUser($user)) {
            Log::info('User removed from business', [
                'removed_user_id' => $user->id,
                'removed_by' => $currentUser->id,
                'business_id' => $business->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User successfully removed from business'
            ]);
        }

        return response()->json(['error' => 'Failed to remove user'], 500);
    }

    /**
     * Get business codes for the current user
     */
    public function getBusinessCodes()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isBusinessOwner()) {
            return response()->json(['error' => 'Only business owners can access business codes'], 403);
        }

        $business = $user->ownedBusinesses()->first();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        return response()->json([
            'public_id' => $business->public_id,
            'invitation_code' => $business->invitation_code
        ]);
    }

    /**
     * Regenerate invitation code
     */
    public function regenerateInvitationCode()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isBusinessOwner()) {
            return response()->json(['error' => 'Only business owners can regenerate invitation codes'], 403);
        }

        $business = $user->ownedBusinesses()->first();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $newCode = $business->refreshInvitationCode();

        Log::info('Invitation code regenerated', [
            'business_id' => $business->id,
            'regenerated_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'new_code' => $newCode,
            'message' => 'Invitation code successfully regenerated'
        ]);
    }
}
