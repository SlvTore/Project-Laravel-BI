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
        'category',
        'icon',
        'description',
        'current_value',
        'previous_value',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'current_value' => 'decimal:2',
        'previous_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function metricRecords()
    {
        return $this->hasMany(MetricRecord::class);
    }

    // Alias for records() method used in feeds
    public function records()
    {
        return $this->metricRecords();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors & Calculated attributes
    public function getChangePercentageAttribute()
    {
        if ($this->previous_value == 0) {
            return $this->current_value > 0 ? 100 : 0;
        }
        return (($this->current_value - $this->previous_value) / $this->previous_value) * 100;
    }

    public function getFormattedValueAttribute()
    {
        switch ($this->unit) {
            case 'Rp':
                return 'Rp ' . number_format($this->current_value, 0, ',', '.');
            case '%':
                return number_format($this->current_value, 1) . '%';
            default:
                return number_format($this->current_value, 0, ',', '.');
        }
    }

    public function getFormattedChangeAttribute()
    {
        $change = $this->change_percentage;
        $sign = $change >= 0 ? '+' : '';
        return $sign . number_format($change, 1) . '%';
    }

    public function getChangeStatusAttribute()
    {
        $change = $this->change_percentage;
        if ($change > 0) return 'increase';
        if ($change < 0) return 'decrease';
        return 'stable';
    }
}
