<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\DataIntegrityService;
use Illuminate\Support\Facades\Auth;
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
        'new_customer_count',
        'total_customer_count',
        'notes',
    ];

    protected $casts = [
        'sales_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_cogs' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Backup data before updating
        static::updating(function ($model) {
            DataIntegrityService::backupData($model, 'update');
        });

        // Backup data before deleting
        static::deleting(function ($model) {
            DataIntegrityService::backupData($model, 'delete');
        });

        // Validate data after saving
        static::saved(function ($model) {
            $model->validateDataIntegrity();
        });
    }

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

    /**
     * Validate data integrity for this record
     */
    public function validateDataIntegrity()
    {
        $errors = [];

        // Validate customer data
        $customerErrors = DataIntegrityService::validateCustomerData(
            $this->business_id,
            $this->sales_date,
            $this->new_customer_count ?? 0,
            $this->total_customer_count ?? 0
        );
        $errors = array_merge($errors, $customerErrors);

        // Validate sales data
        $salesErrors = DataIntegrityService::validateSalesData(
            $this->business_id,
            $this->sales_date,
            $this->total_revenue ?? 0,
            $this->total_cogs ?? null
        );
        $errors = array_merge($errors, $salesErrors);

        // Log any validation errors
        if (!empty($errors)) {
            ActivityLog::create([
                'business_id' => $this->business_id,
                'user_id' => Auth::id(),
                'type' => 'data_validation_warning',
                'title' => 'Data Validation Warning',
                'description' => 'Data integrity issues detected: ' . implode(', ', $errors),
                'metadata' => [
                    'errors' => $errors,
                    'record_data' => $this->toArray()
                ],
                'icon' => 'bi-exclamation-triangle',
                'color' => 'warning'
            ]);
        }

        return $errors;
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
