@php use Illuminate\Support\Str; @endphp

<x-app-layout>
<div class="font-sans text-slate-800 bg-[#f5f8ff] px-0 md:px-6 py-4">

  <!-- Hero -->
<section
  class="relative overflow-hidden rounded-2xl shadow-[0_6px_20px_rgba(0,31,153,.18)] mt-6 mx-[22px] min-h-[220px] flex items-center 
         bg-[linear-gradient(135deg,#001f99_0%,#0033cc_60%,#006dff_100%),url('{{ asset('images/branding/attendify-brand.PNG') }}')] 
         bg-center bg-cover bg-no-repeat"
>
  <div class="absolute inset-0 bg-blue-700/40"></div>
  <div class="relative z-10 max-w-[640px] px-[8vw] py-12">
    <h1 class="text-white font-black tracking-wide leading-tight text-3xl md:text-5xl mb-3">
      The right choice of design course
    </h1>
    <p class="text-white/90 text-base md:text-lg">Choose from 250,000 online video courses with a new issue added every month</p>
    <button type="button"
      class="mt-6 inline-flex items-center rounded-full px-5 py-2.5 text-white font-bold shadow-[0_2px_12px_rgba(0,51,204,.2)] bg-[#006dff] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)] hover:translate-y-[2px] hover:scale-[1.03] transition">
      View All
    </button>
  </div>
</section>


  <!-- Controls -->
  <section class="max-w-[1100px] mx-auto px-4 mt-6">
    <div class="flex flex-wrap items-center justify-between gap-5">
      <div class="flex items-center gap-2 flex-1 min-w-[300px]">
        <!-- Search: live keyword filter -->
        <input id="searchInput" type="text" placeholder="Search events by title or date..."
          aria-label="Search events"
          class="flex-1 min-w-0 rounded-full bg-[#eef3ff] border border-[#0033cc44] px-4 py-2.5 text-base outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition" />
        <button id="searchBtn" type="button"
          class="rounded-full bg-white border border-[#0033cc44] px-4 py-2.5 font-bold text-[#0033cc] shadow-sm hover:border-[#006dff] hover:shadow-md transition">
          Search
        </button>
      </div>

      <div class="flex items-center">
        <button id="filterToggleBtn" aria-expanded="false" aria-controls="filterPanelContent"
          class="p-0 bg-transparent">
          <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#f5f8ff] border border-[#0033cc44] shadow-sm text-[#0033cc] font-bold">
            <span class="btn__label">Show Filters</span>
            <span class="btn__chev">▼</span>
          </span>
        </button>
      </div>

      <div class="flex items-center gap-2">
        <label for="viewType" class="font-bold text-[#0033cc]">View</label>
        <select id="viewType"
          class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-4 py-2 font-bold text-[#0033cc] outline-none focus:border-[#0033cc] focus:ring-4 ring-[#0033cc]/20 transition cursor-pointer">
          <option value="grid" selected>Grid</option>
          <option value="list">List</option>
        </select>
      </div>
    </div>

    <!-- Filter panel -->
    <div id="filterPanelContent"
         class="hidden mt-3 bg-white rounded-xl shadow-[0_12px_32px_rgba(0,31,153,.2)] p-3">
      <div class="flex justify-end">
        <a href="#" id="clearFilters" class="text-[#006dff] underline font-bold text-[0.98rem]">Clear filters</a>
      </div>

      <div class="grid gap-3 mt-2 sm:grid-cols-2 md:grid-cols-3">
        <!-- Status -->
        <div class="flex flex-col gap-1.5">
          <div class="font-extrabold text-[#001f99]">By Status</div>
          <select class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-3 py-2 font-bold text-[#0033cc] outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition">
            <option>All</option><option>Upcoming</option><option>Ongoing</option><option>Past Events</option>
          </select>
        </div>

        <!-- Registration -->
        <div class="flex flex-col gap-1.5">
          <div class="font-extrabold text-[#001f99]">My Registration</div>
          <select class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-3 py-2 font-bold text-[#0033cc] outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition">
            <option>All</option><option>Registered Only</option>
          </select>
        </div>

        <!-- Participants -->
        <div class="flex flex-col gap-1.5">
          <div class="font-extrabold text-[#001f99]">Participants</div>
          <select id="participants"
                  class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-3 py-2 font-bold text-[#0033cc] outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition">
            <option value="all">All</option>
            <option value="faculty">Faculty</option>
            <option value="bsme">BS in Mechanical Engineering</option>
            <option value="bsit">BS in Information Technology</option>
            <option value="bsee">BS in Electrical Engineering</option>
            <option value="bsce">BS in Civil Engineering</option>
            <option value="bscoe">BS in Computer Engineering</option>
            <option value="abel">AB in English Language (for ABEL)</option>
            <option value="bsmath">BS in Mathematics</option>
            <option value="bse">BS in Education</option>
            <option value="bsarch">BS in Architecture</option>
          </select>
        </div>

        <!-- Year Level -->
        <div class="flex flex-col gap-1.5">
          <div class="font-extrabold text-[#001f99]">Year Level</div>
          <select id="yearLevel"
                  class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-3 py-2 font-bold text-[#0033cc] outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition">
            <option value="all">All</option>
            <option value="first-year">First Year</option>
            <option value="second-year">Second Year</option>
            <option value="third-year">Third Year</option>
            <option value="fourth-year">Fourth Year</option>
          </select>
        </div>

        <!-- Order by Name -->
        <div class="flex flex-col gap-1.5">
          <div class="font-extrabold text-[#001f99]">Order by Name</div>
          <select class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-3 py-2 font-bold text-[#0033cc] outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition">
            <option>Default</option><option>Ascending</option><option>Descending</option>
          </select>
        </div>

        <!-- Order by Date -->
        <div class="flex flex-col gap-1.5">
          <div class="font-extrabold text-[#001f99]">Order by Date</div>
          <select class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-3 py-2 font-bold text-[#0033cc] outline-none focus:border-[#006dff] focus:ring-4 ring-[#006dff]/20 transition">
            <option>Default</option><option>Ascending</option><option>Descending</option>
          </select>
        </div>
      </div>
    </div>

    <div class="mt-6 mb-2 flex items-center justify-between">
      <h2 class="text-[1.45rem] font-black text-[#001f99] tracking-wide">List of Events</h2>
      <div id="resultsBadge" class="text-sm text-[#0033cc] font-semibold" aria-live="polite"></div>
    </div>

    <!-- List header -->
    <div id="listHead"
         class="hidden grid grid-cols-[56px_1fr_160px_110px_120px_auto] items-center gap-4 px-4 py-2 bg-[#eaf1ff] text-[#001f99] rounded-[14px] font-extrabold shadow-[inset_0_1px_0_rgba(0,0,0,.02)]">
    </div>
  </section>

  <!-- Cards -->
  <section id="cardsWrap" class="cards max-w-[1100px] mx-auto px-4 mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @php
      $cards = [
        ['title' => 'Creative UI Design Basics',         'meta' => 'Oct 15, 2025 • 3:00 PM', 'status' => 'Upcoming', 'photo' => "images/event4.jpg"],
        ['title' => 'Interactive Prototyping Masterclass','meta' => 'Oct 18, 2025 • 1:00 PM', 'status' => 'Upcoming', 'photo' => "images/event5.jpg"],
        ['title' => 'Intro to 3D Animation Design',      'meta' => 'Oct 22, 2025 • 4:30 PM', 'status' => 'Ongoing', 'photo' => "images/event6.jpg"],
        ['title' => 'Indonesia - Korea Conference',                'meta' => 'Oct 25, 2025 • 10:00 AM','status' => 'Upcoming', 'photo' => "images/event1.jpg"],
        ['title' => 'Dream World Wide in Jakarta',          'meta' => 'Oct 28, 2025 • 2:00 PM', 'status' => 'Past', 'photo' => "images/event2.jpg"],
        ['title' => 'Campus Sparkler Night',        'meta' => 'Oct 30, 2025 • 5:00 PM', 'status' => 'Upcoming', 'photo' => "images/event3.jpg"],
      ];
      function statusClasses($s) {
        return match(strtolower($s)) {
          'ongoing'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
          'past'     => 'bg-slate-100 text-slate-700 ring-slate-300',
          default    => 'bg-blue-50 text-blue-700 ring-blue-200',
        };
      }
    @endphp

    @foreach($cards as $c)
    @php $badge = statusClasses($c['status']); @endphp
    <article class="group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-[0_6px_20px_rgba(0,31,153,.18)] p-4 transition hover:-translate-y-[2px] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)]"
             data-title="{{ Str::lower($c['title']) }}"
             data-meta="{{ Str::lower($c['meta']) }}"
             data-status="{{ Str::lower($c['status']) }}">
      <div class="card__media w-full h-[200px] md:h-[220px] rounded-[10px] overflow-hidden mb-3">
        <img src="{{ asset($c['photo']) }}" alt="{{ $c['title'] }}" class="w-full h-full object-cover" loading="lazy">
      </div>

      <div class="card__body w-full text-center mb-2">
        <h3 class="card__title font-extrabold text-[#001f99] text-[1.02rem] mb-1">{{ $c['title'] }}</h3>

        <div class="grid-meta inline-flex items-center justify-center gap-2">
          <p class="card__meta text-[#0033cc] opacity-90 text-[0.95rem] m-0">{{ $c['meta'] }}</p>
          <span class="status-inline inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badge }}">
            {{ $c['status'] }}
          </span>
        </div>
      </div>

      <div class="card__date hidden text-[#0033cc] opacity-90 text-[0.95rem]"></div>
      <div class="card__time hidden text-[#0033cc] opacity-90 text-[0.95rem]"></div>
      <div class="status-col hidden">
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $badge }}">
          {{ $c['status'] }}
        </span>
      </div>

      <div class="card__actions w-full flex justify-center gap-2">
        <button class="btn--primary inline-flex items-center rounded-full px-4 py-2.5 text-white font-bold bg-[#006dff] shadow-[0_2px_12px_rgba(0,51,204,.2)] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)] hover:translate-y-[2px] hover:scale-[1.03] transition">
          View Event
        </button>

        <button
          type="button"
          class="btn-share inline-flex items-center justify-center rounded-full border border-[#0033cc33] bg-white text-[#0033cc] hover:border-[#006dff] hover:text-[#006dff] shadow-sm w-10 h-10 transition"
          aria-label="Share this event"
          data-title="{{ $c['title'] }}"
          data-meta="{{ $c['meta'] }}">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 17L17 7" />
            <path d="M8 7h9v9" />
          </svg>
        </button>
      </div>
    </article>
    @endforeach
  </section>

  <!-- Empty state -->
  <div id="emptyState" class="hidden max-w-[1100px] mx-auto px-4 mt-6">
    <div class="rounded-2xl border border-dashed border-blue-300 bg-white p-10 text-center shadow-sm">
      <p class="text-[#001f99] font-bold text-lg">No events match your search.</p>
      <p class="text-[#0033cc] opacity-80 mt-1">Try a different keyword or clear filters.</p>
    </div>
  </div>

  <!-- Share toast -->
  <div id="shareToast"
       class="fixed bottom-4 right-4 hidden opacity-0 transition rounded-full bg-[#001f99] text-white px-4 py-2 text-sm font-semibold shadow-lg">
    Link copied!
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const viewType     = document.getElementById('viewType');
  const cardsWrap    = document.getElementById('cardsWrap');
  const listHead     = document.getElementById('listHead');
  const searchInput  = document.getElementById('searchInput');
  const searchBtn    = document.getElementById('searchBtn');
  const resultsBadge = document.getElementById('resultsBadge');
  const emptyState   = document.getElementById('emptyState');

  const normalizeMeta = () => {
    document.querySelectorAll('.card__meta').forEach(meta => {
      if (meta.dataset.prepped === '1') return;
      const text  = meta.textContent.trim();
      const [dateTxt, timeTxt] = text.split('•').map(s => (s || '').trim());
      meta.innerHTML = `
        <span class="meta--grid">${text}</span>
        <span class="meta--date hidden">${dateTxt || ''}</span>
        <span class="meta--time hidden">${timeTxt || ''}</span>
      `;
      meta.dataset.prepped = '1';
    });
  };

  const fillListColumns = (card) => {
    const meta = card.querySelector('.card__meta');
    const dateTxt = meta?.querySelector('.meta--date')?.textContent || '';
    const timeTxt = meta?.querySelector('.meta--time')?.textContent || '';
    const dateEl = card.querySelector('.card__date');
    const timeEl = card.querySelector('.card__time');
    if (dateEl) { dateEl.textContent = dateTxt; dateEl.classList.remove('hidden'); }
    if (timeEl) { timeEl.textContent = timeTxt; timeEl.classList.remove('hidden'); }
    card.querySelector('.status-col')?.classList.remove('hidden');
    card.querySelector('.status-inline')?.classList.add('hidden');
  };

  const hideListColumns = (card) => {
    card.querySelector('.card__date')?.classList.add('hidden');
    card.querySelector('.card__time')?.classList.add('hidden');
    card.querySelector('.status-col')?.classList.add('hidden');
    card.querySelector('.status-inline')?.classList.remove('hidden');
  };

  const toListCard = (card) => {
    card.className = 'group card grid grid-cols-[56px_1fr_160px_110px_120px_auto] items-center gap-4 p-3 bg-white rounded-[14px] border border-[#0033cc24] shadow-[0_6px_20px_rgba(0,31,153,.18)] transition';
    card.querySelector('.card__media').className = 'card__media w-14 h-14 min-w-14 rounded-full overflow-hidden m-0 shadow ring-4 ring-white';
    card.querySelector('.card__body').className  = 'card__body m-0 text-left';
    card.querySelector('.grid-meta')?.classList.add('hidden');
    const actions = card.querySelector('.card__actions');
    actions.className = 'card__actions m-0 flex justify-end gap-2';
    fillListColumns(card);
    const meta = card.querySelector('.card__meta');
    meta?.querySelector('.meta--grid')?.classList.add('hidden');
    meta?.querySelector('.meta--date')?.classList.remove('hidden');
    meta?.querySelector('.meta--time')?.classList.remove('hidden');
  };

  const toGridCard = (card) => {
    card.className = 'group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-[0_6px_20px_rgba(0,31,153,.18)] p-4 transition hover:-translate-y-[2px] hover:shadow-[0_12px_40px_rgba(0,31,153,.25),0_2px_16px_rgba(0,109,255,.2)]';
    card.querySelector('.card__media').className = 'card__media w-full rounded-[10px] overflow-hidden mb-3';
    card.querySelector('.card__body').className  = 'card__body w-full text-center mb-2';
    card.querySelector('.grid-meta')?.classList.remove('hidden');
    const actions = card.querySelector('.card__actions');
    actions.className = 'card__actions w-full flex justify-center gap-2';
    hideListColumns(card);
    const meta = card.querySelector('.card__meta');
    meta?.querySelector('.meta--grid')?.classList.remove('hidden');
    meta?.querySelector('.meta--date')?.classList.add('hidden');
    meta?.querySelector('.meta--time')?.classList.add('hidden');
  };

  const setView = (mode) => {
    normalizeMeta();
    const cards = Array.from(document.querySelectorAll('#cardsWrap .card'));
    if (mode === 'list') {
      cardsWrap.className = 'cards max-w-[1100px] mx-auto px-4 mt-3 flex flex-col gap-3';
      cards.forEach(toListCard);
    } else {
      cardsWrap.className = 'cards max-w-[1100px] mx-auto px-4 mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3';
      cards.forEach(toGridCard);
    }
    updateListHead();
  };

  const matchCard = (card, q) => {
    if (!q) return true;
    const t = (card.dataset.title || '').includes(q);
    const m = (card.dataset.meta  || '').includes(q);
    const s = (card.dataset.status || '').includes(q);
    return t || m || s;
  };

  const filterCards = () => {
    const q = searchInput.value.trim().toLowerCase();
    const cards = Array.from(document.querySelectorAll('#cardsWrap .card'));
    let shown = 0;
    cards.forEach(c => {
      const ok = matchCard(c, q);
      c.classList.toggle('hidden', !ok);
      if (ok) shown++;
    });
    resultsBadge.textContent = shown ? `${shown} result${shown>1?'s':''}` : 'No results';
    emptyState.classList.toggle('hidden', shown !== 0);
    cardsWrap.classList.toggle('hidden', shown === 0);
    updateListHead();
  };

  const updateListHead = () => {
    const isList = viewType.value === 'list';
    const anyVisible = document.querySelector('#cardsWrap .card:not(.hidden)') !== null;
    if (isList && anyVisible) listHead.classList.remove('hidden');
    else listHead.classList.add('hidden');
  };

  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
  const debouncedFilter = debounce(filterCards, 120);

  setView(viewType.value);
  filterCards();

  viewType.addEventListener('change', () => setView(viewType.value));
  searchInput.addEventListener('input', debouncedFilter);
  searchBtn.addEventListener('click', filterCards);

  const toggleBtn = document.getElementById('filterToggleBtn');
  const panel     = document.getElementById('filterPanelContent');
  const label     = toggleBtn.querySelector('.btn__label');
  const chev      = toggleBtn.querySelector('.btn__chev');
  const openFilters = () => { panel.classList.remove('hidden'); toggleBtn.setAttribute('aria-expanded','true'); label.textContent='Hide Filters'; chev.style.transform='rotate(180deg)'; };
  const closeFilters= () => { panel.classList.add('hidden');    toggleBtn.setAttribute('aria-expanded','false');label.textContent='Show Filters'; chev.style.transform='rotate(0deg)'; };
  closeFilters();
  toggleBtn.addEventListener('click', () => panel.classList.contains('hidden') ? openFilters() : closeFilters());
  const clear = document.getElementById('clearFilters');
  if (clear){
    clear.addEventListener('click', (e) => {
      e.preventDefault();
      panel.classList.add('ring-4','ring-[#006dff]/25'); setTimeout(() => panel.classList.remove('ring-4','ring-[#006dff]/25'), 300);
      searchInput.value = '';
      filterCards();
    });
  }

  const toast = document.getElementById('shareToast');
  let toastTimer;

  const showToast = (msg = 'Link copied!') => {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.remove('hidden');
    requestAnimationFrame(() => {
      toast.classList.remove('opacity-0');
      toast.classList.add('opacity-100');
    });
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('opacity-100');
      toast.classList.add('opacity-0');
      setTimeout(() => toast.classList.add('hidden'), 200);
    }, 1400);
  };

  const slugify = (s) =>
    s.toString().toLowerCase()
      .replace(/\s+/g, '-').replace(/[^\w\-]+/g, '')
      .replace(/\-\-+/g, '-').replace(/^-+/, '').replace(/-+$/, '');

  const buildShareUrl = (title, meta) => {
    const base = window.location.origin;
    const slug = slugify(title || 'event');
    const q = new URLSearchParams({ t: title || '', d: meta || '' }).toString();
    return `${base}/events/${slug}?${q}`;
  };

  const onShare = async (btn) => {
    const title = btn.dataset.title || document.title;
    const meta  = btn.dataset.meta  || '';
    const theUrl = buildShareUrl(title, meta);

    if (navigator.share) {
      try {
        await navigator.share({ title, text: meta, url: theUrl });
        showToast('Shared!');
        return;
      } catch (e) { /* fallback */ }
    }
    try {
      await navigator.clipboard.writeText(theUrl);
      showToast('Link copied!');
    } catch {
      window.prompt('Copy this link:', theUrl);
    }
  };

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-share');
    if (btn) onShare(btn);
  });
});
</script>
@endpush
</x-app-layout>
