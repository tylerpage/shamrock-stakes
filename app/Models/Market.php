<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Market extends Model
{
    use HasFactory;

    const TYPE_YES_NO = 'yes_no';
    const TYPE_PEOPLE = 'people';

    const STATUS_SETUP = 'setup';
    const STATUS_PRE_VOTING = 'pre_voting';
    const STATUS_LIVE = 'live';
    const STATUS_PENDING_RESOLUTION = 'pending_resolution';
    const STATUS_RESOLVED = 'resolved';

    const RESOLUTION_OFFICIAL = 'official';
    const RESOLUTION_VOTING = 'voting';

    protected $fillable = [
        'party_id', 'title', 'type', 'ends_at', 'resolution_type',
        'voting_ends_at', 'status',
    ];

    protected $casts = [
        'ends_at' => 'datetime',
        'voting_ends_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(MarketOption::class, 'market_id')->orderBy('sort_order');
    }

    public function preVotes(): HasMany
    {
        return $this->hasMany(PreVote::class, 'market_id');
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class, 'market_id');
    }

    public function resolution(): HasOne
    {
        return $this->hasOne(MarketResolution::class, 'market_id');
    }

    public function resolutionProposals(): HasMany
    {
        return $this->hasMany(ResolutionProposal::class, 'market_id')->orderBy('created_at', 'desc');
    }

    /** Pending user-submitted resolution (market is paused until admin accepts/denies) */
    public function pendingResolutionProposal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ResolutionProposal::class, 'market_id')->where('status', ResolutionProposal::STATUS_PENDING);
    }

    public function isPreVoting(): bool
    {
        return $this->status === self::STATUS_PRE_VOTING;
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isPendingResolution(): bool
    {
        return $this->status === self::STATUS_PENDING_RESOLUTION;
    }

    /** Compute current odds (probability 0-1) per option from pre-votes or live bets */
    public function getOddsAttribute(): array
    {
        $optionIds = $this->options->pluck('id')->all();
        if (empty($optionIds)) {
            return [];
        }
        if ($this->isResolved() && $this->relationLoaded('resolution') && $this->resolution) {
            $winning = $this->resolution->winning_option_id;
            return $this->options->mapWithKeys(fn ($o) => [$o->id => (int) ($o->id === $winning)])->all();
        }

        $total = 0;
        $counts = array_fill_keys($optionIds, 0);

        if ($this->isPreVoting()) {
            foreach ($this->preVotes as $v) {
                $counts[$v->market_option_id] = ($counts[$v->market_option_id] ?? 0) + 1;
                $total++;
            }
        } else {
            foreach ($this->bets->whereNull('forfeited_at') as $b) {
                $counts[$b->market_option_id] = ($counts[$b->market_option_id] ?? 0) + (float) $b->amount;
                $total += (float) $b->amount;
            }
        }

        if ($total <= 0) {
            $n = count($optionIds);
            return array_combine($optionIds, array_fill(0, $n, 1 / $n));
        }

        return array_map(fn ($c) => $c / $total, $counts);
    }

    /**
     * For a resolved market, get a user's result: total staked, payout received, and win/loss.
     * Returns ['staked' => float, 'payout' => float, 'win_loss' => float] (win_loss = payout - staked).
     */
    public function userResult(int $userId): array
    {
        if (!$this->isResolved() || !$this->relationLoaded('resolution') || !$this->resolution) {
            return ['staked' => 0.0, 'payout' => 0.0, 'win_loss' => 0.0];
        }
        $myBets = $this->bets->where('user_id', $userId)->whereNull('forfeited_at');
        $staked = $myBets->sum(fn ($b) => (float) $b->amount * (float) $b->price);
        $pool = $this->bets->sum(fn ($b) => (float) $b->amount * (float) $b->price);
        $winningId = $this->resolution->winning_option_id;
        $totalWinning = $this->bets->where('market_option_id', $winningId)->whereNull('forfeited_at')->sum('amount');
        $payout = 0.0;
        if ($totalWinning > 0 && $pool > 0) {
            foreach ($myBets->where('market_option_id', $winningId)->whereNull('forfeited_at') as $bet) {
                $payout += $pool * ((float) $bet->amount / $totalWinning);
            }
        }
        $payout = round($payout, 2);
        return [
            'staked' => round($staked, 2),
            'payout' => $payout,
            'win_loss' => round($payout - $staked, 2),
        ];
    }
}
