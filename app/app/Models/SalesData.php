<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalesData extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'sales_date',
        'total_revenue',
        'total_cogs',
        'transaction_count',
        'notes',
    ];

    protected $casts = [
        'sales_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_cogs' => 'decimal:2',
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

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sales_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sales_date', Carbon::now()->month)
                    ->whereYear('sales_date', Carbon::now()->year);
    }

    public function scopeLastMonth($query)
    {
        $lastMonth = Carbon::now()->subMonth();
        return $query->whereMonth('sales_date', $lastMonth->month)
                    ->whereYear('sales_date', $lastMonth->year);
    }

    // Accessors
    public function getGrossProfitAttribute()
    {
        return $this->total_revenue - $this->total_cogs;
    }

    public function getProfitMarginAttribute()
    {
        if ($this->total_revenue == 0) return 0;
        return (($this->total_revenue - $this->total_cogs) / $this->total_revenue) * 100;
    }

    public function getFormattedRevenueAttribute()
    {
        return 'Rp ' . number_format($this->total_revenue, 0, ',', '.');
    }

    public function getFormattedCogsAttribute()
    {
        return 'Rp ' . number_format($this->total_cogs, 0, ',', '.');
    }

    public function getFormattedGrossProfitAttribute()
    {
        return 'Rp ' . number_format($this->gross_profit, 0, ',', '.');
    }

    public function getFormattedProfitMarginAttribute()
    {
        return number_format($this->profit_margin, 1) . '%';
    }
}
