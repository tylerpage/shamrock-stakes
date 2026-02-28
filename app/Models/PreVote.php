<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreVote extends Model
{
    protected $fillable = ['market_id', 'user_id', 'market_option_id'];

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
