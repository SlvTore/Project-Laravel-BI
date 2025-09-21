<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'category',
        'description',
        'amount',
        'unit_quantity',
        'unit_type',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'unit_quantity' => 'decimal:2',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getUnitCostAttribute()
    {
        if ($this->unit_quantity <= 0) {
            return 0;
        }

        return $this->amount / $this->unit_quantity;
    }

    public function getCategoryBadgeColorAttribute()
    {
        $colors = [
            'Bahan Baku' => 'bg-blue-100 text-blue-800',
            'Tenaga Kerja' => 'bg-green-100 text-green-800',
            'Overhead' => 'bg-yellow-100 text-yellow-800',
            'Packaging' => 'bg-purple-100 text-purple-800',
            'Lainnya' => 'bg-gray-100 text-gray-800',
        ];

        return $colors[$this->category] ?? 'bg-gray-100 text-gray-800';
    }
}
