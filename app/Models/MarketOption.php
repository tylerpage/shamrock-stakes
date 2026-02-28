<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MarketOption extends Model
{
    protected $fillable = ['market_id', 'label', 'image_path', 'sort_order'];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function preVotes(): HasMany
    {
        return $this->hasMany(PreVote::class, 'market_option_id');
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class, 'market_option_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        return Storage::disk('public')->url($this->image_path);
    }
}
