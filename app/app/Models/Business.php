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
    ];

    protected function casts(): array
    {
        return [
            'founded_date' => 'date',
            'initial_revenue' => 'decimal:2',
            'goals' => 'array',
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
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    public function metrics()
    {
        return $this->hasMany(BusinessMetric::class);
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
}
