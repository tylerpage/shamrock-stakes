<?php

namespace App\Events;

use App\Models\Market;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketOddsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Market $market;

    public function __construct(Market $market)
    {
        $this->market = $market;
    }

    public function broadcastOn(): array
    {
        return [new Channel('market.' . $this->market->id)];
    }

    public function broadcastAs(): string
    {
        return 'MarketOddsUpdated';
    }

    public function broadcastWith(): array
    {
        $this->market->load(['options', 'preVotes', 'bets.user', 'bets.marketOption', 'resolution.winningOption']);
        $poolData = $this->market->poolAndOptionTotals();
        $payload = [
            'market_id' => $this->market->id,
            'status' => $this->market->status,
            'odds' => $this->market->odds,
            'options' => $this->market->options->mapWithKeys(fn ($o) => [$o->id => $o->label])->all(),
            'pool' => $poolData['pool'],
            'option_totals' => $poolData['option_totals'],
            'recent_bets' => $this->market->bets->take(10)->map(fn ($b) => [
                'user_name' => $b->user->name ?? '—',
                'amount' => (float) $b->amount,
                'option_label' => $b->marketOption->label ?? '—',
                'price' => (float) $b->price,
            ])->values()->all(),
        ];
        if ($this->market->isResolved() && $this->market->resolution && $this->market->resolution->winningOption) {
            $payload['winning_option_id'] = $this->market->resolution->winning_option_id;
            $payload['winning_label'] = $this->market->resolution->winningOption->label;
        }
        return $payload;
    }
}
