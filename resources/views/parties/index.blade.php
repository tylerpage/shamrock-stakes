@extends('layouts.app')

@section('title', 'My Parties')

@section('content')
<div class="container">
    <h1 class="mb-4" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">My Parties</h1>

    @if($adminParties->isNotEmpty())
        <h2 class="h5 mb-2" style="color: #1a4d3e;">Parties you run</h2>
        <div class="row mb-4">
            @foreach($adminParties as $party)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card shamrock-card h-100">
                        <div class="card-header shamrock-header">{{ $party->name }}</div>
                        <div class="card-body">
                            <p class="mb-2">{{ $party->markets_count }} market(s)</p>
                            <a href="{{ route('admin.parties.show', $party) }}" class="btn btn-shamrock btn-sm btn-touch">Manage</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="h5 mb-2" style="color: #1a4d3e;">Parties you're in</h2>
    <div class="row">
        @forelse($parties as $party)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shamrock-card h-100">
                    <div class="card-header shamrock-header">{{ $party->name }}</div>
                    <div class="card-body">
                        <p class="mb-1">Balance: <strong>${{ number_format(auth()->user()->balanceInParty($party), 2) }}</strong></p>
                        <p class="mb-2">{{ $party->markets_count }} market(s)</p>
                        <a href="{{ route('parties.show', $party) }}" class="btn btn-shamrock btn-sm btn-touch">Enter</a>
                        <a href="{{ route('parties.leaderboard', $party) }}" class="btn btn-outline-secondary btn-sm btn-touch">Leaderboard</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shamrock-card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted">You're not in any parties yet. Get invited by an admin or create one if you're an admin.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
