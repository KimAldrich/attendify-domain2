<div class="grid gap-5 items-stretch md:[grid-template-columns:1.65fr_1fr]">

  {{-- LEFT: TUI calendar --}}
<article class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-4
                h-[72vh] md:h-[78vh] flex flex-col min-h-0">
    <div class="rounded-lg bg-[#001f99] text-white px-3 py-2 mb-3
                flex items-center gap-2 flex-wrap">
    <h3 class="font-semibold mr-auto">My Class Schedule</h3>

    <div class="flex items-center cal-toolbar gap-2">
        <button id="calToday" class="btn-tab h-8 px-3 rounded-md border text-sm" data-view="day">Today</button>
        <button id="calThisWeek" class="btn-tab h-8 px-3 rounded-md border text-sm" data-view="week">Weekly</button>
    </div>
    </div>

    {{-- optional info banner --}}
    <div id="class-indicator"
        class="mt-2 hidden rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2 text-sm text-slate-800"></div>

    {{-- Calendar fills leftover height --}}
    <div id="tui-student-week" class="mt-3 w-full flex-1 min-h-0"></div>
</article>

  {{-- RIGHT: Class Schedule (unchanged visually) --}}
  <section class="bg-white rounded-[14px] shadow-[0_10px_20px_rgba(0,0,0,.06)] overflow-hidden h-[72vh] md:h-[78vh] flex flex-col min-h-0">
    <div class="border-b border-slate-200 px-4 py-3">
      <div class="font-semibold text-base md:text-[1.05rem]">Class Schedule</div>
    </div>

    <div class="p-4 overflow-auto flex-1 min-h-0">
      <div class="hidden md:block">
        <table class="w-full border-separate [border-spacing:0_6px]" aria-label="Class schedule">
          <thead class="bg-[#001f99] text-white">
            <tr>
              <th class="text-left font-medium px-3 py-2.5">Course</th>
              <th class="text-left font-medium px-3 py-2.5">Instructor</th>
              <th class="text-left font-medium px-3 py-2.5">Classroom</th>
              <th class="text-left font-medium px-3 py-2.5">Status</th>
            </tr>
          </thead>
          <tbody class="text-[0.92rem]">
            @php
            $rows = [
                ['c'=>'System Administration & Maintenance','i'=>'Paul Andrew Roa','r'=>'TBA','s'=>'upcoming'],
                ['c'=>'System Integration & Architecture','i'=>'Leo Gabriel Villanueva','r'=>'AB1-207','s'=>'upcoming'],
                ['c'=>'Elective 3: Web & Mobile','i'=>'Rhenel Bernisca','r'=>'AB1-204','s'=>'upcoming'],
                ['c'=>'Capstone Project 2','i'=>'Frederick Patacsil','r'=>'AB1-202','s'=>'upcoming'],
                ['c'=>'Info Assurance & Security 2','i'=>'Kathleen De Guzman','r'=>'TBA','s'=>'upcoming'],
                ['c'=>'Elective 4: Web & Mobile 2','i'=>'Mark Denver Adora','r'=>'AB1-205','s'=>'upcoming'],
                ['c'=>'Operating System Application','i'=>'','r'=>'TBA','s'=>'upcoming'],
            ];
            @endphp
            @foreach ($rows as $row)
              <tr class="bg-slate-50 odd:bg-slate-100">
                <td class="px-3 py-2.5 border border-slate-200 rounded-l-lg">{{ $row['c'] }}</td>
                <td class="px-3 py-2.5 border border-slate-200">{{ $row['i'] }}</td>
                <td class="px-3 py-2.5 border border-slate-200">{{ $row['r'] }}</td>
                <td class="px-3 py-2.5 border border-slate-200 rounded-r-lg font-bold">
                  <span class="inline-block px-2 py-1 rounded text-[11px]
                    @if($row['s']==='present') text-white bg-[#006dff]
                    @else text-white bg-slate-400
                    @endif">
                    {{ ucfirst($row['s']) }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="space-y-3 md:hidden">
        @foreach ($rows as $row)
          <div class="rounded-xl border border-slate-200 p-3">
            <div class="font-semibold text-sm">{{ $row['c'] }}</div>
            <div class="mt-1 text-xs text-slate-500">Instructor: <span class="font-medium text-slate-700">{{ $row['i'] }}</span></div>
            <div class="text-xs text-slate-500">Room: <span class="font-medium text-slate-700">{{ $row['r'] }}</span></div>
            <div class="mt-2">
              <span class="text-[11px] font-bold px-2 py-1 rounded
                @if($row['s']==='present') text-white bg-[#006dff]
                @else text-white bg-slate-400
                @endif">
                {{ ucfirst($row['s']) }}
              </span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
<div id="event-popover" class="hidden fixed z-[2002] max-w-xs rounded-xl border border-slate-200 bg-white p-3 shadow-lg"></div>

</div>

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
@push('scripts')
<script>
  // assumes Toast UI Calendar assets are already loaded globally
  (function(){
    const el = document.getElementById('tui-sched');
    if(!el) return;

    const Calendar = window.tui?.Calendar || window.toastui?.Calendar;
    const cal = new Calendar('#tui-sched', {
      defaultView: 'week',
      usageStatistics: false,
      isReadOnly: true,
      week: {
        startDayOfWeek: 1,   // Monday
        hourStart: 7,
        hourEnd: 17,
        taskView: false,
        eventView: ['time'],
        workweek: true,
      },
      theme: {
        common: { gridSelection: { backgroundColor: 'transparent' } }
      },
      calendars: [
        { id:'present',  name:'Present',  backgroundColor:'#006dff', borderColor:'#006dff' },
        { id:'absent',   name:'Absent',   backgroundColor:'#64748b', borderColor:'#64748b' },
        { id:'upcoming', name:'Upcoming', backgroundColor:'#cbd5e1', borderColor:'#cbd5e1', color:'#0f172a' },
      ],
    });

    // Match your labels: week of 2025-09-29 (Mon … Fri)
    const base = new Date('2025-09-29T00:00:00'); // Monday
    const d = (offset, h1, m1, h2, m2) => {
      const s = new Date(base); s.setDate(base.getDate()+offset); s.setHours(h1, m1, 0, 0);
      const e = new Date(base); e.setDate(base.getDate()+offset); e.setHours(h2, m2, 0, 0);
      return { start:s, end:e };
    };

    const events = [
      { ...d(0, 7, 0, 10, 0), title:'Data Structures & Algorithms (Lab)', calendarId:'present' },
      { ...d(1, 9, 0, 11, 0), title:'Discrete Mathematics',              calendarId:'absent'  },
      { ...d(1,13, 0, 15, 0), title:'Operating Systems',                 calendarId:'present' },
      { ...d(2,10, 0, 12, 0), title:'Computer Networks',                 calendarId:'present' },
      { ...d(2,15, 0, 17, 0), title:'Web Systems & Technologies',        calendarId:'present' },
      { ...d(3,10, 0, 13, 0), title:'Database Systems (Lab)',            calendarId:'present' },
      { ...d(4,14, 0, 17, 0), title:'Mobile App Development',            calendarId:'upcoming'},
    ].map((e, i) => ({ id:String(i+1), category:'time', ...e }));

    cal.createEvents(events);

    // keep view in that same week
    cal.setDate(new Date('2025-10-01'));

    // Toolbar
    const setActive = (btn) => {
      document.querySelectorAll('.cal-toolbar button').forEach(b=>b.classList.remove('is-active'));
      btn.classList.add('is-active');
    };
    const btnToday = document.getElementById('cal-today');
    const btnWeek  = document.getElementById('cal-week');
    const btnDay   = document.getElementById('cal-day');

    btnToday?.addEventListener('click', () => { cal.today(); setActive(btnWeek); cal.changeView('week'); });
    btnWeek?.addEventListener('click',  () => { cal.changeView('week'); setActive(btnWeek); });
    btnDay?.addEventListener('click',   () => { cal.changeView('day');  setActive(btnDay);  });
    setActive(btnWeek);

    // Make sure calendar always fills the left column height
    const ro = new ResizeObserver(() => cal.render());
    ro.observe(el);
  })();
</script>
@endpush
