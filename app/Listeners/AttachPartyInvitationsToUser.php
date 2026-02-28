<?php

namespace App\Listeners;

use App\Models\PartyInvitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Session;

class AttachPartyInvitationsToUser
{
    /**
     * When a user registers, attach them to any parties they were invited to by email (pending invitations).
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;
        $email = strtolower($user->email);

        $invitations = PartyInvitation::where('email', $email)
            ->with('party')
            ->get();

        if ($invitations->isEmpty()) {
            return;
        }

        $partyNames = [];
        foreach ($invitations as $invitation) {
            $party = $invitation->party;
            if ($party->users()->where('user_id', $user->id)->exists()) {
                $invitation->delete();
                continue;
            }
            $party->users()->attach($user->id, [
                'balance' => $party->default_balance,
                'invited_at' => $invitation->invited_at ?? now(),
                'joined_at' => now(),
            ]);
            $partyNames[] = $party->name;
            $invitation->delete();
        }

        if (!empty($partyNames)) {
            Session::flash('invited_parties', $partyNames);
            Session::flash('success', 'Welcome! You’ve been added to: ' . implode(', ', $partyNames) . '. You can enter from the cards below.');
        }
    }
}
