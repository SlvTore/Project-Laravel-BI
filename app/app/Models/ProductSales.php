<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductSales extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'product_name',
        'product_sku',
        'sales_date',
        'quantity_sold',
        'unit_price',
        'revenue_generated',
        'cost_per_unit',
        'category',
        'notes',
    ];

    protected $casts = [
        'sales_date' => 'date',
        'unit_price' => 'decimal:2',
        'revenue_generated' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Scopes
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByProduct($query, $productName)
    {
        return $query->where('product_name', $productName);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sales_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sales_date', Carbon::now()->month)
                    ->whereYear('sales_date', Carbon::now()->year);
    }

    public function scopeTopSelling($query, $limit = 10)
    {
        return $query->selectRaw('product_name, SUM(quantity_sold) as total_quantity, SUM(revenue_generated) as total_revenue')
                    ->groupBy('product_name')
                    ->orderByDesc('total_revenue')
                    ->limit($limit);
    }

    // Accessors
    public function getGrossProfitAttribute()
    {
        return $this->revenue_generated - ($this->cost_per_unit * $this->quantity_sold);
    }

    public function getProfitMarginAttribute()
    {
        if ($this->revenue_generated == 0) return 0;
        return ($this->gross_profit / $this->revenue_generated) * 100;
    }

    public function getFormattedRevenueAttribute()
    {
        return 'Rp ' . number_format($this->revenue_generated, 0, ',', '.');
    }

    public function getFormattedProfitAttribute()
    {
        return 'Rp ' . number_format($this->gross_profit, 0, ',', '.');
    }

    public function getFormattedProfitMarginAttribute()
    {
        return number_format($this->profit_margin, 1) . '%';
    }
}
