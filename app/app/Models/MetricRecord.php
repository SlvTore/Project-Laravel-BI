<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MetricRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_metric_id',
        'record_date',
        'value',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'record_date' => 'date',
        'value' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function businessMetric()
    {
        return $this->belongsTo(BusinessMetric::class);
    }

    // Scopes
    public function scopeForBusiness($query, $businessId)
    {
        return $query->whereHas('businessMetric', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }

    public function scopeForMetric($query, $metricName)
    {
        return $query->whereHas('businessMetric', function ($q) use ($metricName) {
            $q->where('metric_name', $metricName);
        });
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('record_date', Carbon::now()->month)
                    ->whereYear('record_date', Carbon::now()->year);
    }

    public function scopeLastMonth($query)
    {
        $lastMonth = Carbon::now()->subMonth();
        return $query->whereMonth('record_date', $lastMonth->month)
                    ->whereYear('record_date', $lastMonth->year);
    }

    // Accessors
    public function getFormattedValueAttribute()
    {
        $unit = $this->businessMetric->unit ?? '';
        switch ($unit) {
            case 'Rp':
                return 'Rp ' . number_format($this->value, 0, ',', '.');
            case '%':
                return number_format($this->value, 1) . '%';
            default:
                return number_format($this->value, 0, ',', '.');
        }
    }
}
