@extends('layouts.app')

@section('title', 'Create Party')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shamrock-card">
                <div class="card-header shamrock-header">Create a new party</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.parties.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Party name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="default_balance" class="form-label">Default balance (fake $ per invited user)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('default_balance') is-invalid @enderror" id="default_balance" name="default_balance" value="{{ old('default_balance', 100) }}" required>
                            @error('default_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bet input</label>
                            <select class="form-select" name="bet_input_mode">
                                <option value="dollars" {{ old('bet_input_mode', 'contracts') === 'dollars' ? 'selected' : '' }}>Dollar amount (user enters $ to spend)</option>
                                <option value="contracts" {{ old('bet_input_mode', 'contracts') === 'contracts' ? 'selected' : '' }}>Number of contracts</option>
                            </select>
                            <small class="text-muted">Whether participants enter how much to spend ($) or number of contracts.</small>
                        </div>
                        <button type="submit" class="btn btn-shamrock">Create party</button>
                        <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
