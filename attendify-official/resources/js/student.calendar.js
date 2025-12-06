import Calendar from '@toast-ui/calendar';
import '@toast-ui/calendar/dist/toastui-calendar.min.css';

// ========= Config =========
const HOUR_START   = 7;
const HOUR_END     = 17;
const PX_PER_HOUR  = 64;   
const CAL_HEIGHT = 480;

// Subtle blue palette
const EVENT_BG   = '#EAF2FF';
const EVENT_BD   = '#BFD4FF';
const EVENT_TEXT = '#0A2E86';

// ========= Responsive height =========
function setCalendarHeight(el) {
  const isMobile = window.matchMedia('(max-width: 767px)').matches;
  el.style.height = isMobile ? '250px' : '480px';
}

// ========= Date helpers =========
function getMonday(d = new Date()) {
  const date = new Date(d);
  const day = date.getDay();                 // 0 Sun .. 6 Sat
  const diff = (day === 0 ? -6 : 1) - day;   // move to Monday
  date.setDate(date.getDate() + diff);
  date.setHours(0, 0, 0, 0);
  return date;
}
const monday = getMonday();
const dayIdx = { M: 0, T: 1, W: 2, H: 3, F: 4 };

function dt(code, hhmm) {
  const [hh, mm] = hhmm.split(':').map(Number);
  const d = new Date(monday);
  d.setDate(monday.getDate() + (dayIdx[code] ?? 0));
  d.setHours(hh, mm, 0, 0);
  return d;
}

function fmtTime(d) {
  const hh = d.getHours();
  const mm = d.getMinutes().toString().padStart(2, '0');
  const ampm = hh >= 12 ? 'PM' : 'AM';
  const h12 = ((hh + 11) % 12) + 1;
  return `${h12}:${mm} ${ampm}`;
}

function minutesDiff(a, b) {
  return Math.round((b.getTime() - a.getTime()) / 60000);
}

function startOfDay(d) { const x = new Date(d); x.setHours(0,0,0,0); return x; }
function daysBetween(a, b) {
  return Math.round((startOfDay(b) - startOfDay(a)) / 86400000);
}

function humanEta(now, to) {
  const mins = minutesDiff(now, to);
  const dd = daysBetween(now, to);
  if (dd >= 1) return dd === 1 ? 'tomorrow' : `in ${dd} days`;
  if (mins < 60) return `${mins}m`;
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return m ? `${h}h ${m}m` : `${h}h`;
}

function getTeacher(ev) {
  return ev?.raw?.teacher || ev?.description || '';
}

function setActiveTab(view) {
  document.querySelectorAll('.btn-tab').forEach(b => b.classList.remove('is-active'));
  const btn = document.querySelector(`.btn-tab[data-view="${view}"]`);
  if (btn) btn.classList.add('is-active');
}

// ========= Event builder (title = course only) =========
let idSeq = 1;
function cls(course, code, start, end, room, teacher) {
  return {
    id: String(idSeq++),
    title: course,                
    start: dt(code, start),
    end: dt(code, end),
    location: room,
    description: teacher || '',
    raw: { teacher: teacher || '' },

    isReadOnly: true,
    backgroundColor: EVENT_BG,
    borderColor: EVENT_BD,
    color: EVENT_TEXT,
  };
}

const EVENTS = [
  cls('System Administration & Maintenance','M','10:00','12:00','TBA','Paul Andrew Roa'),
  cls('System Administration & Maintenance','W','14:00','17:00','ABI-205','Paul Andrew Roa'),

  cls('System Integration & Architecture','W','08:00','10:00','AB1-207','Leo Gabriel Villanueva'),
  cls('System Integration & Architecture','H','14:00','17:00','AB1-206','Leo Gabriel Villanueva'),

  cls('Elective 3: Web & Mobile','M','12:00','14:00','AB1-204','Rhenel Bernisca'),
  cls('Elective 3: Web & Mobile','F','14:00','17:00','AB1-204','Rhenel Bernisca'),

  cls('Capstone Project 2','F','11:00','14:00','AB1-202','Frederick Patacsil'),

  cls('Info Assurance & Security 2','M','14:00','16:00','TBA','Kathleen De Guzman'),

  cls('Elective 4: Web & Mobile 2','F','08:00','11:00','AB1-205','Mark Denver Adora'),
  cls('Elective 4: Web & Mobile 2','W','11:00','13:00','AB1-206','Mark Denver Adora'),
  cls('Elective 4: Web & Mobile 2','T','11:00','14:00','AB1-206','Mark Denver Adora'),

  cls('Operating System Application','H','08:00','10:00','TBA',''),
  cls('Operating System Application','T','14:00','17:00','AB1-205',''),
];

// ========= Now/Next resolver & UI =========
function findNowOrNext(events, now = new Date()) {
  const ongoing = events.find(e => e.start <= now && now < e.end);
  if (ongoing) return { type: 'now', ev: ongoing };
  const future = events.filter(e => e.start > now).sort((a,b) => a.start - b.start)[0];
  if (future) return { type: 'next', ev: future };
  return null;
}

