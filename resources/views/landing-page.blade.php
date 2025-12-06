@extends('layouts.landing')
@push('styles')
    @vite('resources/css/landing.css')
@endpush
@section('content')
<!-- Header / Navigation Bar -->
<header class="attendify-header">
    <nav class="navbar">
        <a href="#home" class="logo" aria-label="Attendify Home">
        <img src="{{ asset('images/branding/attendy-brand.png') }}"
            alt="Attendify Logo"
            class="logo-img">
        </a>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
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

<!-- Hero Section -->
<section class="hero-section" id="home">
    <div class="hero-content">
        <div class="hero-left">
            <h1 class="hero-title">BECOME ENGAGED, ACTIVELY PARTICIPATE</h1>
            <p class="hero-subtext">Attendify helps students and faculty stay engaged and participate in campus events with smart attendance solutions.</p>
            <a href="{{route("register")}}" class="btn btn-primary">Register</a>
        </div>
        <div class="hero-right">
            <img src="/images/girl.png" alt="Engaged Student" class="hero-img standout-img" aria-hidden="true">
        </div>
    </div>
    <div class="wave-separator"></div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section" id="how-it-works">
    <h2 class="section-title">How It Works</h2>
    <p class="section-subtitle">A simple step process to get started</p>
    <div class="how-it-works-steps">
        <div class="step">
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            <h3>Register to our Events</h3>
            <p>Sign up for upcoming campus events easily.</p>
        </div>
        <div class="step">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <h3>Be Reminded</h3>
            <p>Get timely reminders so you never miss out.</p>
        </div>
        <div class="step">
            <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
            <h3>Join us at the Venue</h3>
            <p>Check in at the event location and participate.</p>
        </div>
        <!-- <div class="curvy-dashed-line"></div> -->
    </div>
</section>

<!-- Smart Classroom Attendance Section -->
<section class="attendance-section" id="attendance">
    <h2 class="section-title">Smart Classroom Attendance</h2>
    <p class="section-subtitle">Automated, contactless, and secure attendance tracking with facial recognition.</p>
    <div class="attendance-content">
        <div class="attendance-img">
            <img src="/images/class.JPEG" alt="Smart Attendance" class="attendance-photo">
            <div class="facial-recognition-overlay"></div>
        </div>
        <div class="attendance-features">
            <ul class="features-list">
                <li class="feature-row">
                    <span class="feature-icon-btn"><i class="fas fa-check"></i></span>
                    <div class="feature-text">
                        <span class="feature-heading">Accurate Attendance Tracking</span>
                        <span class="feature-desc">Reduces errors and ensures every student is counted correctly.</span>
                    </div>
                </li>
                <li class="feature-row">
                    <span class="feature-icon-btn"><i class="fas fa-clock"></i></span>
                    <div class="feature-text">
                        <span class="feature-heading">Time-Saving Automation</span>
                        <span class="feature-desc">Minimizes roll call time with automated check-ins.</span>
                    </div>
                </li>
                <li class="feature-row">
                    <span class="feature-icon-btn"><i class="fas fa-shield-alt"></i></span>
                    <div class="feature-text">
                        <span class="feature-heading">Contactless & Secure</span>
                        <span class="feature-desc">Promotes hygiene and security with touch-free technology.</span>
                    </div>
                </li>
                <li class="feature-row">
                    <span class="feature-icon-btn"><i class="fas fa-download"></i></span>
                    <div class="feature-text">
                        <span class="feature-heading">Real-Time Monitoring</span>
                        <span class="feature-desc">Instant updates for admins and faculty, anytime.</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</section>

