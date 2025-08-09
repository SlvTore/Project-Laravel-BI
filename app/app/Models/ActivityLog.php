<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'title',
        'description',
        'metadata',
        'icon',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public static function logActivity($businessId, $userId, $type, $title, $description, $metadata = null, $icon = 'bi-activity', $color = 'primary')
    {
        return self::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'icon' => $icon,
            'color' => $color,
        ]);
    }

    public static function logUserJoined($businessId, $userId)
    {
        $user = User::find($userId);
        $roleName = $user->userRole ? $user->userRole->display_name : 'User';

        return self::logActivity(
            $businessId,
            $userId,
            'user_joined',
            'New User Joined',
            "{$user->name} joined as {$roleName}",
            ['role' => $roleName],
            'bi-person-plus',
            'success'
        );
    }

    public static function logDataInput($businessId, $userId, $metricId, $value)
    {
        $user = User::find($userId);
        $metric = BusinessMetric::find($metricId);

        return self::logActivity(
            $businessId,
            $userId,
            'data_input',
            'Data Input',
            "{$user->name} updated {$metric->metric_name}",
            ['metric_id' => $metricId, 'value' => $value],
            'bi-graph-up',
            'primary'
        );
    }

    public static function logPromotion($businessId, $userId, $oldRole, $newRole)
    {
        $user = User::find($userId);

        return self::logActivity(
            $businessId,
            $userId,
            'promotion',
            'Role Updated',
            "{$user->name} was promoted from {$oldRole} to {$newRole}",
            ['old_role' => $oldRole, 'new_role' => $newRole],
            'bi-star',
            'warning'
        );
    }
}