function renderIndicator() {
  const box = document.getElementById('class-indicator');
  if (!box) return;
  const now = new Date();
  const info = findNowOrNext(EVENTS, now);
  if (!info) { box.classList.add('hidden'); box.innerHTML=''; return; }

  const { ev } = info;
  const start = fmtTime(ev.start);
  const end   = fmtTime(ev.end);
  const room  = ev.location ? ` • ${ev.location}` : '';
  const teacher = getTeacher(ev);
  const prof    = teacher ? ` — ${teacher}` : '';

  let prefix, tail;
  if (info.type === 'now') {
    const left = humanEta(now, ev.end); // e.g., 32m / 1h 20m
    prefix = `<span class="font-semibold text-blue-900">Now:</span>`;
    tail   = `· ends in ${left}`;
  } else {
    const eta = humanEta(now, ev.start); // e.g., 2h 10m / tomorrow / in 2 days
    prefix = `<span class="font-semibold text-blue-900">Next:</span>`;
    tail   = `· starts in ${eta}`;
  }

  box.classList.remove('hidden');
  box.innerHTML = `
    <div class="flex flex-col gap-0.5">
      <div>${prefix} ${ev.title}</div>
      <div class="text-slate-700">${start}–${end}${room}${prof} ${tail}</div>
    </div>
  `;
}

// ========= Popover (click event) =========
function showPopover(ev, clientX, clientY) {
  const pop = document.getElementById('event-popover');
  if (!pop) return;

  const start = fmtTime(ev.start);
  const end   = fmtTime(ev.end);
  const room  = ev.location ? ev.location : 'TBA';
  const prof  = getTeacher(ev) || '—';

  pop.innerHTML = `
    <div class="space-y-1">
      <div class="font-semibold text-slate-900">${ev.title}</div>
      <div class="text-sm text-slate-700">${start} – ${end}</div>
      <div class="text-sm text-slate-700"><span class="font-medium">Room:</span> ${room}</div>
      <div class="text-sm text-slate-700"><span class="font-medium">Instructor:</span> ${prof}</div>
    </div>
  `;

  // position near click, but keep on-screen
  const pad = 10;
  const vw = window.innerWidth, vh = window.innerHeight;
  const rectW = 280; // approx max width
  const rectH = 130; // approx
  let x = clientX + pad, y = clientY + pad;
  if (x + rectW > vw) x = vw - rectW - pad;
  if (y + rectH > vh) y = vh - rectH - pad;

  pop.style.left = `${x}px`;
  pop.style.top  = `${y}px`;
  pop.classList.remove('hidden');

  // close handlers
  const closer = (e) => {
    // close if click outside
    if (!pop.contains(e.target)) {
      pop.classList.add('hidden');
      window.removeEventListener('mousedown', closer);
      window.removeEventListener('keydown', escCloser);
      window.removeEventListener('scroll', hideOnScroll, true);
      window.removeEventListener('resize', hideOnScroll);
    }
  };
  const escCloser = (e) => { if (e.key === 'Escape') closer(e); };
  const hideOnScroll = () => { pop.classList.add('hidden'); };

  window.addEventListener('mousedown', closer);
  window.addEventListener('keydown', escCloser);
  window.addEventListener('scroll', hideOnScroll, true);
  window.addEventListener('resize', hideOnScroll);
}

// ========= Boot =========
function initStudentCalendar() {
  const el = document.querySelector('#tui-student-week');
  if (!el) return;

  setCalendarHeight(el);

  const cal = new Calendar(el, {
    defaultView: 'day',
    usageStatistics: false,
    week: {
      startDayOfWeek: 1,   // Monday
      workweek: true,      // Mon–Fri
      hourStart: HOUR_START,
      hourEnd:   HOUR_END,
      showNowIndicator: true,
      taskView: false,
      eventView: ['time'],
    },
    day: {
      hourStart: HOUR_START,
      hourEnd: HOUR_END,
    },
    template: {
      time(event) {
        return `<span style="color:${EVENT_TEXT}">${event.title}</span>`;
      }
    }
  });

  cal.createEvents(EVENTS);
  setActiveTab('day');

  // ---- Toolbar actions ----
  // Today → switch to DAY view on today's date
  document.getElementById('calToday')?.addEventListener('click', () => {
    cal.today();              
    cal.changeView('day');
    setActiveTab('day');     
    renderIndicator();
  });

  // This Week → switch to WEEK view on current week (starting Monday)
  document.getElementById('calThisWeek')?.addEventListener('click', () => {
    cal.setDate(getMonday(new Date())); // align to this week's Monday
    cal.changeView('week');
    setActiveTab('week');           
    renderIndicator();
  });

  // Click event → popover
  cal.on('clickEvent', (ev) => {
    const e = ev.event;
    const n = ev.nativeEvent;
    const clientX = n?.clientX ?? (n?.touches?.[0]?.clientX ?? 80);
    const clientY = n?.clientY ?? (n?.touches?.[0]?.clientY ?? 80);
    showPopover(e, clientX, clientY);
  });

  // Indicator refresh loop
  renderIndicator();
  setInterval(renderIndicator, 60 * 1000);

  // Responsive height changes
  const resizer = () => setCalendarHeight(el);
  window.addEventListener('resize', resizer);
  window.addEventListener('orientationchange', resizer);
}

document.addEventListener('DOMContentLoaded', initStudentCalendar);