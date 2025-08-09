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
        return $this->belongsToMany(Business::class, 'business_user')
                    ->select('businesses.*')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    public function ownedBusinesses()
    {
        return $this->hasMany(Business::class);
    }

    public function primaryBusiness()
    {
        return $this->ownedBusinesses()->oldest();
    }

    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
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

    public function hasRole(string $roleName)
    {
        return $this->userRole && $this->userRole->name === $roleName;
    }

    public function promoteTo(string $roleName)
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->update(['role_id' => $role->id]);
            return true;
        }
        return false;
    }

    public function isBusinessOwner()
    {
        return $this->hasRole('business-owner');
    }

    public function isAdministrator()
    {
        return $this->hasRole('administrator');
    }

    public function isStaff()
    {
        return $this->hasRole('staff');
    }

    public function isBusinessInvestigator()
    {
        return $this->hasRole('business-investigator');
    }

    public function canManageUsers()
    {
        return $this->isBusinessOwner() || $this->isAdministrator();
    }

    public function canPromoteUsers()
    {
        return $this->isBusinessOwner() || $this->isAdministrator();
    }

    public function canDeleteUsers()
    {
        return $this->isBusinessOwner() || $this->isAdministrator();
    }

    public function canImportMetrics()
    {
        return $this->isBusinessOwner() || $this->isAdministrator();
    }

    public function canDeleteMetrics()
    {
        return $this->isBusinessOwner() || $this->isAdministrator();
    }

    public function isAdmin()
    {
        return $this->role === 'admin' || ($this->userRole && $this->userRole->name === 'admin') || $this->isBusinessOwner() || $this->isAdministrator();
    }

    public function isMentor()
    {
        return $this->role === 'mentor' || ($this->userRole && $this->userRole->name === 'mentor');
    }

    public function isActive()
    {
        return $this->is_active;
    }
}
