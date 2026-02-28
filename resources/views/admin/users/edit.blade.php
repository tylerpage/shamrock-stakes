@extends('layouts.app')

@section('title', 'Admin — Edit ' . $user->name)

@section('content')
<div class="container">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm btn-touch">← Users</a>
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Edit user</h1>
        @if($user->isBlocked())
            <span class="badge bg-danger ms-2">Blocked</span>
        @endif
    </div>

    <div class="card shamrock-card">
        <div class="card-header shamrock-header">{{ $user->name }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New password (leave blank to keep current)</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>
                <div class="mb-4">
                    <div class="form-check">
                        <input type="hidden" name="is_admin" value="0">
                        <input type="checkbox" class="form-check-input" name="is_admin" id="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_admin">Administrator</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-shamrock">Save changes</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>

            @if(!$user->isBlocked() && $user->id !== auth()->id())
                <hr class="my-4">
                <form method="POST" action="{{ route('admin.users.block', $user) }}" class="d-inline" onsubmit="return confirm('Block this user? They will not be able to sign in.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">Block user</button>
                </form>
            @endif
            @if($user->isBlocked())
                <form method="POST" action="{{ route('admin.users.unblock', $user) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-success">Unblock user</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
