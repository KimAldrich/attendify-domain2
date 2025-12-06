<article class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-4">
  <!-- Dark-blue header bar -->
  <div class="rounded-lg bg-[#001f99] text-white px-3 py-2 mb-3
              flex items-center gap-2 flex-wrap">
    <h3 class="font-semibold mr-auto">My Class Schedule</h3>

    <div class="flex items-center cal-toolbar gap-2">
      <button id="calToday"
              class="btn-tab h-8 px-3 rounded-md border text-sm"
              data-view="day">
        Today
      </button>
      <button id="calThisWeek"
              class="btn-tab h-8 px-3 rounded-md border text-sm"
              data-view="week">
        Weekly
      </button>
    </div>
  </div>

  <!-- current/next indicator -->
  <div id="class-indicator"
       class="mt-2 hidden rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2 text-sm text-slate-800"></div>

  <!-- fixed height, scroll inside -->
  <div id="tui-student-week" class="mt-3 w-full" style="height: 480px;"></div>
</article>

{{-- popover target --}}
<div id="event-popover" class="hidden fixed z-[2002] max-w-xs rounded-xl border border-slate-200 bg-white p-3 shadow-lg"></div>

@push('styles')
<style>
  /* ===== Scope everything to this instance ===== */
  #tui-student-week{ -ms-overflow-style:none; scrollbar-width:none; }
  #tui-student-week *::-webkit-scrollbar{ display:none; }

  /* ============== WEEK VIEW HEADER ============== */
  /* header strip container */
  #tui-student-week .toastui-calendar-week-daynames{
    background:#001f99 !important;
    border-bottom:1px solid rgba(255,255,255,.12) !important;
    height:52px;           /* a touch taller prevents collisions */
  }
  /* each header cell → stack date/name vertically */
  #tui-student-week .toastui-calendar-week-dayname{
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding-top:6px; gap:2px;
  }
  /* big date number */
  #tui-student-week .toastui-calendar-week-dayname-date{
    display:block; opacity:1 !important;
    color:#fff !important; font-weight:600; font-size:18px; line-height:1.1;
    white-space:nowrap;
  }
  /* small weekday name */
  #tui-student-week .toastui-calendar-week-dayname-name{
    display:block; opacity:1 !important;
    color:rgba(255,255,255,.85) !important; font-size:12px; line-height:1.1;
    white-space:nowrap;
  }

  /* =============== DAY VIEW HEADER =============== */
  /* single header cell container */
  #tui-student-week .toastui-calendar-dayname{
    background:#001f99 !important;
    border-bottom:1px solid rgba(255,255,255,.12) !important;
    height:52px; display:flex; align-items:center; justify-content:center;
    flex-direction:column; gap:2px;
  }
  #tui-student-week .toastui-calendar-dayname-date{
    display:block; opacity:1 !important;
    color:#fff !important; font-weight:600; font-size:18px; line-height:1.1;
  }
  #tui-student-week .toastui-calendar-dayname-name{
    display:block; opacity:1 !important;
    color:rgba(255,255,255,.85) !important; font-size:12px; line-height:1.1;
  }

  /* ======= Responsive compression (both views) ======= */
  @media (max-width:1024px){
    #tui-student-week .toastui-calendar-week-daynames,
    #tui-student-week .toastui-calendar-dayname{ height:48px; }
    #tui-student-week .toastui-calendar-week-dayname-date,
    #tui-student-week .toastui-calendar-dayname-date{ font-size:16px; }
  }
  @media (max-width:480px){
    #tui-student-week .toastui-calendar-week-daynames,
    #tui-student-week .toastui-calendar-dayname{ height:44px; }
    #tui-student-week .toastui-calendar-week-dayname-date,
    #tui-student-week .toastui-calendar-dayname-date{ font-size:15px; }
    #tui-student-week .toastui-calendar-week-dayname-name,
    #tui-student-week .toastui-calendar-dayname-name{ font-size:11px; }
  }
  @media (max-width:360px){
    /* if extremely narrow, hide weekday names—date stays visible */
    #tui-student-week .toastui-calendar-week-dayname-name,
    #tui-student-week .toastui-calendar-dayname-name{ display:none; }
  }

  /* ===== Hour labels + events ===== */
  #tui-student-week .toastui-calendar-timegrid-time,
  #tui-student-week .toastui-calendar-timegrid-hour{ color:#475569; }
  #tui-student-week .toastui-calendar-event{
    border-radius:10px; font-size:.85rem; cursor:pointer;
  }

  /* ===== Toolbar tabs ===== */
  .btn-tab{ background:#fff; border-color:#e2e8f0; color:#0f172a; }
  .btn-tab:hover{ background:#f8fafc; }
  .btn-tab.is-active{
    background:#eef2ff; border-color:#c7d2fe; color:#1e3a8a;
    box-shadow:inset 0 0 0 1px rgba(30,58,138,.08);
  }

  /* ===== Popover caret ===== */
  #event-popover::before{
    content:""; position:absolute; top:-6px; left:16px;
    border-width:0 6px 6px 6px; border-style:solid;
    border-color:transparent transparent #e5e7eb transparent;
  }
  #event-popover::after{
    content:""; position:absolute; top:-5px; left:16px;
    border-width:0 6px 6px 6px; border-style:solid;
    border-color:transparent transparent #fff transparent;
  }
</style>
@endpush



