@extends('layouts.guest')
@section('title','Attendify - Reset Password')

@push('styles')
    @vite('resources/css/auth/reset-password.css')
@endpush

@section('content')
  <div class="container">
    <div class="left">
      <div class="form-header">
        <h3>Reset Password</h3>
        <p>Enter your new password to regain access.</p>
      </div>

      <form method="POST" action="{{ route('password.update.firebase') }}">
        @csrf
        <input type="hidden" name="oobCode" value="{{ $oobCode }}">

        <div class="form-group">
          <!-- <input id="email" type="email" value="{{ $email }}" readonly class="input readonly" aria-readonly="true"> -->
          <div class="email-display">{{ $email }}</div>
        </div>

        <div class="form-group">
          <input id="password" type="password" name="password" required placeholder="New Password">
          @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <ul class="reqs">
          <li id="req-length" class="bad">At least 8 characters</li>
          <li id="req-upper"  class="bad">At least 1 uppercase letter</li>
          <li id="req-lower"  class="bad">At least 1 lowercase letter</li>
          <li id="req-number" class="bad">At least 1 number</li>
        </ul>
        <small class="muted">We also block passwords known to appear in public data leaks.</small>

        @push('scripts')
        <script>
        (function(){
          const pwd = document.getElementById('password');
          if (!pwd) return;
          const mark = (el, ok) => el.className = ok ? 'ok' : 'bad';
          const els = {
            length: document.getElementById('req-length'),
            upper:  document.getElementById('req-upper'),
            lower:  document.getElementById('req-lower'),
            number: document.getElementById('req-number'),
          };
          const check = v => {
            mark(els.length, /.{8,}/.test(v));
            mark(els.upper,  /[A-Z]/.test(v));
            mark(els.lower,  /[a-z]/.test(v));
            mark(els.number, /\d/.test(v));
          };
          check(pwd.value || '');
          pwd.addEventListener('input', e => check(e.target.value));
        })();
        </script>
        @endpush

        <div class="form-group">
          <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="Confirm Password">
          @error('password_confirmation') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-primary"><b>Reset Password</b></button>
        </div>

        <p class="backline">
          <a href="{{ route('login') }}">Back to login</a>
        </p>
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