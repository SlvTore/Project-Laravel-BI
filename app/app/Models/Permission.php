<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource','action','scope','code','description','is_active'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function buildCode(string $resource, string $action, ?string $scope = null): string
    {
        return $scope ? "$resource.$action.$scope" : "$resource.$action";
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
