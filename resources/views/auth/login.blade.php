@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="text-center mb-4">
                <h1 class="shamrock-auth-title">☘ Shamrock Stakes</h1>
                <p class="text-muted mb-0">Sign in to your account</p>
            </div>
            <div class="card shamrock-card border-0 shadow">
                <div class="card-header shamrock-header rounded-0 py-3">Login</div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="you@example.com">
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-shamrock btn-lg w-100 py-2 mb-2">
                            Sign in
                        </button>
                        @if (Route::has('password.request'))
                            <a class="btn btn-link text-decoration-none d-block text-center" href="{{ route('password.request') }}">
                                Forgot your password?
                            </a>
                        @endif
                    </form>
                </div>
            </div>
            <p class="text-center text-muted mt-3 small">
                Don't have an account? <a href="{{ route('register') }}" class="fw-semibold">Register</a>
            </p>
        </div>
    </div>
</div>
@endsection
