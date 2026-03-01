@extends('layouts.app')

@section('title', $party->name)

@section('content')
<div class="container" data-party-id="{{ $party->id }}">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">{{ $party->name }}</h1>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-success me-0 fs-4 px-3 py-2" id="party-available">Available: ${{ number_format($availableBalance, 2) }}</span>
            <span class="badge bg-dark me-0 fs-5 px-2 py-1" id="party-portfolio">Portfolio: ${{ number_format($portfolioValue, 2) }}</span>
            <a href="{{ route('parties.leaderboard', $party) }}" class="btn btn-shamrock btn-sm btn-touch">Leaderboard</a>
            <a href="{{ route('parties.markets.create', $party) }}" class="btn btn-outline-primary btn-sm btn-touch">Create market</a>
            <a href="{{ route('parties.index') }}" class="btn btn-outline-secondary btn-sm btn-touch">Back</a>
        </div>
    </div>

    @php
        $activeMarkets = $party->markets->whereIn('status', ['setup', 'pre_voting', 'live', 'pending_resolution']);
        $resolvedMarkets = $party->markets->where('status', 'resolved');
    @endphp

    <ul class="nav nav-tabs mb-3" id="partyMarketsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-markets" type="button" role="tab">Active ({{ $activeMarkets->count() }})</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resolved-tab" data-bs-toggle="tab" data-bs-target="#resolved-markets" type="button" role="tab">Resolved ({{ $resolvedMarkets->count() }})</button>
        </li>
    </ul>

    <div class="tab-content" id="partyMarketsTabContent">
        <div class="tab-pane fade show active" id="active-markets" role="tabpanel">
            @foreach($activeMarkets as $market)
                @php $odds = $market->odds; @endphp
                <div class="card shamrock-card mb-4" data-market-id="{{ $market->id }}" data-market-title="{{ e($market->title) }}">
                    <div class="card-header shamrock-header d-flex justify-content-between align-items-center">
                        <span>{{ $market->title }}</span>
                        <span class="badge badge-shamrock" data-market-status-badge>{{ $market->isPendingResolution() ? 'Under review' : $market->status }}</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3" data-market-type-line>
                            Type: {{ $market->type }} · Resolution: {{ $market->resolution_type }}
                            @if($market->ends_at) · Ends {{ $market->ends_at->diffForHumans() }}@endif
                        </p>

                        @if($market->isPreVoting())
                            <p class="fw-bold">Set initial odds (no tokens): pick the outcome you think is most likely.</p>
                            <form method="POST" action="{{ route('parties.pre-vote', [$party, $market]) }}" class="mb-3">
                                @csrf
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($market->options as $option)
                                        <label class="btn btn-outline-secondary d-inline-flex align-items-center btn-touch py-3 px-3">
                                            <input type="radio" name="market_option_id" value="{{ $option->id }}" class="me-2"
                                                {{ optional($market->preVotes->where('user_id', auth()->id())->first())->market_option_id === $option->id ? 'checked' : '' }}>
                                            @if($option->image_url)<img src="{{ $option->image_url }}" alt="" class="rounded me-1" style="height:24px;width:24px;object-fit:cover">@endif
                                            {{ $option->label }}
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-shamrock btn-touch mt-3 py-3 px-4">Submit pre-vote</button>
                            </form>
                        @endif

                        @if($market->isPendingResolution())
                            @php $pendingProposal = $market->pendingResolutionProposal; @endphp
                            <div class="alert alert-warning mb-3" role="alert">
                                <strong>Resolution under review</strong>
                                @if($pendingProposal)
                                    {{ $pendingProposal->user->name }} proposed <strong>{{ $pendingProposal->winningOption->label ?? '—' }}</strong> as the outcome. The market is paused until an admin accepts or denies. No new bets.
                                @else
                                    The market is paused pending resolution review. No new bets.
                                @endif
                            </div>
                        @endif

                        @if($market->isLive())
                            @php
                                $myBetsByOption = $market->bets->where('user_id', auth()->id())->whereNull('forfeited_at')->groupBy('market_option_id');
                                $poolData = $market->poolAndOptionTotals();
                                $totalPool = $poolData['pool'];
                                $optionTotals = $poolData['option_totals'];
                            @endphp
                            <p class="fw-bold" data-market-live-intro>
                                @if($party->isBetInputDollars())
                                    Enter how much to spend ($) on any option. You can bet on both sides or add more to the same option.
                                @else
                                    Buy contracts on any option (cost = amount × price). You can bet on both sides or add more to the same option.
                                @endif
                            </p>
                            <p class="alert alert-secondary py-2 mb-2 d-none" data-market-closed-message role="alert">This market is closed. See the Resolved tab for results.</p>
                            <div class="row mb-3" data-market-live-options>
                                @foreach($market->options as $option)
                                    @php
                                        $prob = $odds[$option->id] ?? 0.5;
                                        $priceForBet = $prob < 0.01 ? 0.01 : ($prob > 1 ? 1 : $prob);
                                        $myContracts = $myBetsByOption->get($option->id, collect());
                                        $myTotal = $myContracts->sum('amount');
                                        $myCost = $myContracts->sum(fn ($b) => $b->amount * (float) $b->price);
                                        $totalOnOption = $optionTotals[$option->id] ?? 0;
                                        $toWin = ($totalPool > 0 && $myTotal > 0) ? round($totalPool * ($myTotal / max($totalOnOption, $myTotal)), 2) : null;
                                    @endphp
                                    <div class="col-md-6 mb-2" data-option-id="{{ $option->id }}" data-pool="{{ $totalPool }}" data-total-on-option="{{ $totalOnOption }}" data-my-contracts="{{ $myTotal }}">
                                        <div class="border rounded p-2" style="border-color: #0d3328 !important;">
                                            <div class="d-flex align-items-center mb-1">
                                                @if($option->image_url)<img src="{{ $option->image_url }}" alt="" class="rounded me-2" style="height:36px;width:36px;object-fit:cover">@endif
                                                <strong>{{ $option->label }}</strong>
                                                <span class="ms-auto" data-option-odds>{{ number_format($priceForBet * 100, 0) }}¢</span>
                                            </div>
                                            <div class="odds-bar"><div class="odds-fill" data-option-odds-bar style="width:{{ min(100, $priceForBet * 100) }}%"></div></div>
                                            <p class="small text-muted mb-1 mt-1" data-option-position style="{{ $myTotal > 0 ? '' : 'display:none;' }}">Your position: <span data-option-position-text>{{ $myTotal > 0 ? number_format($myTotal, 1) . ' contracts ($' . number_format($myCost, 2) . ' staked)' : '' }}</span>@if($toWin !== null)<span data-option-to-win> — to win ~${{ number_format($toWin, 2) }}</span>@else<span data-option-to-win style="display:none;"></span>@endif</p>
                                            <form method="POST" action="{{ route('parties.bet', [$party, $market]) }}" class="mt-2 bet-form" data-bet-form data-bet-input-mode="{{ $party->isBetInputDollars() ? 'dollars' : 'contracts' }}">
                                                @csrf
                                                <input type="hidden" name="market_option_id" value="{{ $option->id }}">
                                                <input type="hidden" name="price" value="{{ number_format($priceForBet, 4, '.', '') }}" data-option-price-input>
                                                <div class="input-group bet-input-group">
                                                    @if($party->isBetInputDollars())
                                                        <input type="number" step="0.01" min="0.01" class="form-control" placeholder="{{ $myTotal > 0 ? 'Add $…' : 'Amount to spend ($)' }}" required inputmode="decimal" data-bet-amount data-bet-dollar-input>
                                                        <input type="hidden" name="amount" data-bet-amount-hidden>
                                                    @else
                                                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="{{ $myTotal > 0 ? 'Add amount…' : 'Contracts' }}" required inputmode="decimal" data-bet-amount>
                                                    @endif
                                                    <button type="submit" class="btn btn-shamrock btn-touch" data-bet-submit>{{ $myTotal > 0 ? 'Buy more' : 'Buy' }}</button>
                                                </div>
                                                <p class="small mb-0 mt-1" data-bet-cost>{{ $party->isBetInputDollars() ? 'You\'re spending: $0.00' : 'Cost: $0.00' }}</p>
                                                <p class="small text-muted mb-0 mt-0" data-bet-to-win style="display:none;">To win ~$0.00</p>
                                                <div class="text-danger small" data-bet-error style="display:none;"></div>
                                                @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('parties.propose-resolution.form', [$party, $market]) }}" class="btn btn-outline-secondary btn-touch">Propose resolution</a>
                                <span class="small text-muted ms-2">Submit the outcome you believe is true (with description and optional photos). Market will pause until an admin reviews. If denied, you forfeit your position.</span>
                            </div>
                        @endif

                        <div data-market-recent-bets class="mt-3">
                            @if($market->bets->isNotEmpty())
                                <details>
                                    <summary class="small text-muted">Recent bets</summary>
                                    <ul class="list-unstyled small mt-1" data-recent-bets-list>
                                        @foreach($market->bets->take(10) as $bet)
                                            <li>{{ $bet->user->name }}: ${{ number_format($bet->amount * $bet->price, 2) }} on {{ $bet->marketOption->label }} @ {{ number_format($bet->price * 100, 0) }}¢</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" data-view-all-bets data-market-id="{{ $market->id }}" data-market-title="{{ e($market->title) }}">View all bets</button>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($activeMarkets->isEmpty())
                <div class="card shamrock-card">
                    <div class="card-body text-center py-5 text-muted">No active markets. Resolved markets appear in the Resolved tab.</div>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="resolved-markets" role="tabpanel">
            @foreach($resolvedMarkets as $market)
                @php
                    $result = $market->userResult(auth()->id());
                @endphp
                <div class="card shamrock-card mb-4">
                    <div class="card-header shamrock-header d-flex justify-content-between align-items-center">
                        <span>{{ $market->title }}</span>
                        <span class="badge bg-secondary">Resolved</span>
                    </div>
                    <div class="card-body">
                        <p class="text-success fw-bold mb-2">Winner: {{ $market->resolution && $market->resolution->winningOption ? $market->resolution->winningOption->label : '—' }}</p>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($market->options as $option)
                                <span class="badge {{ $market->resolution && $market->resolution->winning_option_id == $option->id ? 'bg-success' : 'bg-secondary' }}">
                                    @if($option->image_url)<img src="{{ $option->image_url }}" alt="" class="rounded" style="height:16px;width:16px;object-fit:cover"> @endif
                                    {{ $option->label }}
                                </span>
                            @endforeach
                        </div>
                        <div class="border rounded p-3" style="border-color: #0d3328 !important; background: rgba(13,51,40,0.06);">
                            <strong>Your result</strong>
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                <span class="text-muted">Staked: ${{ number_format($result['staked'], 2) }}</span>
                                <span class="text-muted">Payout: ${{ number_format($result['payout'], 2) }}</span>
                                @if($result['win_loss'] >= 0)
                                    <span class="text-success fw-bold">Win: +${{ number_format($result['win_loss'], 2) }}</span>
                                @else
                                    <span class="text-danger fw-bold">Loss: ${{ number_format($result['win_loss'], 2) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($resolvedMarkets->isEmpty())
                <div class="card shamrock-card">
                    <div class="card-body text-center py-5 text-muted">No resolved markets yet.</div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="allBetsModal" tabindex="-1" aria-labelledby="allBetsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header shamrock-header">
                    <h5 class="modal-title" id="allBetsModalTitle">All bets</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small text-muted">Filter by option</label>
                    <select class="form-select form-select-sm mb-3" id="allBetsFilterOption">
                        <option value="">All options</option>
                    </select>
                    <div id="allBetsList" class="small"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js" defer></script>
<script>
(function () {
  console.log('[Shamrock] Party page script loaded');
  if (window.location.hash === '#resolved-markets') {
    var resolvedTab = document.getElementById('resolved-tab');
    if (resolvedTab) resolvedTab.click();
  }
  var availableEl = document.getElementById('party-available');
  var portfolioEl = document.getElementById('party-portfolio');
  if (!availableEl) return;

  function minimalConfetti() {
    if (typeof confetti !== 'function') return;
    confetti({
      particleCount: 40,
      spread: 55,
      origin: { y: 0.65 },
      colors: ['#0d3328', '#c9a227', '#1a4d3e', '#e8c547'],
      ticks: 100
    });
  }

  function updateBetCost(form) {
    var amountEl = form.querySelector('[data-bet-amount]');
    var priceEl = form.querySelector('[data-option-price-input]');
    var costEl = form.querySelector('[data-bet-cost]');
    var toWinEl = form.querySelector('[data-bet-to-win]');
    if (!costEl || !amountEl || !priceEl) return;
    var isDollars = form.getAttribute('data-bet-input-mode') === 'dollars';
    var optionBlock = form.closest('[data-option-id]');
    var pool = optionBlock ? parseFloat(optionBlock.getAttribute('data-pool')) || 0 : 0;
    var totalOnOption = optionBlock ? parseFloat(optionBlock.getAttribute('data-total-on-option')) || 0 : 0;
    var cost = 0, contracts = 0;
    if (isDollars) {
      var dollars = parseFloat(amountEl.value) || 0;
      cost = dollars;
      var price = parseFloat(priceEl.value) || 0.01;
      contracts = price > 0 ? dollars / price : 0;
      costEl.textContent = 'You\'re spending: $' + (dollars > 0 ? dollars.toFixed(2) : '0.00');
    } else {
      var amount = parseFloat(amountEl.value) || 0;
      var price = parseFloat(priceEl.value) || 0;
      cost = amount * price;
      contracts = amount;
      costEl.textContent = 'Cost: $' + (cost > 0 ? cost.toFixed(2) : '0.00');
    }
    if (toWinEl) {
      if (cost > 0 && contracts > 0) {
        var newPool = pool + cost;
        var newTotalOnOption = totalOnOption + contracts;
        var toWin = newPool * (contracts / newTotalOnOption);
        toWinEl.textContent = 'To win ~$' + toWin.toFixed(2);
        toWinEl.style.display = '';
      } else {
        toWinEl.style.display = 'none';
      }
    }
  }
  document.querySelectorAll('[data-bet-form]').forEach(function (form) {
    var amountInput = form.querySelector('[data-bet-amount]');
    if (amountInput) {
      amountInput.addEventListener('input', function () { updateBetCost(form); });
      amountInput.addEventListener('change', function () { updateBetCost(form); });
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var isDollars = form.getAttribute('data-bet-input-mode') === 'dollars';
      if (isDollars) {
        var dollarInput = form.querySelector('[data-bet-dollar-input]');
        var priceInput = form.querySelector('[data-option-price-input]');
        var amountHidden = form.querySelector('[data-bet-amount-hidden]');
        if (dollarInput && priceInput && amountHidden) {
          var dollars = parseFloat(dollarInput.value) || 0;
          var price = parseFloat(priceInput.value) || 0.01;
          if (dollars <= 0) return;
          var contracts = dollars / price;
          if (contracts < 0.01) return;
          amountHidden.value = Math.max(0.01, contracts).toFixed(4);
        }
      }
      var submitBtn = form.querySelector('[data-bet-submit]');
      var errorEl = form.querySelector('[data-bet-error]');
      var marketCard = form.closest('[data-market-id]');
      var marketId = marketCard ? marketCard.getAttribute('data-market-id') : null;

      if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; }
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = '…'; }

      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (res) {
          return res.json().then(function (data) {
            if (!res.ok) throw { status: res.status, data: data };
            return data;
          });
        })
        .then(function (data) {
          if (data.balance !== undefined) {
            availableEl.textContent = 'Available: $' + parseFloat(data.balance).toFixed(2);
          }
          if (data.portfolio !== undefined && portfolioEl) {
            portfolioEl.textContent = 'Portfolio: $' + parseFloat(data.portfolio).toFixed(2);
          }
          if (data.pool !== undefined && data.option_totals) {
            marketCard.querySelectorAll('[data-option-id]').forEach(function (optionBlock) {
              var oid = optionBlock.getAttribute('data-option-id');
              if (oid && data.option_totals[oid] !== undefined) {
                optionBlock.setAttribute('data-pool', data.pool);
                optionBlock.setAttribute('data-total-on-option', data.option_totals[oid]);
              }
            });
          }
          if (marketId && data.market_id === parseInt(marketId, 10) && data.positions) {
            var pool = data.pool != null ? parseFloat(data.pool) : 0;
            var optionTotals = data.option_totals || {};
            Object.keys(data.positions).forEach(function (optionId) {
              var pos = data.positions[optionId];
              var optionBlock = marketCard.querySelector('[data-option-id="' + optionId + '"]');
              if (!optionBlock) return;
              var oddsEl = optionBlock.querySelector('[data-option-odds]');
              var barEl = optionBlock.querySelector('[data-option-odds-bar]');
              var posEl = optionBlock.querySelector('[data-option-position]');
              var posTextEl = optionBlock.querySelector('[data-option-position-text]');
              var toWinSpan = optionBlock.querySelector('[data-option-to-win]');
              var priceInput = optionBlock.querySelector('[data-option-price-input]');
              if (oddsEl) oddsEl.textContent = Math.round(pos.price * 100) + '¢';
              if (barEl) barEl.style.width = Math.min(100, pos.price * 100) + '%';
              if (priceInput) priceInput.value = pos.price.toFixed(4);
              if (posEl && posTextEl) {
                if (pos.total > 0) {
                  posTextEl.textContent = parseFloat(pos.total).toFixed(1) + ' contracts ($' + parseFloat(pos.cost).toFixed(2) + ' staked)';
                  posEl.style.display = '';
                  optionBlock.setAttribute('data-my-contracts', pos.total);
                  var totalOnOpt = parseFloat(optionTotals[optionId]) || 0;
                  var toWin = (pool > 0 && pos.total > 0) ? (pool * (pos.total / Math.max(totalOnOpt, pos.total))).toFixed(2) : null;
                  if (toWinSpan) {
                    if (toWin !== null) { toWinSpan.textContent = ' — to win ~$' + toWin; toWinSpan.style.display = ''; }
                    else { toWinSpan.style.display = 'none'; }
                  }
                } else {
                  posEl.style.display = 'none';
                  optionBlock.setAttribute('data-my-contracts', '0');
                  if (toWinSpan) toWinSpan.style.display = 'none';
                }
              }
              optionBlock.querySelectorAll('[data-bet-form]').forEach(function (f) { updateBetCost(f); });
            });
          }
          var amountInput = form.querySelector('[data-bet-amount]');
          if (amountInput) amountInput.value = '';
          var amountHidden = form.querySelector('[data-bet-amount-hidden]');
          if (amountHidden) amountHidden.value = '';
          updateBetCost(form);
          var optionId = form.querySelector('input[name="market_option_id"]').value;
          var hasPosition = data.positions && data.positions[optionId] && data.positions[optionId].total > 0;
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = hasPosition ? 'Buy more' : 'Buy'; }
          minimalConfetti();
        })
        .catch(function (err) {
          var msg = err.data && err.data.message ? err.data.message : (err.data && err.data.errors && err.data.errors.amount ? err.data.errors.amount[0] : 'Something went wrong.');
          if (errorEl) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
          var optBlock = form.closest('[data-option-id]');
          var hasPosition = optBlock && optBlock.querySelector('[data-option-position-text]') && optBlock.querySelector('[data-option-position-text]').textContent;
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = hasPosition ? 'Buy more' : 'Buy'; }
        });
    });
  });

  var container = document.querySelector('[data-party-id]');
  if (container) {
    container.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-view-all-bets]');
      if (!btn) return;
      e.preventDefault();
      var partyId = container.getAttribute('data-party-id');
      var marketId = btn.getAttribute('data-market-id');
      var marketTitle = btn.getAttribute('data-market-title') || 'Market';
      if (!partyId || !marketId) return;
      var modalEl = document.getElementById('allBetsModal');
      var titleEl = document.getElementById('allBetsModalTitle');
      var filterSelect = document.getElementById('allBetsFilterOption');
      var listEl = document.getElementById('allBetsList');
      if (!modalEl || !titleEl || !filterSelect || !listEl) return;
      titleEl.textContent = 'All bets — ' + marketTitle;
      listEl.innerHTML = '<p class="text-muted">Loading…</p>';
      filterSelect.innerHTML = '<option value="">All options</option>';
      var allBets = [];
      fetch('/parties/' + partyId + '/markets/' + marketId + '/bets', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (r) {
          if (!r.ok) throw new Error('Fetch failed');
          return r.json();
        })
        .then(function (data) {
          allBets = data.bets || [];
          var options = data.options || [];
          options.forEach(function (opt) {
            var o = document.createElement('option');
            o.value = opt.id;
            o.textContent = opt.label;
            filterSelect.appendChild(o);
          });
          function renderBets(filterOptionId) {
            var list = filterOptionId ? allBets.filter(function (b) { return String(b.option_id) === String(filterOptionId); }) : allBets;
            if (list.length === 0) {
              listEl.innerHTML = '<p class="text-muted">No bets</p>';
              return;
            }
            var html = '<ul class="list-unstyled mb-0">';
            list.forEach(function (b) {
              var cost = (typeof b.amount === 'number' ? b.amount : parseFloat(b.amount) || 0) * (typeof b.price === 'number' ? b.price : parseFloat(b.price) || 0);
              var price = typeof b.price === 'number' ? Math.round(b.price * 100) : (b.price ? Math.round(parseFloat(b.price) * 100) : 0);
              var time = b.created_at ? (new Date(b.created_at)).toLocaleString() : '';
              html += '<li class="border-bottom py-2">' + (b.user_name || '—') + ': $' + cost.toFixed(2) + ' on <strong>' + (b.option_label || '—') + '</strong> @ ' + price + '¢' + (time ? ' <span class="text-muted">' + time + '</span>' : '') + '</li>';
            });
            html += '</ul>';
            listEl.innerHTML = html;
          }
          filterSelect.onchange = function () { renderBets(filterSelect.value || ''); };
          renderBets('');
          if (window.bootstrap && window.bootstrap.Modal) {
            var modal = new window.bootstrap.Modal(modalEl);
            modal.show();
          } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.setAttribute('aria-modal', 'true');
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.setAttribute('data-modal-backdrop', '1');
            document.body.appendChild(backdrop);
            modalEl.querySelector('[data-bs-dismiss="modal"]').addEventListener('click', function closeModal() {
              modalEl.classList.remove('show');
              modalEl.style.display = '';
              document.querySelector('[data-modal-backdrop="1"]') && document.querySelector('[data-modal-backdrop="1"]').remove();
            });
          }
        })
        .catch(function () {
          listEl.innerHTML = '<p class="text-danger">Could not load bets.</p>';
          if (window.bootstrap && window.bootstrap.Modal) {
            var modal = new window.bootstrap.Modal(modalEl);
            modal.show();
          } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
          }
        });
    });
  }
})();

  // Real-time odds: subscribe to each market channel and update DOM when MarketOddsUpdated fires
  var echoDebug = window.location.search.indexOf('echo_debug=1') !== -1;
  var partyScriptRan = false;
  function subscribeMarketOdds() {
    if (typeof window.Echo === 'undefined') {
      return;
    }
    var cards = document.querySelectorAll('[data-market-id]');
    if (!partyScriptRan) {
      console.log('[Echo] Party page: found ' + cards.length + ' market(s), subscribing...');
      partyScriptRan = true;
    }
    cards.forEach(function (card) {
      var marketId = card.getAttribute('data-market-id');
      if (!marketId) return;
      var channelName = 'market.' + marketId;
      window.Echo.channel(channelName)
        .listen('.MarketOddsUpdated', function (e) {
          if (echoDebug) console.log('[Echo] MarketOddsUpdated received', channelName, e);
          if (!e) return;
          var status = e.status || '';
          var badgeEl = card.querySelector('[data-market-status-badge]');
          if (badgeEl) badgeEl.textContent = status || badgeEl.textContent;
          if (status && status !== 'live') {
            card.querySelectorAll('[data-bet-form]').forEach(function (form) {
              form.querySelectorAll('input, button').forEach(function (el) { el.disabled = true; });
            });
            var closedMsg = card.querySelector('[data-market-closed-message]');
            if (closedMsg) closedMsg.classList.remove('d-none');
          }
          if (e.pool != null && e.option_totals) {
            card.querySelectorAll('[data-option-id]').forEach(function (optionBlock) {
              var oid = optionBlock.getAttribute('data-option-id');
              if (oid && e.option_totals[oid] !== undefined) {
                optionBlock.setAttribute('data-pool', e.pool);
                optionBlock.setAttribute('data-total-on-option', e.option_totals[oid]);
                var myContracts = parseFloat(optionBlock.getAttribute('data-my-contracts')) || 0;
                var toWinSpan = optionBlock.querySelector('[data-option-to-win]');
                if (toWinSpan && myContracts > 0 && e.pool > 0) {
                  var totalOnOpt = parseFloat(e.option_totals[oid]) || 0;
                  var toWin = (e.pool * (myContracts / Math.max(totalOnOpt, myContracts))).toFixed(2);
                  toWinSpan.textContent = ' — to win ~$' + toWin;
                  toWinSpan.style.display = '';
                }
              }
            });
          }
          if (e.odds) {
            var odds = e.odds;
            Object.keys(odds).forEach(function (optionId) {
              var prob = parseFloat(odds[optionId]);
              var priceForBet = prob < 0.01 ? 0.01 : (prob > 1 ? 1 : prob);
              var optionBlock = card.querySelector('[data-option-id="' + optionId + '"]');
              if (!optionBlock) return;
              var oddsEl = optionBlock.querySelector('[data-option-odds]');
              var barEl = optionBlock.querySelector('[data-option-odds-bar]');
              var priceInput = optionBlock.querySelector('[data-option-price-input]');
              if (oddsEl) oddsEl.textContent = Math.round(priceForBet * 100) + '¢';
              if (barEl) barEl.style.width = Math.min(100, priceForBet * 100) + '%';
              if (priceInput) priceInput.value = priceForBet.toFixed(4);
              var form = optionBlock.querySelector('[data-bet-form]');
              if (form && typeof updateBetCost === 'function') updateBetCost(form);
              else if (form) {
                var amt = parseFloat(form.querySelector('[data-bet-amount]') && form.querySelector('[data-bet-amount]').value) || 0;
                var isDollars = form.getAttribute('data-bet-input-mode') === 'dollars';
                var costEl = form.querySelector('[data-bet-cost]');
                if (costEl) {
                  if (isDollars) costEl.textContent = 'You\'re spending: $' + (amt > 0 ? amt.toFixed(2) : '0.00');
                  else costEl.textContent = 'Cost: $' + ((amt * priceForBet) > 0 ? (amt * priceForBet).toFixed(2) : '0.00');
                }
              }
            });
          }
          if (e.recent_bets && Array.isArray(e.recent_bets)) {
            var container = card.querySelector('[data-market-recent-bets]');
            if (container) {
              if (e.recent_bets.length === 0) {
                container.innerHTML = '';
              } else {
                var marketId = card.getAttribute('data-market-id');
                var marketTitle = (card.getAttribute('data-market-title') || '').replace(/"/g, '&quot;');
                var html = '<details><summary class="small text-muted">Recent bets</summary><ul class="list-unstyled small mt-1" data-recent-bets-list>';
                e.recent_bets.forEach(function (b) {
                  var cost = (typeof b.amount === 'number' ? b.amount : parseFloat(b.amount) || 0) * (typeof b.price === 'number' ? b.price : parseFloat(b.price) || 0);
                  var price = typeof b.price === 'number' ? Math.round(b.price * 100) : (b.price ? Math.round(parseFloat(b.price) * 100) : 0);
                  html += '<li>' + (b.user_name || '—') + ': $' + cost.toFixed(2) + ' on ' + (b.option_label || '—') + ' @ ' + price + '¢</li>';
                });
                html += '</ul><button type="button" class="btn btn-link btn-sm p-0 mt-1" data-view-all-bets data-market-id="' + (marketId || '') + '" data-market-title="' + (marketTitle.replace(/"/g, '&quot;') || '') + '">View all bets</button></details>';
                container.innerHTML = html;
              }
            }
          }
        });
      console.log('[Echo] Subscribed to', channelName);
    });
    var partyContainer = document.querySelector('[data-party-id]');
    var partyId = partyContainer && partyContainer.getAttribute('data-party-id');
    if (partyId) {
      var partyChannel = 'party.' + partyId;
      window.Echo.channel(partyChannel).listen('.PartyMarketsUpdated', function () {
        window.location.reload();
      });
      console.log('[Echo] Subscribed to', partyChannel, '(reload on new/updated markets)');
    }
  }
  var tries = 0;
  function trySubscribe() {
    if (window.Echo) {
      subscribeMarketOdds();
      return;
    }
    tries++;
    if (tries === 1) console.log('[Echo] Waiting for app.js to load (window.Echo)...');
    if (tries >= 100) {
      console.warn('[Echo] Gave up: window.Echo not set. Check that /js/app.js loads and has no errors.');
      return;
    }
    setTimeout(trySubscribe, 50);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', trySubscribe);
  } else {
    trySubscribe();
  }
</script>
@endpush
@endsection
