@extends('layouts.app')

@section('title', 'Create market — ' . $party->name)

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="mb-3" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Create a market</h1>
            <p class="text-muted mb-4">Add a Yes/No market to <strong>{{ $party->name }}</strong>. It will go live immediately (no pre-vote). The house will seed 10% on Yes and 10% on No so odds don’t swing too much at the start.</p>
            <div class="card shamrock-card border-0 shadow">
                <div class="card-header shamrock-header rounded-0 py-3">New market</div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('parties.markets.store', $party) }}">
                        @csrf
                        <div class="mb-4">
                            <label for="title" class="form-label">Question / title</label>
                            <input id="title" type="text" class="form-control form-control-lg @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Will it rain tomorrow?">
                            @error('title')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-shamrock btn-touch">Create market</button>
                            <a href="{{ route('parties.show', $party) }}" class="btn btn-outline-secondary btn-touch">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