<!-- Campus Events Section -->
<section class="campus-events-section" id="events">
    <h2 class="section-title">Campus Events</h2>
    <div class="events-cards">
        <!-- Event Card 1 -->
        <div class="event-card">
            <div class="event-image-area">
                <img src="/images/event1.jpg" alt="DJ Night" class="event-img">
               
                <div class="event-actions">
                    <!-- <button class="event-action-btn"><i class="fas fa-download"></i></button> -->
                    <button class="event-action-btn"><i class="fas fa-share-alt"></i></button>
                </div>
            </div>
            <div class="event-info-area">
                <div class="event-date">
                    <span class="event-month">SEP</span>
                    <span class="event-day">18</span>
                </div>
                <div class="event-details">
                    <div class="event-title">Indonesia - Korea Conference</div>
                    <div class="event-location">Soehanna, Daerah Khusus Ibukota Yogyakarta, Indonesia</div>
                </div>
            </div>
        </div>
        <!-- Event Card 2 -->
        <div class="event-card">
            <div class="event-image-area">
                <img src="/images/event2.jpg" alt="Party" class="event-img">
               
                <div class="event-actions">
                 
                    <button class="event-action-btn"><i class="fas fa-share-alt"></i></button>
                </div>
            </div>
            <div class="event-info-area">
                <div class="event-date">
                    <span class="event-month">SEP</span>
                    <span class="event-day">17</span>
                </div>
                <div class="event-details">
                    <div class="event-title">Dream World Wide in Jakarta</div>
                    <div class="event-location">Jakarta Convention Center, Indonesia</div>
                </div>
            </div>
        </div>
        <!-- Event Card 3 -->
        <div class="event-card">
            <div class="event-image-area">
                <img src="/images/event3.jpg" alt="Sparklers" class="event-img">
              
                <div class="event-actions">
                   
                    <button class="event-action-btn"><i class="fas fa-share-alt"></i></button>
                </div>
            </div>
            <div class="event-info-area">
                <div class="event-date">
                    <span class="event-month">SEP</span>
                    <span class="event-day">16</span>
                </div>
                <div class="event-details">
                    <div class="event-title">Campus Sparkler Night</div>
                    <div class="event-location">PSU-UCC Main Hall, Philippines</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section" id="testimonials">
    <h2 class="section-title">Testimonials From Recent PSU-UCC Events on Attendify</h2>
    <div class="testimonials-cards">
        <div class="testimonial-card">
            <div class="testimonial-info">
                <div class="testimonial-profile">
                    <img src="/images/profile1.jpg" alt="Jane D." class="testimonial-pic">
                </div>
                <span class="testimonial-name">Jane D.</span>
            </div>
            <p class="testimonial-text">“Attendify made the Prom Gala so much fun and easy to join!”</p>
            <div class="testimonial-info-details">
                <span class="testimonial-location">PSU-UCC</span>
                <span class="testimonial-event">PSU Prom Gala 2024</span>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="testimonial-info">
                <div class="testimonial-profile">
                    <img src="/images/profile2.jpg" alt="Mark S." class="testimonial-pic">
                </div>
                <span class="testimonial-name">Mark S.</span>
            </div>
            <p class="testimonial-text">“I met new friends and never missed an event thanks to reminders.”</p>
            <div class="testimonial-info-details">
                <span class="testimonial-location">PSU-UCC</span>
                <span class="testimonial-event">PSU Prom Gala 2024</span>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="testimonial-info">
                <div class="testimonial-profile">
                    <img src="/images/profile3.jpg" alt="Alyssa T." class="testimonial-pic">
                </div>
                <span class="testimonial-name">Alyssa T.</span>
            </div>
            <p class="testimonial-text">“The smart attendance system is so convenient and secure!”</p>
            <div class="testimonial-info-details">
                <span class="testimonial-location">PSU-UCC</span>
                <span class="testimonial-event">PSU Prom Gala 2024</span>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="attendify-footer">
    <div class="footer-main">
        <div class="footer-left">
            <div class="logo">Attendify</div>
            <p class="vision">Our vision is to provide convenience and help revolutionize attendance tracking.</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-center">
            <h4>About</h4>
            <ul>
                <li><a href="#how-it-works">How it works</a></li>
                <li><a href="#featured">Featured</a></li>
                <li><a href="#partnership">Partnership</a></li>
                <li><a href="#business">Business Relation</a></li>
            </ul>
        </div>
        <div class="footer-right">
            <h4>Community</h4>
            <ul>
                <li><a href="#events">Events</a></li>
                <!-- <li><a href="#blog">Blog</a></li>
                <li><a href="#podcast">Podcast</a></li> -->
                <li><a href="#invite">Invite a friend</a></li>
            </ul>
            <div class="footer-socials">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <!-- <a href="#"><i class="fab fa-discord"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a> -->
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <span>©2025 Attendify. All rights reserved.</span>
        <span class="footer-links"><a href="#privacy">Privacy & Policy</a> | <a href="#terms">Terms & Condition</a></span>
    </div>
</footer>

<!-- Custom Styles -->
<link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/attendify.css">
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection
