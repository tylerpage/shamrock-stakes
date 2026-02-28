@extends('layouts.app')

@section('title', 'Admin — Users')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
        <h1 class="mb-0" style="font-family: 'Bebas Neue', sans-serif; color: #0d3328;">Manage Users</h1>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2">
                <input type="search" name="q" class="form-control" placeholder="Search name or email…" value="{{ request('q') }}" style="min-width:180px">
                <button type="submit" class="btn btn-outline-secondary">Search</button>
            </form>
            <a href="{{ route('admin.users.create') }}" class="btn btn-shamrock">Create User</a>
            <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary">Parties</a>
        </div>
    </div>

    <div class="card shamrock-card">
        <div class="card-header shamrock-header">Users</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Admin</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>@if($user->is_admin)<span class="badge badge-shamrock">Admin</span>@else—@endif</td>
                                <td>
                                    @if($user->isBlocked())
                                        <span class="badge bg-danger">Blocked</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @if($user->isBlocked())
                                        <form method="POST" action="{{ route('admin.users.unblock', $user) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-success">Unblock</button></form>
                                    @else
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.block', $user) }}" class="d-inline" onsubmit="return confirm('Block this user? They will not be able to sign in.');">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Block</button></form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{ $users->links() }}
</div>
@endsection
