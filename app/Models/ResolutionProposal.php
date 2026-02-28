<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResolutionProposal extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DENIED = 'denied';

    protected $fillable = [
        'market_id', 'user_id', 'winning_option_id', 'description',
        'status', 'reviewed_by', 'reviewed_at', 'denial_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function winningOption(): BelongsTo
    {
        return $this->belongsTo(MarketOption::class, 'winning_option_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ResolutionProposalPhoto::class)->orderBy('id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
