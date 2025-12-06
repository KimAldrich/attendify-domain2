@php /** @var bool $showAux */ @endphp

{{-- Main content area for student --}}
<section class="mt-12">
  <h3 class="text-[1.5rem] font-bold text-[#0033cc] border-b-2 border-[#006dff] pb-2 mb-6">
    Your Registered Events
  </h3>

  <!-- 2 columns always on ≥sm, stays 1 col on very small screens -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
<!-- 1. Creative UI Design Basics -->
<article class="group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-[0_6px_20px_rgba(0,31,153,.18)] p-4 transition hover:-translate-y-[2px] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)]"
         data-title="creative ui design basics"
         data-meta="oct 15, 2025 • 3:00 pm"
         data-status="upcoming">

  <div class="card__media w-full h-[200px] md:h-[220px] rounded-[10px] overflow-hidden mb-3">
    <img src="{{ asset('images/event4.jpg') }}" alt="Creative UI Design Basics" class="w-full h-full object-cover" loading="lazy">
  </div>

  <div class="card__body w-full text-center mb-2">
    <h3 class="card__title font-extrabold text-[#001f99] text-[1.02rem] mb-1">Creative UI Design Basics</h3>
    <div class="grid-meta inline-flex items-center justify-center gap-2">
      <p class="card__meta text-[#0033cc] opacity-90 text-[0.95rem] m-0">Oct 15, 2025 • 3:00 PM</p>
      <span class="status-inline inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 bg-blue-50 text-blue-700 ring-blue-200">Upcoming</span>
    </div>
  </div>

  <div class="card__actions w-full flex justify-center gap-2">
    <button class="btn--primary inline-flex items-center rounded-full px-4 py-2.5 text-white font-bold bg-[#006dff] shadow-[0_2px_12px_rgba(0,51,204,.2)] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)] hover:translate-y-[2px] hover:scale-[1.03] transition">
      View Event
    </button>
    <button type="button" class="btn-share inline-flex items-center justify-center rounded-full border border-[#0033cc33] bg-white text-[#0033cc] hover:border-[#006dff] hover:text-[#006dff] shadow-sm w-10 h-10 transition" aria-label="Share this event">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M7 17L17 7" />
        <path d="M8 7h9v9" />
      </svg>
    </button>
  </div>
</article>

<!-- 2. Interactive Prototyping Masterclass -->
<article class="group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-[0_6px_20px_rgba(0,31,153,.18)] p-4 transition hover:-translate-y-[2px] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)]"
         data-title="interactive prototyping masterclass"
         data-meta="oct 18, 2025 • 1:00 pm"
         data-status="upcoming">

  <div class="card__media w-full h-[200px] md:h-[220px] rounded-[10px] overflow-hidden mb-3">
    <img src="{{ asset('images/event5.jpg') }}" alt="Interactive Prototyping Masterclass" class="w-full h-full object-cover" loading="lazy">
  </div>

  <div class="card__body w-full text-center mb-2">
    <h3 class="card__title font-extrabold text-[#001f99] text-[1.02rem] mb-1">Interactive Prototyping Masterclass</h3>
    <div class="grid-meta inline-flex items-center justify-center gap-2">
      <p class="card__meta text-[#0033cc] opacity-90 text-[0.95rem] m-0">Oct 18, 2025 • 1:00 PM</p>
      <span class="status-inline inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 bg-blue-50 text-blue-700 ring-blue-200">Upcoming</span>
    </div>
  </div>

  <div class="card__actions w-full flex justify-center gap-2">
    <button class="btn--primary inline-flex items-center rounded-full px-4 py-2.5 text-white font-bold bg-[#006dff] shadow-[0_2px_12px_rgba(0,51,204,.2)] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)] hover:translate-y-[2px] hover:scale-[1.03] transition">
      View Event
    </button>
    <button type="button" class="btn-share inline-flex items-center justify-center rounded-full border border-[#0033cc33] bg-white text-[#0033cc] hover:border-[#006dff] hover:text-[#006dff] shadow-sm w-10 h-10 transition" aria-label="Share this event">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M7 17L17 7" />
        <path d="M8 7h9v9" />
      </svg>
    </button>
  </div>
</article>

<!-- 3. Intro to 3D Animation Design -->
<article class="group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-[0_6px_20px_rgba(0,31,153,.18)] p-4 transition hover:-translate-y-[2px] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)]"
         data-title="intro to 3d animation design"
         data-meta="oct 22, 2025 • 4:30 pm"
         data-status="ongoing">

  <div class="card__media w-full h-[200px] md:h-[220px] rounded-[10px] overflow-hidden mb-3">
    <img src="{{ asset('images/event6.jpg') }}" alt="Intro to 3D Animation Design" class="w-full h-full object-cover" loading="lazy">
  </div>

  <div class="card__body w-full text-center mb-2">
    <h3 class="card__title font-extrabold text-[#001f99] text-[1.02rem] mb-1">Intro to 3D Animation Design</h3>
    <div class="grid-meta inline-flex items-center justify-center gap-2">
      <p class="card__meta text-[#0033cc] opacity-90 text-[0.95rem] m-0">Oct 22, 2025 • 4:30 PM</p>
      <span class="status-inline inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 bg-emerald-50 text-emerald-700 ring-emerald-200">Ongoing</span>
    </div>
  </div>

  <div class="card__actions w-full flex justify-center gap-2">
    <button class="btn--primary inline-flex items-center rounded-full px-4 py-2.5 text-white font-bold bg-[#006dff] shadow-[0_2px_12px_rgba(0,51,204,.2)] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)] hover:translate-y-[2px] hover:scale-[1.03] transition">
      View Event
    </button>
    <button type="button" class="btn-share inline-flex items-center justify-center rounded-full border border-[#0033cc33] bg-white text-[#0033cc] hover:border-[#006dff] hover:text-[#006dff] shadow-sm w-10 h-10 transition" aria-label="Share this event">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M7 17L17 7" />
        <path d="M8 7h9v9" />
      </svg>
    </button>
  </div>
</article>

  </div>
</section>
