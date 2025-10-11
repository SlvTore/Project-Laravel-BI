<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    // Relationship to normalized permission rows (avoid naming collision with legacy JSON column "permissions")
    public function permissionItems()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function hasPermission($permission)
    {
        // Pivot-based first
        if ($this->relationLoaded('permissionItems') || method_exists($this, 'permissionItems')) {
            $pivotCodes = $this->permissionItems->pluck('code')->all();
            if (in_array('*.*', $pivotCodes, true)) return true;
            if (in_array($permission, $pivotCodes, true)) return true;
        }
        // Legacy JSON fallback
        return in_array('all', $this->permissions ?? []) || in_array($permission, $this->permissions ?? []);
    }
}
