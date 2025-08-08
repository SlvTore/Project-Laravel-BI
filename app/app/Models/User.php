<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'business_type',
        'phone',
        'role',
        'role_id',
        'is_active',
        'business_metrics',
        'setup_completed',
        'setup_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'business_metrics' => 'array',
            'is_active' => 'boolean',
            'setup_completed' => 'boolean',
            'setup_completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function primaryBusiness()
    {
        return $this->hasOne(Business::class)->oldest();
    }

    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Many-to-many relationship for business access
    public function businessAccess()
    {
        return $this->belongsToMany(Business::class, 'business_user')
                    ->withPivot('role_id', 'joined_at')
                    ->withTimestamps();
    }

    // Get user's roles in specific business
    public function rolesInBusiness(Business $business)
    {
        return $this->businessAccess()
                    ->where('business_id', $business->id)
                    ->with('roles')
                    ->get();
    }

    // Helper methods
    public function isSetupCompleted()
    {
        return $this->setup_completed;
    }

    public function markSetupCompleted()
    {
        $this->update([
            'setup_completed' => true,
            'setup_completed_at' => now(),
        ]);
    }

    // RBAC Helper Methods
    public function hasRole(string $roleName): bool
    {
        return $this->userRole && $this->userRole->name === $roleName;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        return $this->userRole && in_array($this->userRole->name, $roleNames);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->userRole && $this->userRole->hasPermission($permission);
    }

    public function promoteTo(string $roleName): bool
    {
        $role = \App\Models\Role::where('name', $roleName)->where('is_active', true)->first();
        
        if (!$role) {
            return false;
        }

        $this->update(['role_id' => $role->id]);
        return true;
    }

    // Specific role checks
    public function isBusinessOwner(): bool
    {
        return $this->hasRole('business-owner');
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('administrator');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    public function isBusinessInvestigator(): bool
    {
        return $this->hasRole('business-investigator');
    }

    public function canManageUsers(): bool
    {
        return $this->hasAnyRole(['business-owner', 'administrator']);
    }

    public function canPromoteUsers(): bool
    {
        return $this->hasAnyRole(['business-owner', 'administrator']);
    }

    public function canDeleteUsers(): bool
    {
        return $this->hasAnyRole(['business-owner', 'administrator']);
    }

    public function canManageMetrics(): bool
    {
        return $this->hasAnyRole(['business-owner', 'administrator', 'staff']);
    }

    public function canViewOnlyData(): bool
    {
        return $this->hasRole('business-investigator');
    }

    // Legacy methods for backward compatibility
    public function isAdmin()
    {
        return $this->hasAnyRole(['business-owner', 'administrator']);
    }

    public function isMentor()
    {
        return $this->hasRole('business-investigator');
    }

    public function isActive()
    {
        return $this->is_active;
    }
}
