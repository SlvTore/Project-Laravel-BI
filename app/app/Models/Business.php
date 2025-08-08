<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'invitation_code_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'founded_date' => 'date',
            'initial_revenue' => 'decimal:2',
            'goals' => 'array',
            'invitation_code_generated_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function metrics()
    {
        return $this->hasMany(BusinessMetric::class);
    }

    // Many-to-many relationship for user access
    public function users()
    {
        return $this->belongsToMany(User::class, 'business_user')
                    ->withPivot('role_id', 'joined_at')
                    ->withTimestamps();
    }

    // Get users by role
    public function usersByRole($roleName)
    {
        return $this->users()
                    ->join('roles', 'business_user.role_id', '=', 'roles.id')
                    ->where('roles.name', $roleName)
                    ->select('users.*', 'business_user.joined_at', 'roles.name as role_name', 'roles.display_name as role_display_name');
    }

    // Helper methods
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

    // RBAC Helper Methods
    public function generatePublicId(): string
    {
        do {
            $publicId = 'BIZ-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('public_id', $publicId)->exists());

        $this->update(['public_id' => $publicId]);
        return $publicId;
    }

    public function generateInvitationCode(): string
    {
        do {
            $invitationCode = strtoupper(substr(md5(uniqid(mt_rand(), true) . $this->id), 0, 12));
        } while (self::where('invitation_code', $invitationCode)->exists());

        $this->update([
            'invitation_code' => $invitationCode,
            'invitation_code_generated_at' => now(),
        ]);

        return $invitationCode;
    }

    public function refreshInvitationCode(): string
    {
        return $this->generateInvitationCode();
    }

    public function hasValidInvitationCode(): bool
    {
        return !empty($this->invitation_code);
    }

    public function addUserWithRole(User $user, string $roleName): bool
    {
        $role = \App\Models\Role::where('name', $roleName)->where('is_active', true)->first();
        
        if (!$role) {
            return false;
        }

        // Check if user is already associated with this business
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $this->users()->attach($user->id, [
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        return true;
    }

    public function promoteUser(User $user, string $newRoleName): bool
    {
        $role = \App\Models\Role::where('name', $newRoleName)->where('is_active', true)->first();
        
        if (!$role) {
            return false;
        }

        // Update user's role in this business
        $this->users()->updateExistingPivot($user->id, [
            'role_id' => $role->id,
        ]);

        // Also update user's primary role
        $user->update(['role_id' => $role->id]);

        return true;
    }

    public function removeUser(User $user): bool
    {
        return $this->users()->detach($user->id) > 0;
    }
}
