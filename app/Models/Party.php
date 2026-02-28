<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Party extends Model
{
    use HasFactory;

    const BET_INPUT_CONTRACTS = 'contracts';
    const BET_INPUT_DOLLARS = 'dollars';

    protected $fillable = ['name', 'admin_id', 'default_balance', 'bet_input_mode'];

    protected $casts = [
        'default_balance' => 'decimal:2',
    ];

    public function isBetInputDollars(): bool
    {
        return $this->bet_input_mode === self::BET_INPUT_DOLLARS;
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'party_user')
            ->withPivot('balance', 'invited_at', 'joined_at')
            ->withTimestamps();
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PartyUser::class)->orderBy('joined_at');
    }

    public function markets(): HasMany
    {
        return $this->hasMany(Market::class)->orderBy('created_at');
    }

    public function partyInvitations(): HasMany
    {
        return $this->hasMany(PartyInvitation::class)->orderBy('invited_at', 'desc');
    }

    /** Leaderboard rows: available, portfolio, change_pct (from default_balance), sorted by portfolio desc. */
    public function getLeaderboardMembers(): \Illuminate\Support\Collection
    {
        $default = (float) $this->default_balance;
        return $this->members()->with('user')->get()->map(function ($m) use ($default) {
            $portfolio = $m->user->portfolioValueInParty($this);
            $changePct = $default > 0
                ? round((($portfolio - $default) / $default) * 100, 1)
                : null;
            return [
                'user_id' => $m->user_id,
                'user_name' => $m->user->name,
                'available' => round((float) $m->balance, 2),
                'portfolio' => round($portfolio, 2),
                'change_pct' => $changePct,
            ];
        })->sortByDesc('portfolio')->values();
    }
}
