@extends('events.dispatcher')

@section('events.content')
<div class="max-w-[1200px] mx-auto px-4 pt-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-[1.8rem] font-black text-brandDark tracking-wide">Drafts</h1>
    <div class="flex items-center gap-2">
      <a href="{{ route('events.manage') }}" class="inline-flex items-center rounded-full px-4 py-2.5 bg-white text-brand border border-blue-200 font-bold shadow hover:shadow-md transition">Published</a>
      <a href="{{ route('events.create') }}" class="inline-flex items-center rounded-full px-5 py-2.5 text-white font-bold bg-brand shadow hover:shadow-lg transition">+ Create Event</a>
    </div>
  </div>

  <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto]">
    <div class="flex items-center gap-2">
      <input id="searchInput" type="text" placeholder="Search drafts by title..." aria-label="Search drafts" class="flex-1 rounded-full bg-[#eef3ff] border border-[#0033cc44] px-4 py-2.5 text-base outline-none focus:border-brand focus:ring-4 ring-brand/20 transition" />
      <button id="searchBtn" type="button" class="rounded-full bg-white border border-[#0033cc44] px-4 py-2.5 font-bold text-brand shadow-sm hover:border-brand hover:shadow-md transition">Search</button>
    </div>
  </div>

  <section id="cardsWrap" class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"></section>
  <div id="emptyState" class="hidden mt-6">
    <div class="rounded-2xl border border-dashed border-blue-300 bg-white p-10 text-center shadow-sm">
      <p class="text-brandDark font-bold text-lg">No drafts found.</p>
      <p class="text-brand opacity-80 mt-1">Create a new event to get started.</p>
    </div>
  </div>
</div>

<script>
const LS_DRAFTS='event_drafts'; const $=(s,r=document)=>r.querySelector(s); const $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
function cardHtml(id,d){ const img=d.pubmat||d.hero?.coverDataUrl||'/images/branding/attendify-brand.png'; const title=d.title||d.info?.title||'Untitled'; return `<article class="group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-card p-4 transition hover:-translate-y-[2px] hover:shadow-cardHover">
  <div class="w-full rounded-[10px] overflow-hidden mb-3"><img src="${img}" class="w-full h-40 object-cover" alt=""></div>
  <div class="w-full text-center mb-2">
    <h3 class="font-extrabold text-brandDark text-[1.02rem] mb-1">${title}</h3>
    <div class="text-brand opacity-90 text-[0.95rem]">Draft</div>
  </div>
  <div class="w-full flex justify-center gap-2">
    <a href="/events/design/${id}?open=1" class="rounded-full bg-brand text-white px-4 py-2 text-sm font-semibold">Resume</a>
    <button data-del="${id}" class="rounded-full border border-red-200 text-red-600 px-3 py-2 text-sm">Delete</button>
  </div></article>`; }
function render(){ const wrap=$('#cardsWrap'); wrap.innerHTML=''; const drafts=JSON.parse(localStorage.getItem(LS_DRAFTS)||'{}'); const keys=Object.keys(drafts); let shown=0; keys.forEach(id=>{ const d=drafts[id]; const el=document.createElement('div'); el.innerHTML=cardHtml(id,d); const card=el.firstElementChild; card.dataset.title=(d.title||'').toLowerCase(); wrap.appendChild(card); shown++; }); $('#emptyState').classList.toggle('hidden', shown!==0); }
function filter(){ const q=$('#searchInput').value.trim().toLowerCase(); let shown=0; $$('#cardsWrap .card').forEach(c=>{ const ok=!q||c.dataset.title.includes(q); c.classList.toggle('hidden',!ok); if(ok)shown++; }); $('#emptyState').classList.toggle('hidden', shown!==0); }
document.addEventListener('DOMContentLoaded',()=>{ render(); filter(); $('#searchBtn').addEventListener('click',filter); $('#searchInput').addEventListener('input',()=>{clearTimeout(window.__t); window.__t=setTimeout(filter,120);}); document.addEventListener('click',e=>{ const del=e.target.closest('[data-del]'); if(del){ const id=del.dataset.del; const drafts=JSON.parse(localStorage.getItem(LS_DRAFTS)||'{}'); delete drafts[id]; localStorage.setItem(LS_DRAFTS, JSON.stringify(drafts)); render(); filter(); } }); });
</script>
@endsection
