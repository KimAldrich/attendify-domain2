{{-- resources/views/auth/_social-login.blade.php --}}
{{-- Script-only partial that wires the existing Google button --}}
{{-- Requirements: 
     - Your Blade page has a button with id="google-login" (or data-google-login)
     - Your layout includes: <meta name="csrf-token" content="{{ csrf_token() }}">
     - config('services.firebase.web.*') is set in config/services.php and .env
--}}

@push('scripts')
<script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.13.1/firebase-app.js";
import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.13.1/firebase-auth.js";

if (!window.__attendifyGoogleInit) {
  window.__attendifyGoogleInit = true; // avoid double-binding if partial gets included twice

  const firebaseConfig = {
    apiKey:     "{{ config('services.firebase.web.api_key') }}",
    authDomain: "{{ config('services.firebase.web.auth_domain') }}",
    projectId:  "{{ config('services.firebase.web.project_id') }}",
    appId:             "{{ config('services.firebase.web.app_id') }}",
    messagingSenderId: "{{ config('services.firebase.web.messaging_sender_id') }}",
    measurementId:     "{{ config('services.firebase.web.measurement_id') }}", // optional
  };

  const app  = initializeApp(firebaseConfig);
  const auth = getAuth(app);

  const btn = document.getElementById('google-login') 
            || document.querySelector('[data-google-login]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  if (btn) {
    btn.addEventListener('click', async () => {
      if (btn.disabled) return;
      btn.disabled = true;
      try {
        const provider = new GoogleAuthProvider();
        const cred = await signInWithPopup(auth, provider);

        // Get tokens
        const idToken = await cred.user.getIdToken(true); // force refresh
        const refreshToken = cred.user.refreshToken;      // handy for selective Firebase routes

        const resp = await fetch("{{ route('login.idtoken') }}", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf
          },
          body: JSON.stringify({
            id_token: idToken,
            refresh_token: refreshToken,
            remember: true // auto-remember social logins
          })
        });

        if (resp.redirected) {
          window.location = resp.url;
        } else {
          // If your controller ever returns JSON, handle it here; for now, just reload
          window.location.reload();
        }
      } catch (e) {
        console.error(e);
        alert('Google sign-in failed. Please try again.');
      } finally {
        btn.disabled = false;
      }
    }, { passive: true });
    console.log('[Attendify] idtoken POST status:', resp.status);
try { console.log('[Attendify] idtoken POST text:', await resp.clone().text()); } catch {}
  } else {
    // No button found; silent no-op
    console.warn('[Attendify] Google login button not found. Add id="google-login" to your button.');
  }
}
</script>
@endpush
