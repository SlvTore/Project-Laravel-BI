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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function metrics()
    {
        return $this->hasMany(BusinessMetric::class);
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
}
