@extends('layouts.guest')
@section('title','Attendify - Forgot Password')

@push('styles')
    @vite('resources/css/auth/reset-password.css')
@endpush

@section('content')
  <div class="container">
    <div class="left">
      <div class="form-header">
        <h3>Forgot Password?</h3>
        <p>No problem. Enter your email below and we'll send you a reset link.</p>
      </div>

      <!-- Session Status, in our case, we should use toast instead of this -->
      @if (session('status'))
        <div class="status" role="status" aria-live="polite">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
          <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
          @error('email')
            <div class="error">{{ $message }}</div>
          @enderror
        </div>

        <button type="submit" class="btn"><b>Email Password Reset Link</b></button>

        <a href="{{ route('login') }}">
          <button type="button" class="btn btn-secondary"><b>Return to Login</b></button>
        </a>
      </form>
    </div>

    <div class="right">
      <div class="logo-placeholder">
        <img src="{{ asset('images/branding/attendify-logo.png') }}" alt="School Logo" class="logo-image">
      </div>
      <div class="logo-text">
        <h2>Attendify</h2>
        <p>Secure password recovery for the Facial Recognition Smart Attendance System.</p>
      </div>
    </div>
  </div>
@endsection