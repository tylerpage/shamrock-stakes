<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Market;
use App\Models\Party;
use App\Models\PartyUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HouseMarketSeedService
{
    /** Fraction of the party's default balance the house places on each option for equal seeding (e.g. 25% on Yes, 25% on No). */
    public const SEED_PER_OPTION_FRACTION = 0.25;

    public function getOrCreateHouseUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'house@shamrock-stakes.internal'],
            [
                'name' => 'House',
                'password' => bcrypt(str()->random(32)),
            ]
        );
    }

    public function ensureHouseInParty(Party $party, int $houseUserId, float $balance): void
    {
        $exists = PartyUser::where('party_id', $party->id)->where('user_id', $houseUserId)->exists();
        if ($exists) {
            PartyUser::where('party_id', $party->id)->where('user_id', $houseUserId)->increment('balance', $balance);
        } else {
            $party->users()->attach($houseUserId, [
                'balance' => $balance,
                'invited_at' => now(),
                'joined_at' => now(),
            ]);
        }
    }

    /**
     * Seed market with equal house bets: 25% of party's default balance on each option (e.g. Yes and No each get 25%).
     * Reduces initial odds swing. No pre-vote; odds start at 50/50 for yes/no.
     */
    public function seedMarketEqual(Market $market, float $seedPerOptionDollars): void
    {
        $seedPerOptionDollars = round($seedPerOptionDollars, 2);
        if ($seedPerOptionDollars < 0.01) {
            return;
        }

        $market->load('options');
        $options = $market->options;
        if ($options->isEmpty()) {
            return;
        }

        $n = $options->count();
        $price = 1 / $n; // 0.5 for yes/no
        $party = $market->party;
        $house = $this->getOrCreateHouseUser();
        // Spend seedPerOptionDollars on each option: cost = amount * price => amount = seedPerOptionDollars / price = seedPerOptionDollars * n
        $totalCost = round($seedPerOptionDollars * $n, 2); // e.g. 25% * 2 options = 50% of default

        DB::transaction(function () use ($market, $party, $house, $options, $seedPerOptionDollars, $price, $n, $totalCost) {
            $partyUser = PartyUser::where('party_id', $party->id)
                ->where('user_id', $house->id)
                ->lockForUpdate()
                ->first();

            if (!$partyUser || (float) $partyUser->balance < $totalCost) {
                return;
            }

            $betsToCreate = [];
            foreach ($options as $option) {
                $cost = round($seedPerOptionDollars, 2);
                $amount = round($seedPerOptionDollars / $price, 2); // contracts = cost / price
                if ($cost < 0.01) {
                    continue;
                }
                $betsToCreate[] = [
                    'market_id' => $market->id,
                    'user_id' => $house->id,
                    'market_option_id' => $option->id,
                    'amount' => $amount,
                    'price' => round($price, 4),
                ];
            }

            $partyUser->decrement('balance', $totalCost);
            foreach ($betsToCreate as $attrs) {
                Bet::create($attrs);
            }
        });
    }
}
