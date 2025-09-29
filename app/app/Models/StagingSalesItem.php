<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StagingSalesItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_feed_id',
        'product_id',
        'customer_id',
        'product_name',
        'quantity',
        'unit_at_transaction',
        'selling_price_at_transaction',
        'discount_per_item',
        'tax_amount',
        'shipping_cost',
        'payment_method',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'selling_price_at_transaction' => 'decimal:2',
            'discount_per_item' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function dataFeed()
    {
        return $this->belongsTo(DataFeed::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
