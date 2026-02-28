@extends('layouts.app')

@section('title', 'Admin — Parties')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Manage Parties</h1>
        <a href="{{ route('admin.parties.create') }}" class="btn btn-shamrock">Create Party</a>
    </div>
    <div class="row">
        @forelse($parties as $party)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shamrock-card h-100">
                    <div class="card-header shamrock-header">{{ $party->name }}</div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Default balance:</strong> ${{ number_format($party->default_balance, 2) }}</p>
                        <p class="mb-2"><strong>Markets:</strong> {{ $party->markets_count }}</p>
                        <a href="{{ route('admin.parties.show', $party) }}" class="btn btn-shamrock btn-sm">Manage</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shamrock-card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-3">No parties yet.</p>
                        <a href="{{ route('admin.parties.create') }}" class="btn btn-shamrock">Create your first party</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
    {{ $parties->links() }}
</div>
@endsection
