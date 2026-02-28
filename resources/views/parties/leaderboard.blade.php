@extends('layouts.app')

@section('title', $party->name . ' — Leaderboard')

@section('content')
<div class="container" data-party-id="{{ $party->id }}">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Leaderboard — {{ $party->name }}</h1>
        <a href="{{ route('parties.show', $party) }}" class="btn btn-outline-secondary">Back to party</a>
    </div>
    <div class="card shamrock-card">
        <div class="card-header shamrock-header">Rankings</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Player</th><th>Available</th><th>Portfolio</th><th>Change</th></tr></thead>
                <tbody data-leaderboard-body>
                    @foreach($members as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['user']->name }}</td>
                            <td>${{ number_format($row['available'], 2) }}</td>
                            <td><strong>${{ number_format($row['portfolio'], 2) }}</strong></td>
                            <td data-change-cell>
                                @if(isset($row['change_pct']) && $row['change_pct'] !== null)
                                    @if($row['change_pct'] >= 0)
                                        <span class="text-success">+{{ number_format($row['change_pct'], 1) }}%</span>
                                    @else
                                        <span class="text-danger">{{ number_format($row['change_pct'], 1) }}%</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
  var body = document.querySelector('[data-leaderboard-body]');
  var container = document.querySelector('[data-party-id]');
  var partyId = container && container.getAttribute('data-party-id');
  if (!body || !partyId) return;
  function subscribe() {
    if (typeof window.Echo === 'undefined') {
      setTimeout(subscribe, 50);
      return;
    }
    window.Echo.channel('party.' + partyId).listen('.PartyLeaderboardUpdated', function (e) {
      if (!e || !e.members || !Array.isArray(e.members)) return;
      var html = '';
      e.members.forEach(function (row, index) {
        var available = typeof row.available === 'number' ? row.available.toFixed(2) : row.available;
        var portfolio = typeof row.portfolio === 'number' ? row.portfolio.toFixed(2) : row.portfolio;
        var changePct = row.change_pct;
        var changeHtml = changePct === null || changePct === undefined ? '<span class="text-muted">—</span>' :
          (changePct >= 0 ? '<span class="text-success">+' + Number(changePct).toFixed(1) + '%</span>' : '<span class="text-danger">' + Number(changePct).toFixed(1) + '%</span>');
        html += '<tr><td>' + (index + 1) + '</td><td>' + (row.user_name || '—') + '</td><td>$' + available + '</td><td><strong>$' + portfolio + '</strong></td><td>' + changeHtml + '</td></tr>';
      });
      body.innerHTML = html;
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', subscribe);
  } else {
    subscribe();
  }
})();
</script>
@endpush
@endsection
