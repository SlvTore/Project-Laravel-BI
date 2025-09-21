<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'card_id',
        'name',
        'category',
        'unit',
        'selling_price',
        'cost_price',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function productionCosts()
    {
        return $this->hasMany(ProductionCost::class);
    }

    public function activeProductionCosts()
    {
        return $this->hasMany(ProductionCost::class)->active();
    }

    public function billOfMaterials()
    {
        return $this->hasMany(BillOfMaterial::class);
    }

    public function getTotalProductionCostAttribute()
    {
        return $this->activeProductionCosts()->sum('amount');
    }

    public function getTotalBOMCostAttribute()
    {
        return $this->billOfMaterials()->sum('total_cost');
    }

    public function getMarginPercentageAttribute()
    {
        if ($this->selling_price <= 0) {
            return 0;
        }

        $totalCost = $this->cost_price + $this->getTotalProductionCostAttribute();
        return (($this->selling_price - $totalCost) / $this->selling_price) * 100;
    }
}
