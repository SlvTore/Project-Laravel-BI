<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_name',
        'email',
        'phone',
        'first_purchase_date',
        'last_purchase_date',
        'total_purchases',
        'total_spent',
        'customer_type',
        'notes',
    ];

    protected $casts = [
        'first_purchase_date' => 'date',
        'last_purchase_date' => 'date',
        'total_spent' => 'decimal:2',
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

    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    public function scopeNewCustomers($query)
    {
        return $query->where('customer_type', 'new');
    }

    public function scopeLoyalCustomers($query)
    {
        return $query->where('customer_type', 'loyal');
    }

    public function scopeFirstPurchaseInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('first_purchase_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('first_purchase_date', Carbon::now()->month)
                    ->whereYear('first_purchase_date', Carbon::now()->year);
    }

    public function scopeLastMonth($query)
    {
        $lastMonth = Carbon::now()->subMonth();
        return $query->whereMonth('first_purchase_date', $lastMonth->month)
                    ->whereYear('first_purchase_date', $lastMonth->year);
    }

    // Accessors
    public function getFormattedTotalSpentAttribute()
    {
        return 'Rp ' . number_format($this->total_spent, 0, ',', '.');
    }

    public function getAverageOrderValueAttribute()
    {
        if ($this->total_purchases == 0) return 0;
        return $this->total_spent / $this->total_purchases;
    }

    public function getFormattedAverageOrderValueAttribute()
    {
        return 'Rp ' . number_format($this->average_order_value, 0, ',', '.');
    }

    public function getDaysSinceFirstPurchaseAttribute()
    {
        return $this->first_purchase_date->diffInDays(Carbon::now());
    }

    public function getDaysSinceLastPurchaseAttribute()
    {
        if (!$this->last_purchase_date) return null;
        return $this->last_purchase_date->diffInDays(Carbon::now());
    }

    // Mutators
    public function setCustomerTypeAttribute($value)
    {
        // Auto-determine customer type based on purchases
        if ($this->total_purchases >= 5) {
            $this->attributes['customer_type'] = 'loyal';
        } elseif ($this->total_purchases > 1) {
            $this->attributes['customer_type'] = 'returning';
        } else {
            $this->attributes['customer_type'] = 'new';
        }
    }
}
