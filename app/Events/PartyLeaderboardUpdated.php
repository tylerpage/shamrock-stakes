<?php

namespace App\Events;

use App\Models\Party;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyLeaderboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Party $party;

    public function __construct(Party $party)
    {
        $this->party = $party;
    }

    public function broadcastOn(): array
    {
        return [new Channel('party.' . $this->party->id)];
    }

    public function broadcastAs(): string
    {
        return 'PartyLeaderboardUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'party_id' => $this->party->id,
            'members' => $this->party->getLeaderboardMembers()->all(),
        ];
    }
}
