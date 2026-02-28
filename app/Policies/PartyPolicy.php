<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;

class PartyPolicy
{
    public function update(User $user, Party $party): bool
    {
        return (int) $party->admin_id === (int) $user->id;
    }

    public function view(User $user, Party $party): bool
    {
        return $user->parties()->where('party_id', $party->id)->exists()
            || (int) $party->admin_id === (int) $user->id;
    }
}
