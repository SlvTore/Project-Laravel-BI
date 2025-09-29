<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataFeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'source',
        'original_name',
        'data_type',
        'record_count',
        'status',
        'log_message',
    ];

    protected function casts(): array
    {
        return [
            'record_count' => 'integer',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stagingSalesItems()
    {
        return $this->hasMany(StagingSalesItem::class);
    }

    public function stagingCosts()
    {
        return $this->hasMany(StagingCost::class);
    }
}
