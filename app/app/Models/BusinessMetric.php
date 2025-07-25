<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'metric_name',
        'value',
        'unit',
        'period_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'period_date' => 'date',
        ];
    }

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Scopes
    public function scopeByMetric($query, $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    public function scopeByPeriod($query, $startDate, $endDate = null)
    {
        $query->where('period_date', '>=', $startDate);

        if ($endDate) {
            $query->where('period_date', '<=', $endDate);
        }

        return $query;
    }
}
