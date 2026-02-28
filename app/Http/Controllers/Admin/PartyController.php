<?php

namespace App\Http\Controllers\Admin;

use App\Events\MarketOddsUpdated;
use App\Events\PartyLeaderboardUpdated;
use App\Events\PartyMarketsUpdated;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\MarketOption;
use App\Models\MarketResolution;
use App\Models\Party;
use App\Models\PartyInvitation;
use App\Models\PartyUser;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $parties = auth()->user()->administeredParties()->withCount('markets')->latest()->paginate(10);
        return view('admin.parties.index', compact('parties'));
    }

    public function create()
    {
        return view('admin.parties.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_balance' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'bet_input_mode' => ['nullable', Rule::in([Party::BET_INPUT_CONTRACTS, Party::BET_INPUT_DOLLARS])],
        ]);
        $data['bet_input_mode'] = $data['bet_input_mode'] ?? Party::BET_INPUT_CONTRACTS;
        $party = auth()->user()->administeredParties()->create($data);
        return redirect()->route('admin.parties.show', $party)->with('success', 'Party created.');
    }

    public function update(Request $request, Party $party)
    {
        $this->authorize('update', $party);
        $data = $request->validate([
            'bet_input_mode' => ['required', Rule::in([Party::BET_INPUT_CONTRACTS, Party::BET_INPUT_DOLLARS])],
        ]);
        $party->update($data);
        return back()->with('success', 'Party settings updated.');
    }

    public function show(Party $party)
    {
        $this->authorize('update', $party);
        $party->load(['markets.options', 'markets.resolution.winningOption', 'members.user', 'partyInvitations']);
        return view('admin.parties.show', compact('party'));
    }

    public function invite(Request $request, Party $party)
    {
        $this->authorize('update', $party);
        $request->validate([
            'email' => ['required', 'email'],
        ]);
        $email = strtolower($request->email);

        $user = User::where('email', $email)->first();
        if ($user) {
            if ($party->users()->where('user_id', $user->id)->exists()) {
                return back()->withErrors(['email' => 'That user is already in the party.']);
            }
            $party->users()->attach($user->id, [
                'balance' => $party->default_balance,
                'invited_at' => now(),
            ]);
            return back()->with('success', 'Invited ' . $user->name . '. They can open the party from My Parties.');
        }

        if ($party->partyInvitations()->where('email', $email)->exists()) {
            return back()->with('success', 'That email is already invited. They’ll see the party when they register with this address.');
        }
        $party->partyInvitations()->create([
            'email' => $email,
            'invited_at' => now(),
        ]);
        return back()->with('success', 'Invitation sent. When they register with ' . $email . ', they’ll see this party on My Parties.');
    }

    public function updateBalance(Request $request, Party $party)
    {
        $this->authorize('update', $party);
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'balance' => ['required', 'numeric', 'min:0'],
        ]);
        $party->users()->updateExistingPivot($request->user_id, ['balance' => $request->balance]);
        broadcast(new PartyLeaderboardUpdated($party))->toOthers();
        return back()->with('success', 'Balance updated.');
    }

    public function createMarket(Request $request, Party $party)
    {
        $this->authorize('update', $party);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([Market::TYPE_YES_NO, Market::TYPE_PEOPLE])],
            'ends_at' => ['nullable', 'date'],
            'resolution_type' => ['required', Rule::in([Market::RESOLUTION_OFFICIAL, Market::RESOLUTION_VOTING])],
        ]);
        $data['party_id'] = $party->id;
        $market = Market::create($data);
        if ($data['type'] === Market::TYPE_YES_NO) {
            $market->options()->createMany([
                ['label' => 'Yes', 'sort_order' => 0],
                ['label' => 'No', 'sort_order' => 1],
            ]);
        }
        broadcast(new PartyMarketsUpdated($party))->toOthers();
        return redirect()->route('admin.parties.show', $party)->with('success', 'Market created. Add options for people-type markets.');
    }

    public function editMarket(Party $party, Market $market)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id) {
            abort(404);
        }
        $market->load(['options' => function ($q) {
            $q->withCount(['bets', 'preVotes']);
        }]);
        return view('admin.parties.edit-market', compact('party', 'market'));
    }

    public function updateMarket(Request $request, Party $party, Market $market)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id) {
            abort(404);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'ends_at' => ['nullable', 'date'],
            'voting_ends_at' => ['nullable', 'date'],
            'resolution_type' => ['required', Rule::in([Market::RESOLUTION_OFFICIAL, Market::RESOLUTION_VOTING])],
        ]);
        if ($market->isResolved()) {
            $market->update($request->only(['title', 'ends_at', 'voting_ends_at']));
        } else {
            $market->update($data);
        }
        return redirect()->route('admin.parties.show', $party)->with('success', 'Market updated.');
    }

    public function addOption(Request $request, Party $party, Market $market)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id) {
            abort(404);
        }
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
        $path = $request->hasFile('image')
            ? $request->file('image')->store('market-options', 'public')
            : null;
        $market->options()->create([
            'label' => $data['label'],
            'image_path' => $path,
            'sort_order' => $market->options()->count(),
        ]);
        return back()->with('success', 'Option added.');
    }

    public function updateOption(Request $request, Party $party, Market $market, MarketOption $market_option)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id || $market_option->market_id !== $market->id) {
            abort(404);
        }
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
        $path = $market_option->image_path;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('market-options', 'public');
        }
        $market_option->update(['label' => $data['label'], 'image_path' => $path]);
        return back()->with('success', 'Option updated.');
    }

    public function deleteOption(Party $party, Market $market, MarketOption $market_option)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id || $market_option->market_id !== $market->id) {
            abort(404);
        }
        if ($market_option->bets()->exists() || $market_option->preVotes()->exists()) {
            return back()->withErrors(['option' => 'Cannot delete an option that has bets or pre-votes.']);
        }
        if ($market->type === Market::TYPE_YES_NO && $market->options()->count() <= 2) {
            return back()->withErrors(['option' => 'Yes/No markets must keep both options.']);
        }
        $market_option->delete();
        return back()->with('success', 'Option removed.');
    }

    public function startPreVoting(Party $party)
    {
        $this->authorize('update', $party);
        $party->markets()->where('status', Market::STATUS_SETUP)->update(['status' => Market::STATUS_PRE_VOTING]);
        broadcast(new PartyMarketsUpdated($party))->toOthers();
        return back()->with('success', 'Pre-voting started. Participants can set initial odds.');
    }

    public function startLive(Party $party)
    {
        $this->authorize('update', $party);
        $party->markets()->whereIn('status', [Market::STATUS_SETUP, Market::STATUS_PRE_VOTING])
            ->update([
                'status' => Market::STATUS_LIVE,
                'ends_at' => now()->addDays(1),
                'voting_ends_at' => now()->addDays(1)->addHours(24),
            ]);
        broadcast(new PartyMarketsUpdated($party))->toOthers();
        return back()->with('success', 'Markets are now live.');
    }

    public function setOfficialOutcome(Request $request, Party $party, Market $market)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id || $market->resolution_type !== Market::RESOLUTION_OFFICIAL) {
            abort(404);
        }
        $request->validate([
            'winning_option_id' => ['required', 'exists:market_options,id'],
        ]);
        $option = MarketOption::findOrFail($request->winning_option_id);
        if ($option->market_id !== $market->id) {
            abort(422);
        }
        $this->resolveMarket($market, $option->id, Market::RESOLUTION_OFFICIAL);
        return back()->with('success', 'Market resolved (official outcome).');
    }

    public function resolveVoting(Request $request, Party $party, Market $market)
    {
        $this->authorize('update', $party);
        if ($market->party_id !== $party->id || $market->resolution_type !== Market::RESOLUTION_VOTING) {
            abort(404);
        }
        $request->validate([
            'winning_option_id' => ['required', 'exists:market_options,id'],
        ]);
        $option = MarketOption::findOrFail($request->winning_option_id);
        if ($option->market_id !== $market->id) {
            abort(422);
        }
        $this->resolveMarket($market, $option->id, Market::RESOLUTION_VOTING);
        return back()->with('success', 'Market resolved (voting outcome).');
    }

    /**
     * Resolve market and pay out winners. Uses parimutuel: the total stake (all bets)
     * is redistributed to winning bettors proportionally. No new money is created.
     * Sends a push notification to every user who had a bet on this market.
     */
    private function resolveMarket(Market $market, int $winningOptionId, string $resolvedBy): void
    {
        DB::transaction(function () use ($market, $winningOptionId, $resolvedBy) {
            MarketResolution::create([
                'market_id' => $market->id,
                'winning_option_id' => $winningOptionId,
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
            ]);
            $market->update(['status' => Market::STATUS_RESOLVED]);

            $allBets = $market->bets()->get();
            $pool = $allBets->sum(fn ($b) => (float) $b->amount * (float) $b->price);
            $winningBets = $allBets->where('market_option_id', $winningOptionId);
            $totalWinningAmount = $winningBets->sum('amount');

            if ($totalWinningAmount > 0 && $pool > 0) {
                foreach ($winningBets as $bet) {
                    $payout = round($pool * ((float) $bet->amount / $totalWinningAmount), 2);
                    PartyUser::where('party_id', $market->party_id)->where('user_id', $bet->user_id)
                        ->increment('balance', $payout);
                }
            }
        });

        // Notify users who bet on this market (after transaction so we don't hold the lock)
        $market->load('resolution.winningOption', 'party');
        $winningLabel = $market->resolution && $market->resolution->winningOption
            ? $market->resolution->winningOption->label
            : 'Resolved';
        $userIds = $market->bets()->pluck('user_id')->unique()->values()->all();
        if (!empty($userIds)) {
            $push = app(PushNotificationService::class);
            $partyUrl = route('parties.show', $market->party);
            $push->sendToUsers(
                $userIds,
                'Market resolved: ' . $market->title,
                'Winner: ' . $winningLabel,
                ['url' => $partyUrl]
            );
        }

        // Notify WebSocket listeners so they disable betting and show resolved status
        broadcast(new MarketOddsUpdated($market))->toOthers();
        // Notify party page so Active/Resolved tabs and counts can refresh
        broadcast(new PartyMarketsUpdated($market->party))->toOthers();
        broadcast(new PartyLeaderboardUpdated($market->party))->toOthers();
    }
}
