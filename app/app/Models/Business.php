<?php

namespace App\Models;

use App\Models\BusinessInvitation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'industry',
        'founded_date',
        'website',
        'initial_revenue',
        'initial_customers',
        'goals',
        'public_id',
        'invitation_code',
        'logo_path',
        'dashboard_display_name',
        'transferred_from_user_id',
        'ownership_transferred_at',
        'transfer_reason',
    ];

    protected function casts(): array
    {
        return [
            'founded_date' => 'date',
            'initial_revenue' => 'decimal:2',
            'goals' => 'array',
            'ownership_transferred_at' => 'datetime',
        ];
    }

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'business_user')
                    ->select('users.*')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class);
    }

    public function metrics()
    {
        return $this->hasMany(BusinessMetric::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Helper methods
    public function generatePublicId()
    {
        do {
            $publicId = 'BIZ-' . strtoupper(uniqid());
        } while (self::where('public_id', $publicId)->exists());

        $this->update(['public_id' => $publicId]);
        return $publicId;
    }

    public function generateInvitationCode()
    {
        $invitationCode = strtoupper(substr(md5(uniqid() . $this->id), 0, 8));
        $this->update(['invitation_code' => $invitationCode]);
        return $invitationCode;
    }

    public function refreshInvitationCode()
    {
        return $this->generateInvitationCode();
    }

    public function addUser(User $user)
    {
        if (!$this->users()->where('user_id', $user->id)->exists()) {
            $this->users()->attach($user->id, ['joined_at' => now()]);
            return true;
        }
        return false;
    }

    public function removeUser(User $user)
    {
        return $this->users()->detach($user->id);
    }

    public function issueInvitation(array $attributes = []): BusinessInvitation
    {
        $payload = array_merge($attributes, [
            'business_id' => $this->id,
        ]);

        return $this->invitations()->create($payload);
    }

    public function getLatestMetrics()
    {
        return $this->metrics()
                    ->selectRaw('metric_name, value, unit, MAX(period_date) as latest_date')
                    ->groupBy('metric_name', 'value', 'unit')
                    ->get();
    }

    public function getMetricHistory($metricName, $months = 12)
    {
        return $this->metrics()
                    ->where('metric_name', $metricName)
                    ->where('period_date', '>=', now()->subMonths($months))
                    ->orderBy('period_date')
                    ->get();
    }

    /**
     * Get eligible successor for business ownership transfer
     * Follows hierarchy: Administrator > Staff
     * Excludes: Business Investigator
     *
     * @param User|null $excludeUser
     * @return User|null
     */
    public function getEligibleSuccessor(?User $excludeUser = null): ?User
    {
        $ownershipService = app(\App\Services\BusinessOwnershipService::class);

        $currentOwner = $excludeUser ?? $this->owner;

        if (!$currentOwner) {
            return null;
        }

        return $ownershipService->findEligibleSuccessor($this, $currentOwner);
    }

    /**
     * Check if business has eligible successors for ownership transfer
     *
     * @param User|null $excludeUser
     * @return bool
     */
    public function hasEligibleSuccessor(?User $excludeUser = null): bool
    {
        return $this->getEligibleSuccessor($excludeUser) !== null;
    }

    /**
     * Get all eligible successors with their priorities
     *
     * @param User|null $excludeUser
     * @return array
     */
    public function getEligibleSuccessors(?User $excludeUser = null): array
    {
        $ownershipService = app(\App\Services\BusinessOwnershipService::class);

        $currentOwner = $excludeUser ?? $this->owner;

        if (!$currentOwner) {
            return [];
        }

        return $ownershipService->getEligibleSuccessors($this, $currentOwner);
    }

    /**
     * Transfer business ownership to another user
     *
     * @param User $newOwner
     * @param string $reason
     * @return array
     */
    public function transferOwnershipTo(User $newOwner, string $reason = 'Manual transfer'): array
    {
        $ownershipService = app(\App\Services\BusinessOwnershipService::class);

        if (!$this->owner) {
            return [
                'success' => false,
                'message' => 'Business has no current owner',
            ];
        }

        return $ownershipService->transferOwnership($this, $this->owner, $reason);
    }
}
