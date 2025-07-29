<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MetricType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
        'icon',
        'unit',
        'format',
        'settings',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function businessMetrics()
    {
        return $this->hasMany(BusinessMetric::class, 'metric_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    // Accessors
    public function getFormattedValueAttribute($value)
    {
        switch ($this->format) {
            case 'currency':
                return 'Rp ' . number_format($value, 0, ',', '.');
            case 'percentage':
                return $value . '%';
            default:
                return number_format($value, 0, ',', '.');
        }
    }
}
