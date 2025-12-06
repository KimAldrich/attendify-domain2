@extends('layouts.newlanding')

@section('content')
<!-- Skip link for accessibility -->
<a class="skip-link" href="#main">Skip to content</a>

<!-- Header / Navigation Bar -->
<header class="attendify-header" role="banner">
  <nav class="navbar container" aria-label="Primary">
    <div class="nav-left">
        <a href="#home" class="logo" aria-label="Attendify Home">
        <img src="{{ asset('images/branding/attendify-brand.png') }}"
            alt="Attendify Logo"
            class="logo-img">
        </a>
    </div>

    <!-- Mobile toggle -->
    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-menu">
      <span class="sr-only">Toggle navigation</span>
      <i class="fa-solid fa-bars"></i>
    </button>

    <ul id="primary-menu" class="nav-links" data-collapsible>
      <li><a href="#home">Home</a></li>
      <li><a href="#how-it-works">Easy Event Sign-In</a></li>
      <li><a href="#events">Campus Events</a></li>
      <li><a href="#attendance">Smart Attendance</a></li>
      <li><a href="#testimonials">Testimonials</a></li>
    </ul>

    <div class="nav-cta">
      <a href="{{route("register")}}" class="btn btn-primary">Register</a>
      <a href="{{route("login")}}" class="btn btn-secondary">Sign in</a>
    </div>
  </nav>
</header>

