<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'blocked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'blocked_at' => 'datetime',
    ];

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function parties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'party_user')
            ->withPivot('balance', 'invited_at', 'joined_at')
            ->withTimestamps();
    }

    public function administeredParties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Party::class, 'admin_id');
    }

    public function pushSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function balanceInParty(Party $party): float
    {
        $pivot = $this->parties()->where('party_id', $party->id)->first()?->pivot;
        return $pivot ? (float) $pivot->balance : 0;
    }

    /**
     * Portfolio in party: available balance + total staked in live markets (cost basis only).
     * This is zero-sum: no mark-to-market, so the total across all users stays consistent
     * with the money in the system. Payouts come from other players when markets resolve.
     */
    public function portfolioValueInParty(Party $party): float
    {
        $available = $this->balanceInParty($party);
        $liveMarketIds = $party->markets()->where('status', \App\Models\Market::STATUS_LIVE)->pluck('id');
        if ($liveMarketIds->isEmpty()) {
            return $available;
        }
        $staked = \App\Models\Bet::where('user_id', $this->id)
            ->whereNull('forfeited_at')
            ->whereIn('market_id', $liveMarketIds)
            ->get()
            ->sum(fn ($bet) => (float) $bet->amount * (float) $bet->price);
        return round($available + $staked, 2);
    }
}
