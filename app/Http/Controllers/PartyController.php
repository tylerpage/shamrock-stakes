<?php

namespace App\Http\Controllers;

use App\Events\MarketOddsUpdated;
use App\Events\PartyLeaderboardUpdated;
use App\Events\PartyMarketsUpdated;
use App\Models\Bet;
use App\Models\Market;
use App\Models\Party;
use App\Models\PartyUser;
use App\Models\PreVote;
use App\Models\ResolutionProposal;
use App\Models\ResolutionProposalPhoto;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PartyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $parties = auth()->user()->parties()->with('admin')->withCount('markets')->get();
        $adminParties = auth()->user()->administeredParties()->withCount('markets')->get();
        return view('parties.index', compact('parties', 'adminParties'));
    }

    public function marketBets(Party $party, Market $market)
    {
        $this->authorize('view', $party);
        if ($market->party_id !== $party->id) {
            abort(404);
        }
        $market->load(['options', 'bets.user', 'bets.marketOption']);
        $options = $market->options->map(fn ($o) => ['id' => $o->id, 'label' => $o->label])->values()->all();
        $bets = $market->bets->map(fn ($b) => [
            'option_id' => $b->market_option_id,
            'option_label' => $b->marketOption->label ?? '—',
            'user_name' => $b->user->name ?? '—',
            'amount' => (float) $b->amount,
            'price' => (float) $b->price,
            'created_at' => $b->created_at?->toIso8601String(),
        ])->values()->all();
        return response()->json(['options' => $options, 'bets' => $bets]);
    }

    public function show(Party $party)
    {
        $this->authorize('view', $party);
        $party->load(['markets.options', 'markets.preVotes', 'markets.bets', 'markets.resolution.winningOption', 'markets.pendingResolutionProposal']);
        $user = auth()->user();
        $availableBalance = $user->balanceInParty($party);
        $portfolioValue = $user->portfolioValueInParty($party);
        return view('parties.show', compact('party', 'availableBalance', 'portfolioValue'));
    }

    public function preVote(Request $request, Party $party, Market $market)
    {
        $this->authorize('view', $party);
        if ($market->party_id !== $party->id || !$market->isPreVoting()) {
            abort(404);
        }
        $request->validate([
            'market_option_id' => ['required', 'exists:market_options,id'],
        ]);
        $option = $market->options()->findOrFail($request->market_option_id);
        PreVote::updateOrCreate(
            ['market_id' => $market->id, 'user_id' => auth()->id()],
            ['market_option_id' => $option->id]
        );
        $market->load(['options', 'preVotes', 'bets', 'resolution']);
        broadcast(new MarketOddsUpdated($market))->toOthers();
        return back()->with('success', 'Pre-vote recorded.');
    }

    public function placeBet(Request $request, Party $party, Market $market)
    {
        $this->authorize('view', $party);
        if ($market->party_id !== $party->id || !$market->isLive() || $market->isPendingResolution()) {
            abort(404);
        }
        $request->validate([
            'market_option_id' => ['required', 'exists:market_options,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'price' => ['required', 'numeric', 'min:0', 'max:1'],
        ]);
        $option = $market->options()->findOrFail($request->market_option_id);
        $price = max(0.01, min(1.0, (float) $request->price)); // enforce 1¢–100¢ so any option can be bought
        $cost = round($request->amount * $price, 2);
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($party, $market, $option, $request, $cost, $userId, $price) {
                $partyUser = PartyUser::where('party_id', $party->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$partyUser) {
                    throw new \RuntimeException('not_in_party');
                }

                $balance = (float) $partyUser->balance;
                if ($cost > $balance) {
                    throw new \RuntimeException('insufficient:' . number_format($balance, 2));
                }

                $partyUser->decrement('balance', $cost);
                Bet::create([
                    'market_id' => $market->id,
                    'user_id' => $userId,
                    'market_option_id' => $option->id,
                    'amount' => $request->amount,
                    'price' => $price,
                ]);
            });
        } catch (\RuntimeException $e) {
            $message = 'Something went wrong.';
            if ($e->getMessage() === 'not_in_party') {
                $message = 'You are not in this party.';
            } elseif (strpos($e->getMessage(), 'insufficient:') === 0) {
                $balance = substr($e->getMessage(), 12);
                $message = 'Insufficient balance. You have $' . $balance . '.';
            }
            if ($request->wantsJson()) {
                return response()->json(['message' => $message, 'errors' => ['amount' => [$message]]], 422);
            }
            return back()->withErrors(['amount' => $message]);
        }

        $market->load(['options', 'preVotes', 'bets', 'resolution']);
        broadcast(new MarketOddsUpdated($market))->toOthers();
        broadcast(new PartyLeaderboardUpdated($party))->toOthers();

        if ($request->wantsJson()) {
            $partyUser = PartyUser::where('party_id', $party->id)->where('user_id', auth()->id())->first();
            $myBetsByOption = $market->bets->where('user_id', auth()->id())->groupBy('market_option_id');
            $odds = $market->odds;
            $positions = [];
            foreach ($market->options as $opt) {
                $contracts = $myBetsByOption->get($opt->id, collect());
                $total = $contracts->sum('amount');
                $cost = $contracts->sum(fn ($b) => $b->amount * (float) $b->price);
                $prob = $odds[$opt->id] ?? 0.5;
                $priceForBet = $prob < 0.01 ? 0.01 : ($prob > 1 ? 1 : $prob);
                $positions[$opt->id] = [
                    'total' => round($total, 2),
                    'cost' => round($cost, 2),
                    'price' => round($priceForBet, 4),
                ];
            }
            $portfolioValue = auth()->user()->portfolioValueInParty($party);
            $poolData = $market->poolAndOptionTotals();
            return response()->json([
                'success' => true,
                'message' => 'Bet placed.',
                'balance' => $partyUser ? (float) $partyUser->balance : 0,
                'portfolio' => $portfolioValue,
                'market_id' => $market->id,
                'odds' => $odds,
                'positions' => $positions,
                'pool' => $poolData['pool'],
                'option_totals' => $poolData['option_totals'],
            ]);
        }

        return back()->with('success', 'Bet placed.');
    }

    public function proposeResolutionForm(Party $party, Market $market)
    {
        $this->authorize('view', $party);
        if ($market->party_id !== $party->id || !$market->isLive() || $market->isPendingResolution()) {
            abort(404);
        }
        $market->load('options');
        return view('parties.propose-resolution', compact('party', 'market'));
    }

    public function proposeResolution(Request $request, Party $party, Market $market)
    {
        $this->authorize('view', $party);
        if ($market->party_id !== $party->id || !$market->isLive() || $market->isPendingResolution()) {
            abort(404);
        }
        $request->validate([
            'winning_option_id' => ['required', 'exists:market_options,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ]);
        $option = $market->options()->findOrFail($request->winning_option_id);

        $proposal = DB::transaction(function () use ($market, $option, $request) {
            $proposal = ResolutionProposal::create([
                'market_id' => $market->id,
                'user_id' => auth()->id(),
                'winning_option_id' => $option->id,
                'description' => $request->description,
                'status' => ResolutionProposal::STATUS_PENDING,
            ]);
            if ($request->hasFile('photos')) {
                $dir = 'resolution-proposals/' . $proposal->id;
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store($dir, 'public');
                    ResolutionProposalPhoto::create([
                        'resolution_proposal_id' => $proposal->id,
                        'path' => $path,
                    ]);
                }
            }
            $market->update(['status' => Market::STATUS_PENDING_RESOLUTION]);
            return $proposal;
        });

        $market->load(['options', 'preVotes', 'bets', 'resolution', 'pendingResolutionProposal']);
        broadcast(new MarketOddsUpdated($market))->toOthers();
        broadcast(new PartyMarketsUpdated($market->party))->toOthers();

        if ($party->admin_id) {
            app(PushNotificationService::class)->sendToUsers(
                [$party->admin_id],
                'Resolution proposed: ' . $market->title,
                auth()->user()->name . ' proposed an outcome. Accept or deny in Admin.',
                ['url' => route('admin.parties.show', $party)]
            );
        }

        return redirect()->route('parties.show', $party)
            ->with('success', 'Resolution proposed. The market is paused until an admin accepts or denies. You will lose your position if it is denied.');
    }

    public function leaderboard(Party $party)
    {
        $this->authorize('view', $party);
        $rows = $party->getLeaderboardMembers();
        $members = $rows->map(fn ($row) => [
            'user' => (object) ['name' => $row['user_name']],
            'available' => $row['available'],
            'portfolio' => $row['portfolio'],
            'change_pct' => $row['change_pct'],
        ])->all();
        return view('parties.leaderboard', compact('party', 'members'));
    }
}