<main id="main">
  <!-- Hero Section -->
  <section class="hero-section section-pad" id="home">
    <div class="hero-content container">
      <div class="hero-left">
        <h1 class="hero-title">BECOME ENGAGED, ACTIVELY PARTICIPATE</h1>
        <p class="hero-subtext">Attendify helps students and faculty stay engaged and participate in campus events with smart attendance solutions.</p>
        <a href="{{route("register")}}" class="btn btn-primary">Register</a>
      </div>
      <div class="hero-right">
        <img src="/images/girl.png"
             alt=""
             class="hero-img standout-img"
             loading="lazy"
             decoding="async"
             width="640" height="640">
      </div>
    </div>
    <div class="wave-separator" aria-hidden="true"></div>
  </section>

  <!-- Easy Event Sign-In Section -->
  <section class="how-it-works-section section-pad" id="how-it-works">
    <div class="container">
      <h2 class="section-title">Easy Event Sign-In</h2>
      <p class="section-subtitle">A simple step process to get started</p>
      <div class="how-it-works-steps">
        <div class="step">
          <div class="icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></div>
          <h3>Register to our Events</h3>
          <p>Sign up for upcoming campus events easily.</p>
        </div>
        <div class="step">
          <div class="icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
          <h3>Be Reminded</h3>
          <p>Get timely reminders so you never miss out.</p>
        </div>
        <div class="step">
          <div class="icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></div>
          <h3>Join us at the Venue</h3>
          <p>Check in at the event location and participate.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Smart Classroom Attendance Section -->
  <section class="attendance-section section-pad" id="attendance">
    <div class="container">
      <h2 class="section-title">Smart Classroom Attendance</h2>
      <p class="section-subtitle">Automated, contactless, and secure attendance tracking with facial recognition.</p>

      <div class="attendance-content">
        <div class="attendance-img">
          <img src="/images/class.JPEG"
               alt="Students in class during smart attendance"
               class="attendance-photo"
               loading="lazy"
               decoding="async"
               width="700" height="468">
          <div class="facial-recognition-overlay" aria-hidden="true"></div>
        </div>

        <div class="attendance-features">
          <ul class="features-list">
            <li class="feature-row">
              <span class="feature-icon-btn" aria-hidden="true"><i class="fas fa-check"></i></span>
              <div class="feature-text">
                <span class="feature-heading">Accurate Attendance Tracking</span>
                <span class="feature-desc">Reduces errors and ensures every student is counted correctly.</span>
              </div>
            </li>
            <li class="feature-row">
              <span class="feature-icon-btn" aria-hidden="true"><i class="fas fa-clock"></i></span>
              <div class="feature-text">
                <span class="feature-heading">Time-Saving Automation</span>
                <span class="feature-desc">Minimizes roll call time with automated check-ins.</span>
              </div>
            </li>
            <li class="feature-row">
              <span class="feature-icon-btn" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
              <div class="feature-text">
                <span class="feature-heading">Contactless & Secure</span>
                <span class="feature-desc">Promotes hygiene and security with touch-free technology.</span>
              </div>
            </li>
            <li class="feature-row">
              <span class="feature-icon-btn" aria-hidden="true"><i class="fas fa-download"></i></span>
              <div class="feature-text">
                <span class="feature-heading">Real-Time Monitoring</span>
                <span class="feature-desc">Instant updates for admins and faculty, anytime.</span>
              </div>
            </li>
          </ul>
        </div>
      </div>

    </div>
  </section>

  <!-- Campus Events Section -->
  <section class="campus-events-section section-pad" id="events">
    <div class="container">
      <h2 class="section-title">Campus Events</h2>

      <div class="events-cards">
        <!-- Event Card 1 -->
        <article class="event-card">
          <div class="event-image-area">
            <img src="/images/event1.jpg" alt="Indonesia - Korea Conference poster"
                 class="event-img" loading="lazy" decoding="async" width="640" height="360">
            <div class="event-actions">
              <button class="event-action-btn" aria-label="Share event"><i class="fas fa-share-alt"></i></button>
            </div>
          </div>
          <div class="event-info-area">
            <div class="event-date" aria-label="September 18">
              <span class="event-month">SEP</span>
              <span class="event-day">18</span>
            </div>
            <div class="event-details">
              <h3 class="event-title">Indonesia - Korea Conference</h3>
              <p class="event-location">Soehanna, Daerah Khusus Ibukota Yogyakarta, Indonesia</p>
            </div>
          </div>
        </article>

        <!-- Event Card 2 -->
        <article class="event-card">
          <div class="event-image-area">
            <img src="/images/event2.jpg" alt="Dream World Wide in Jakarta poster"
                 class="event-img" loading="lazy" decoding="async" width="640" height="360">
            <div class="event-actions">
              <button class="event-action-btn" aria-label="Share event"><i class="fas fa-share-alt"></i></button>
            </div>
          </div>
          <div class="event-info-area">
            <div class="event-date" aria-label="September 17">
              <span class="event-month">SEP</span>
              <span class="event-day">17</span>
            </div>
            <div class="event-details">
              <h3 class="event-title">Dream World Wide in Jakarta</h3>
              <p class="event-location">Jakarta Convention Center, Indonesia</p>
            </div>
          </div>
        </article>

        <!-- Event Card 3 -->
        <article class="event-card">
          <div class="event-image-area">
            <img src="/images/event3.jpg" alt="Campus Sparkler Night poster"
                 class="event-img" loading="lazy" decoding="async" width="640" height="360">
            <div class="event-actions">
              <button class="event-action-btn" aria-label="Share event"><i class="fas fa-share-alt"></i></button>
            </div>
          </div>
          <div class="event-info-area">
            <div class="event-date" aria-label="September 16">
              <span class="event-month">SEP</span>
              <span class="event-day">16</span>
            </div>
            <div class="event-details">
              <h3 class="event-title">Campus Sparkler Night</h3>
              <p class="event-location">PSU-UCC Main Hall, Philippines</p>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="testimonials-section section-pad" id="testimonials">
    <div class="container">
      <h2 class="section-title">Testimonials From Recent PSU-UCC Events on Attendify</h2>

      <div class="testimonials-cards">
        <figure class="testimonial-card">
          <div class="testimonial-info">
            <div class="testimonial-profile">
              <img src="/images/profile1.jpg" alt="" class="testimonial-pic" loading="lazy" decoding="async" width="96" height="96">
            </div>
            <figcaption class="testimonial-name">Jane D.</figcaption>
          </div>
          <blockquote class="testimonial-text">“Attendify made the Prom Gala so much fun and easy to join!”</blockquote>
          <div class="testimonial-info-details">
            <span class="testimonial-location">PSU-UCC</span>
            <span class="testimonial-event">PSU Prom Gala 2024</span>
          </div>
        </figure>

        <figure class="testimonial-card">
          <div class="testimonial-info">
            <div class="testimonial-profile">
              <img src="/images/profile2.jpg" alt="" class="testimonial-pic" loading="lazy" decoding="async" width="96" height="96">
            </div>
            <figcaption class="testimonial-name">Mark S.</figcaption>
          </div>
          <blockquote class="testimonial-text">“I met new friends and never missed an event thanks to reminders.”</blockquote>
          <div class="testimonial-info-details">
            <span class="testimonial-location">PSU-UCC</span>
            <span class="testimonial-event">PSU Prom Gala 2024</span>
          </div>
        </figure>

        <figure class="testimonial-card">
          <div class="testimonial-info">
            <div class="testimonial-profile">
              <img src="/images/profile3.jpg" alt="" class="testimonial-pic" loading="lazy" decoding="async" width="96" height="96">
            </div>
            <figcaption class="testimonial-name">Alyssa T.</figcaption>
          </div>
          <blockquote class="testimonial-text">“The smart attendance system is so convenient and secure!”</blockquote>
          <div class="testimonial-info-details">
            <span class="testimonial-location">PSU-UCC</span>
            <span class="testimonial-event">PSU Prom Gala 2024</span>
          </div>
        </figure>
      </div>
    </div>
  </section>
