<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\Request;

class MarketOddsController extends Controller
{
    /** Return current odds for a market (for real-time / webhook consumers). */
    public function show(Market $market)
    {
        $market->load(['options', 'preVotes', 'bets', 'resolution']);
        $odds = $market->odds;
        $optionLabels = $market->options->mapWithKeys(fn ($o) => [$o->id => $o->label])->all();
        return response()->json([
            'market_id' => $market->id,
            'title' => $market->title,
            'status' => $market->status,
            'odds' => $odds,
            'options' => $optionLabels,
        ]);
    }
}
