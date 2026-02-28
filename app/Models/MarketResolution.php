<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketResolution extends Model
{
    protected $fillable = ['market_id', 'winning_option_id', 'resolved_by', 'resolved_at'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function winningOption(): BelongsTo
    {
        return $this->belongsTo(MarketOption::class, 'winning_option_id');
    }
}