</main>

<!-- Footer -->
<footer class="attendify-footer">
  <div class="footer-main container">
    <div class="footer-left">
      <div class="logo">Attendify</div>
      <p class="vision">Our vision is to provide convenience and help revolutionize attendance tracking.</p>
      <div class="social-icons" aria-label="Quick socials">
        <!-- Optional quick icons (kept) -->
        <a href="#home" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></a>
      </div>
    </div>

    <!-- Attendify page jumpers -->
    <nav class="footer-center" aria-label="Attendify sections">
      <h4>Attendify</h4>
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="#how-it-works">Easy Event Sign-In</a></li>
        <li><a href="#events">Campus Events</a></li>
        <li><a href="#attendance">Smart Attendance</a></li>
        <li><a href="#testimonials">Testimonials</a></li>
      </ul>
    </nav>

    <!-- Social (opens in new tab) -->
    <div class="footer-right">
      <h4>Social</h4>
      <ul class="footer-socials-list">
        <li>
          <a href="https://web.facebook.com/people/Attendify/61581450396632/"
             target="_blank" rel="noopener noreferrer">
            <i class="fab fa-facebook"></i> Facebook
          </a>
        </li>
        <li>
          <a href="https://www.linkedin.com/in/attendify-support-1297a4387/"
             target="_blank" rel="noopener noreferrer">
            <i class="fab fa-linkedin"></i> LinkedIn
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom container">
    <span>©2025 Attendify. All rights reserved.</span>
    <span class="footer-links">
      <a href="{{ route('privacy') }}">Privacy & Policy</a> | <a href="{{ route('terms') }}">Terms & Condition</a>
    </span>
  </div>
</footer>

<!-- Tiny inline script for mobile nav -->
<script>
  (function () {
    const btn = document.querySelector('.nav-toggle');
    const menu = document.querySelector('#primary-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      menu.classList.toggle('is-open');
    });

    // Close on resize up
    let lastW = window.innerWidth;
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 992 && lastW < 992) {
        btn.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
      }
      lastW = window.innerWidth;
    });
  }());
</script>
@endsection
