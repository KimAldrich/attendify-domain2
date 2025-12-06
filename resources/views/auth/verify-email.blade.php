@extends('layouts.guest')
@section('title','Attendify - Verify Email')

@push('styles')
    @vite('resources/css/auth/verify-email.css')
@endpush

@section('content')
<div class="container">
  <div class="left">
    <div class="form-header">
      <h3>Verify Your Email</h3>
      <p>We sent a verification link to your email. Didn't get it? Resend below.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
      <div class="status" role="status" aria-live="polite">
        A new verification link has been sent to the email address you provided.
      </div>
    @endif

    <div class="btn-row">
      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn"><b>Resend Verification Email</b></button>
      </form>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-secondary"><b>Log Out</b></button>
      </form>

      <a class="btn btn-secondary" href="{{ route('login') }}"><b>Return to Login</b></a>
    </div>
  </div>

  <div class="right">
    <div class="logo-placeholder" aria-hidden="true">
      <img src="{{ asset('images/branding/attendify-logo.png') }}" alt="School Logo" class="logo-image">
    </div>
    <div class="logo-text">
      <h2>Attendify</h2>
      <p>Please verify your email to continue using the Facial Recognition Smart Attendance System.</p>
    </div>
  </div>
</div>
@endsection