<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'data_feed_id',
        'customer_id',
        'transaction_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the business that owns the transaction
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function dataFeed()
    {
        return $this->belongsTo(DataFeed::class);
    }

    /**
     * Get the customer that owns the transaction
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the transaction items
     */
    public function items()
    {
        return $this->hasMany(SalesTransactionItem::class);
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }
}
