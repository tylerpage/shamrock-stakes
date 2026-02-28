<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    protected $fillable = ['market_id', 'user_id', 'market_option_id', 'amount', 'price'];

    protected $casts = [
        'amount' => 'decimal:2',
        'price' => 'decimal:4',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketOption(): BelongsTo
    {
        return $this->belongsTo(MarketOption::class, 'market_option_id');
    }
}
