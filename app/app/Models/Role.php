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

    public function hasPermission($permission)
    {
        return in_array('all', $this->permissions ?? []) ||
               in_array($permission, $this->permissions ?? []);
    }
}
