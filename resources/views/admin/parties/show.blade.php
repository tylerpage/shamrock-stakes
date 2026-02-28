@extends('layouts.app')

@section('title', $party->name . ' — Admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">{{ $party->name }}</h1>
        <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary">Back to parties</a>
    </div>

    {{-- Party settings --}}
    <div class="card shamrock-card mb-4">
        <div class="card-header shamrock-header">Party settings</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.parties.update', $party) }}" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label for="bet_input_mode" class="form-label">Bet input</label>
                    <select class="form-select" name="bet_input_mode" id="bet_input_mode">
                        <option value="dollars" {{ ($party->bet_input_mode ?? 'contracts') === 'dollars' ? 'selected' : '' }}>Dollar amount ($ to spend)</option>
                        <option value="contracts" {{ ($party->bet_input_mode ?? 'contracts') === 'contracts' ? 'selected' : '' }}>Number of contracts</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-shamrock">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Invite & balance --}}
    <div class="card shamrock-card mb-4">
        <div class="card-header shamrock-header">Invite & balances</div>
        <div class="card-body">
            <p class="small text-muted mb-2">Invite by email. If they already have an account, they’re added now. If not, they’ll see this party when they register with that email.</p>
            <form method="POST" action="{{ route('admin.parties.invite', $party) }}" class="row g-2 mb-3">
                @csrf
                <div class="col-auto">
                    <label for="email" class="col-form-label">Email</label>
                </div>
                <div class="col-md-4">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="user@example.com" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-shamrock">Invite</button></div>
            </form>
            @if($party->partyInvitations->isNotEmpty())
                <p class="small mb-1"><strong>Pending</strong> (will join when they register with this email):</p>
                <ul class="small text-muted mb-3">
                    @foreach($party->partyInvitations as $inv)
                        <li>{{ $inv->email }}</li>
                    @endforeach
                </ul>
            @endif
            <table class="table table-sm">
                <thead><tr><th>User</th><th>Balance</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($party->members as $member)
                        <tr>
                            <td>{{ $member->user->name }} ({{ $member->user->email }})</td>
                            <td>${{ number_format($member->balance, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.parties.balance', $party) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                    <input type="number" step="0.01" min="0" name="balance" value="{{ $member->balance }}" class="form-control form-control-sm d-inline-block" style="width:90px">
                                    <button type="submit" class="btn btn-sm btn-shamrock">Set</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @php $pendingProposals = $party->markets->flatMap(fn ($m) => $m->pendingResolutionProposal ? [$m->pendingResolutionProposal] : []); @endphp
    @if($pendingProposals->isNotEmpty())
    <div class="card shamrock-card mb-4 border-warning">
        <div class="card-header shamrock-header d-flex justify-content-between align-items-center">
            <span>Pending resolution proposals ({{ $pendingProposals->count() }})</span>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">A user proposed an outcome for these markets. Accept to resolve with that outcome, or deny to forfeit the proposer’s position and resume the market.</p>
            @foreach($pendingProposals as $proposal)
                <div class="border rounded p-3 mb-3" style="border-color: #0d3328 !important;">
                    <strong>{{ $proposal->market->title }}</strong>
                    <p class="mb-1 small"><strong>Proposed by:</strong> {{ $proposal->user->name }} · <strong>Winning outcome:</strong> {{ $proposal->winningOption->label ?? '—' }}</p>
                    <p class="mb-2">{{ $proposal->description }}</p>
                    @if($proposal->photos->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach($proposal->photos as $photo)
                                <a href="{{ $photo->url() }}" target="_blank" rel="noopener"><img src="{{ $photo->url() }}" alt="Proposal photo" class="rounded" style="max-height:120px; max-width:200px; object-fit:cover;"></a>
                            @endforeach
                        </div>
                    @endif
                    <div class="d-flex flex-wrap gap-2 align-items-end">
                        <form method="POST" action="{{ route('admin.resolution-proposals.accept', [$party, $proposal]) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-shamrock">Accept & resolve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.resolution-proposals.deny', [$party, $proposal]) }}" class="d-inline">
                            @csrf
                            <input type="text" name="denial_reason" class="form-control form-control-sm d-inline-block" style="width:200px" placeholder="Denial reason (optional)">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Deny (forfeit proposer)</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Create market --}}
    <div class="card shamrock-card mb-4">
        <div class="card-header shamrock-header">Create market</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.parties.markets.store', $party) }}">
                @csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="yes_no">Yes / No</option>
                            <option value="people">People (add options below)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="resolution_type" class="form-label">Resolution</label>
                        <select class="form-select" id="resolution_type" name="resolution_type">
                            <option value="official">Official outcome</option>
                            <option value="voting">Voting (24h after end)</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label for="ends_at" class="form-label">Ends at (optional)</label>
                    <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at') }}">
                </div>
                <button type="submit" class="btn btn-shamrock">Add market</button>
            </form>
        </div>
    </div>

    {{-- Markets list & options --}}
    <div class="card shamrock-card mb-4">
        <div class="card-header shamrock-header d-flex justify-content-between align-items-center">
            <span>Markets</span>
            <div>
                <form method="POST" action="{{ route('admin.parties.start-pre-voting', $party) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-shamrock">Start pre-voting</button>
                </form>
                <form method="POST" action="{{ route('admin.parties.start-live', $party) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-shamrock">Go live</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @foreach($party->markets as $market)
                <div class="border rounded p-3 mb-3" style="border-color: #0d3328 !important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>{{ $market->title }}</strong>
                            <span class="badge badge-shamrock ms-2">{{ $market->status === 'pending_resolution' ? 'Under review' : $market->status }}</span>
                            <span class="text-muted small">({{ $market->type }}, {{ $market->resolution_type }})</span>
                        </div>
                        <a href="{{ route('admin.parties.markets.edit', [$party, $market]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    </div>
                    <ul class="list-inline mb-2 mt-2">
                        @foreach($market->options as $opt)
                            <li class="list-inline-item">
                                @if($opt->image_path)
                                    <img src="{{ $opt->image_url }}" alt="" class="rounded" style="height:28px; width:28px; object-fit:cover;">
                                @endif
                                {{ $opt->label }}
                            </li>
                        @endforeach
                    </ul>
                    @if($market->type === 'people')
                        <form method="POST" action="{{ route('admin.parties.markets.options.store', [$party, $market]) }}" class="row g-2 mb-2" enctype="multipart/form-data">
                            @csrf
                            <div class="col-auto"><input type="text" class="form-control form-control-sm" name="label" placeholder="Option label" required></div>
                            <div class="col-auto"><input type="file" class="form-control form-control-sm" name="image" accept="image/*"></div>
                            <div class="col-auto"><button type="submit" class="btn btn-sm btn-shamrock">Add option</button></div>
                        </form>
                    @endif
                    @if($market->status === 'live' || $market->status === 'resolved')
                        @if(!$market->resolution)
                            <form method="POST" action="{{ $market->resolution_type === 'official' ? route('admin.parties.markets.resolve-official', [$party, $market]) : route('admin.parties.markets.resolve-voting', [$party, $market]) }}" class="mt-2">
                                @csrf
                                <select name="winning_option_id" class="form-select form-select-sm d-inline-block w-auto">
                                    @foreach($market->options as $o)<option value="{{ $o->id }}">{{ $o->label }}</option>@endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-shamrock">Resolve</button>
                            </form>
                        @else
                            <p class="text-success small mb-0">Resolved: {{ $market->resolution->winningOption->label ?? 'N/A' }}</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
