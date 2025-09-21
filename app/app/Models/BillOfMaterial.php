<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'material_name',
        'quantity',
        'unit',
        'cost_per_unit',
        'total_cost',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2'
    ];

    protected $appends = [
        'formatted_cost_per_unit',
        'formatted_total_cost'
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getFormattedCostPerUnitAttribute(): string
    {
        return $this->formatCurrency($this->cost_per_unit);
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return $this->formatCurrency($this->total_cost);
    }

    // Helper method for currency formatting
    private function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 2, ',', '.');
    }

    // Boot method to auto-calculate total cost
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($billOfMaterial) {
            $billOfMaterial->total_cost = $billOfMaterial->quantity * $billOfMaterial->cost_per_unit;
        });
    }
}
