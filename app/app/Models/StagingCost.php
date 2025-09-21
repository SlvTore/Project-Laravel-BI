<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StagingCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_feed_id',
        'category',
        'description',
        'amount',
        'vendor',
        'invoice_number',
        'cost_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cost_date' => 'date',
        ];
    }

    public function dataFeed()
    {
        return $this->belongsTo(DataFeed::class);
    }
}
