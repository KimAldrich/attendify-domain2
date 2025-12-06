@extends('layouts.guest')
@section('title','Attendify - Login')

@push('styles')
    @vite('resources/css/auth/login.css')
@endpush


@section('content')
<div class="container">
  <div class="left">
    <div class="form-header">
      <h3>Welcome Back</h3>
      <p>Please sign in to your account</p>
    </div>

    <form class="auth-form" method="POST" action="{{ route('login') }}" novalidate>
      @csrf

      <div class="form-group">
        <label for="email" class="visually-hidden">Email</label>
        <input
          id="email"
          type="email"
          name="email"
          placeholder="Email"
          value="{{ old('email') }}"
          autocomplete="email"
          required
          autofocus
        >
        @error('email')
          <small style="color:red;">{{ $message }}</small>
        @enderror
      </div>

      <div class="form-group">
        <label for="password" class="visually-hidden">Password</label>
        <input
          id="password"
          type="password"
          name="password"
          placeholder="Password"
          autocomplete="current-password"
          required
        >
        @error('password')
          <small style="color:red;">{{ $message }}</small>
        @enderror
      </div>

      <div class="remember-forgot">
        <label class="remember-inline">
          <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
          Remember Me
        </label>

        <a href="{{ route('password.request') }}" aria-disabled="true"><b>Forgot Password?</b></a>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn"><b>Log in</b></button>
      </div>
    </form>

    <div class="or">Or login with</div>
    <div class="social-login-wrapper">
      <div class="social-login">
        <button id="google-login" type="button" class="unstyled-btn" aria-label="Continue with Google">
          <img src="https://cdn-icons-png.flaticon.com/512/281/281764.png" alt="Continue with Google">
        </button>
        <!-- <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Continue with Facebook" role="button" tabindex="0"> -->
      </div>
    </div>

    <div class="signup">
      Don't have an account?
      <a href="{{ route('register') }}"><b>Sign Up</b></a>
    </div>
  </div>

  <div class="right">
    <div class="logo-placeholder" aria-hidden="true">
      <img src="{{ asset('images/branding/attendify-logo.png') }}" alt="School Logo" class="logo-image">
    </div>
    <div class="logo-text">
      <h2 class="fw-bold">Attendify</h2>
      <p class="mb-0 opacity-75">Sign in to the Facial Recognition Smart Attendance System.</p>
    </div>
  </div>
</div>
@endsection
@include('auth._social-login')
