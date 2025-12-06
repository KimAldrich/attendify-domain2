@extends('layouts.guest')
@section('title','Attendify - Register Account')

@push('styles')
    @vite('resources/css/auth/register.css')
@endpush

@section('content')
    <div class="container">
    <div class="left">
      <div class="form-header">
        <h3>Create Account</h3>
        <p>Register an accounnt on Attendify.</p>
      </div>

      <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <!-- First & Last Name -->
        <div class="form-row">
          <div class="form-group">
            <label for="first_name" class="visually-hidden">First Name</label>
            <input id="first_name" type="text" name="first_name" placeholder="Juan" value="{{ old('first_name') }}" required>
            @error('first_name') <small style="color: red;">{{ $message }}</small> @enderror
          </div>
          <div class="form-group">
            <label for="last_name" class="visually-hidden">Last Name</label>
            <input id="last_name" type="text" name="last_name" placeholder="Dela Cruz" value="{{ old('last_name') }}" required>
            @error('last_name') <small style="color: red;">{{ $message }}</small> @enderror
          </div>
        </div>

        <!-- Middle Name + Phone -->
        <div class="form-row">
          <div class="form-group">
            <label for="middle_name" class="visually-hidden">Middle Name <small><i>(optional)</i></small></label>
            <input id="middle_name" type="text" name="middle_name" placeholder="Santos" value="{{ old('middle_name') }}">
            @error('middle_name') <small style="color: red;">{{ $message }}</small> @enderror
          </div>
          <div class="form-group">
            <label for="cp_no" class="visually-hidden">Mobile Number</label>
            <input id="cp_no" type="text" name="cp_no" placeholder="09xxxxxxxxx" value="{{ old('cp_no') }}" required>
            @error('cp_no') <small style="color: red;">{{ $message }}</small> @enderror
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="visually-hidden">Email</label>
          <input id="email" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
          @error('email') <small style="color: red;">{{ $message }}</small> @enderror
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="visually-hidden">Password</label>
          <input id="password" type="password" name="password" placeholder="Password" required>
          @error('password') <small style="color: red;">{{ $message }}</small> @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="visually-hidden">Confirm Password</label>
          <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm Password" required>
          @error('password_confirmation') <small style="color: red;">{{ $message }}</small> @enderror
        </div>

        <!-- Terms -->
        <div
          x-data="{
            open:false, accepted:false, scrolled:false,

            openTac() {
              this.open = true;
              this.scrolled = false;
              this.$nextTick(() => {
                // reset scroll
                this.$refs.scrollbox.scrollTop = 0;
                // compute whether content actually needs scrolling
                this.checkScroll();
              });
            },

            checkScroll() {
              const el = this.$refs.scrollbox;
              if (!el) return;
              const needsScroll = el.scrollHeight > el.clientHeight + 1; // +1 for rounding
              this.scrolled = !needsScroll || (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 4);
            },

            acceptTac() {
              this.accepted = true;
              this.$refs.terms.checked = true;
              this.open = false;
            }
          }"
          class="terms"
          x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        >
          <label class="checkbox-label">
            <input
              type="checkbox"
              name="terms"
              value="1"
              x-ref="terms"
              :checked="accepted"
              @click.prevent="if (!accepted) openTac()"
              required
            >
            <div class="terms-link">
            I agree to the
            <a href="#" @click.prevent="openTac()" @keydown.enter.prevent="openTac()"><b>Terms</b></a>
            and
            <a href="#" @click.prevent="openTac()" @keydown.enter.prevent="openTac()"><b>Privacy Policy</b></a>.
          </div>
          </label>

          @error('terms') <small style="color:red;">{{ $message }}</small> @enderror

          <!-- Overlay -->
          <template x-teleport="body">
            <div
              x-cloak
              x-show="open"
              x-transition.opacity
              class="tac-overlay"
              @click.self="open=false"
              aria-modal="true"
              role="dialog"
            >
              <div
                x-cloak
                x-show="open"
                x-transition
                class="tac-card"
                @keydown.escape.window="open=false"
                tabindex="-1"
                @resize.window="checkScroll()"
              >
                <div class="tac-header">
                  <h4>Terms &amp; Conditions</h4>
                </div>

                <!-- Scrollable content -->
                <div class="tac-content" x-ref="scrollbox" @scroll="checkScroll()">
                  <div class="tac-compact">
                    @include('legal.terms_body')
                    <hr class="hr-exec my-6">
                    @include('legal.privacy_body')
                  </div>

                  <div class="mt-4 text-sm opacity-80">
                    Read the full pages:
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a> ·
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>
                  </div>
                </div>

                <div class="tac-actions">
                  <button type="button" class="tac-btn tac-btn--ghost" @click="open=false">Cancel</button>
                  <button
                    type="button"
                    class="tac-btn tac-btn--primary"
                    :disabled="!scrolled"
                    :class="{'is-disabled': !scrolled}"
                    @click="acceptTac()"
                  >
                    I agree to the Terms and Privacy Policy
                  </button>
                </div>
              </div>
            </div>
          </template>

        </div>


        <button type="submit" class="btn"><b>Sign Up</b></button>
      </form>

      <div class="login-link">
        Already have an account? <a href="{{ route('login') }}"><b>Log In</b></a>
      </div>
    </div>

    <div class="right">
      <div class="logo-placeholder">
        <img src="{{ asset('images/branding/attendify-logo.png') }}" alt="School Logo" class="logo-image">
      </div>
      <div class="logo-text">
        <h2>Attendify</h2>
        <p>Get started with the Facial Recognition Smart Attendance System.</p>
      </div>
    </div>
  </div>
@endsection

