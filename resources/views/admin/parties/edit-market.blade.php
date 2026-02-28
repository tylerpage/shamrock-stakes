@extends('layouts.app')

@section('title', 'Edit market — ' . $party->name)

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Edit market</h1>
        <a href="{{ route('admin.parties.show', $party) }}" class="btn btn-outline-secondary">Back to party</a>
    </div>

    <div class="card shamrock-card mb-4">
        <div class="card-header shamrock-header">Market details</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.parties.markets.update', [$party, $market]) }}">
                @csrf
                @method('PUT')
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $market->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="resolution_type" class="form-label">Resolution</label>
                        <select class="form-select" id="resolution_type" name="resolution_type" @if($market->isResolved()) disabled @endif>
                            <option value="official" {{ old('resolution_type', $market->resolution_type) === 'official' ? 'selected' : '' }}>Official outcome</option>
                            <option value="voting" {{ old('resolution_type', $market->resolution_type) === 'voting' ? 'selected' : '' }}>Voting (24h after end)</option>
                        </select>
                        @if($market->isResolved())<input type="hidden" name="resolution_type" value="{{ $market->resolution_type }}">@endif
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label for="ends_at" class="form-label">Ends at</label>
                        <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at', $market->ends_at ? $market->ends_at->format('Y-m-d\TH:i') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="voting_ends_at" class="form-label">Voting ends at</label>
                        <input type="datetime-local" class="form-control" id="voting_ends_at" name="voting_ends_at" value="{{ old('voting_ends_at', $market->voting_ends_at ? $market->voting_ends_at->format('Y-m-d\TH:i') : '') }}">
                    </div>
                </div>
                <p class="text-muted small mb-2">Type: {{ $market->type }} · Status: {{ $market->status }}</p>
                <button type="submit" class="btn btn-shamrock">Save market</button>
            </form>
        </div>
    </div>

    <div class="card shamrock-card">
        <div class="card-header shamrock-header">Options</div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                @foreach($market->options as $opt)
                    <li class="list-group-item d-flex align-items-center">
                        @if($opt->image_url)
                            <img src="{{ $opt->image_url }}" alt="" class="rounded me-2" style="height:36px; width:36px; object-fit:cover;">
                        @endif
                        <span class="me-auto">{{ $opt->label }}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#edit-option-{{ $opt->id }}">Edit</button>
                        @if($market->type === 'people' && $opt->bets_count == 0 && $opt->pre_votes_count == 0)
                            <form method="POST" action="{{ route('admin.parties.markets.options.destroy', [$party, $market, $opt]) }}" class="d-inline" onsubmit="return confirm('Remove this option?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        @endif
                    </li>
                    {{-- Edit option modal --}}
                    <div class="modal fade" id="edit-option-{{ $opt->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.parties.markets.options.update', [$party, $market, $opt]) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit option</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label">Label</label>
                                            <input type="text" class="form-control" name="label" value="{{ old('label', $opt->label) }}" required>
                                        </div>
                                        <div>
                                            <label class="form-label">Image (optional, leave empty to keep current)</label>
                                            <input type="file" class="form-control" name="image" accept="image/*">
                                            @if($opt->image_path)
                                                <p class="small text-muted mt-1">Current: <img src="{{ $opt->image_url }}" alt="" class="rounded" style="height:24px;width:24px;object-fit:cover;"></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-shamrock">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </ul>
            @if($market->type === 'people')
                <form method="POST" action="{{ route('admin.parties.markets.options.store', [$party, $market]) }}" class="row g-2 mt-3" enctype="multipart/form-data">
                    @csrf
                    <div class="col-auto"><input type="text" class="form-control form-control-sm" name="label" placeholder="New option label" required></div>
                    <div class="col-auto"><input type="file" class="form-control form-control-sm" name="image" accept="image/*"></div>
                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-shamrock">Add option</button></div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
