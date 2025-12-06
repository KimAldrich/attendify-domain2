@extends('events.dispatcher')

@section('events.content')
<div class="max-w-[1200px] mx-auto px-4 pt-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-[1.8rem] font-black text-brandDark tracking-wide">List of Events</h1>
    <div class="flex items-center gap-2">
      <a href="{{ route('events.drafts') }}" class="inline-flex items-center rounded-full px-4 py-2.5 bg-white text-brand border border-blue-200 font-bold shadow hover:shadow-md transition">Drafts</a>
      <a href="{{ route('events.create') }}" class="inline-flex items-center rounded-full px-5 py-2.5 text-white font-bold bg-brand shadow hover:shadow-lg transition">+ Create Event</a>
    </div>
  </div>

  <!-- Controls -->
  <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
    <div class="flex items-center gap-2">
      <input id="searchInput" type="text" placeholder="Search events by title or date..." aria-label="Search events" class="flex-1 rounded-full bg-[#eef3ff] border border-[#0033cc44] px-4 py-2.5 text-base outline-none focus:border-brand focus:ring-4 ring-brand/20 transition" />
      <button id="searchBtn" type="button" class="rounded-full bg-white border border-[#0033cc44] px-4 py-2.5 font-bold text-brand shadow-sm hover:border-brand hover:shadow-md transition">Search</button>
    </div>
    <div class="flex items-center gap-2 justify-end">
      <label for="viewType" class="font-bold text-brand">View</label>
      <select id="viewType" class="rounded-full bg-[#eef3ff] border border-[#0033cc44] shadow-sm px-4 py-2 font-bold text-brand outline-none focus:border-brand focus:ring-4 ring-brand/20 transition cursor-pointer">
        <option value="grid" selected>Grid</option>
        <option value="list">List</option>
      </select>
    </div>
  </div>

  <div class="mt-6 mb-2 flex items-center justify-between">
    <h2 class="text-[1.25rem] font-black text-brandDark tracking-wide">Published</h2>
    <div id="resultsBadge" class="text-sm text-brand font-semibold" aria-live="polite"></div>
  </div>

  <div id="listHead" class="hidden grid grid-cols-[56px_1fr_180px_140px_120px_auto] items-center gap-4 px-4 py-2 bg-[#eaf1ff] text-brandDark rounded-[14px] font-extrabold shadow-[inset_0_1px_0_rgba(0,0,0,.02)]">
    <span>Photo</span><span>Title</span><span>Date</span><span>Time</span><span>Status</span><span class="text-right">Action</span>
  </div>

  <section id="cardsWrap" class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"></section>
  <div id="emptyState" class="hidden mt-6">
    <div class="rounded-2xl border border-dashed border-blue-300 bg-white p-10 text-center shadow-sm">
      <p class="text-brandDark font-bold text-lg">No events match your search.</p>
      <p class="text-brand opacity-80 mt-1">Try a different keyword or clear filters.</p>
    </div>
  </div>
</div>

<script>
const LS_PUBLISHED='events_published'; const $=(s,r=document)=>r.querySelector(s); const $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
function statusClasses(s){ s=(s||'').toLowerCase(); if(s==='ongoing')return 'bg-emerald-50 text-emerald-700 ring-emerald-200'; if(s==='past')return 'bg-slate-100 text-slate-700 ring-slate-300'; return 'bg-blue-50 text-blue-700 ring-blue-200'; }
function fmtMeta(starts_at){ if(!starts_at)return{date:'TBA',time:''}; const d=new Date(starts_at); if(isNaN(+d))return{date:'TBA',time:''}; const od={month:'short',day:'numeric',year:'numeric'}; const ot={hour:'numeric',minute:'2-digit'}; return {date:d.toLocaleDateString(undefined,od),time:d.toLocaleTimeString(undefined,ot)}; }
function loadPublishedSorted(){ try{ const list=JSON.parse(localStorage.getItem(LS_PUBLISHED)||'[]'); return list.sort((a,b)=> new Date(b.published_at||0)-new Date(a.published_at||0)); }catch{return [];} }
function cardHtml(e){ const badge=statusClasses(e.status||'Upcoming'); const meta=fmtMeta(e.starts_at); const img=e.pubmat || '/images/branding/attendify-brand.png'; return `<article class="group card flex flex-col items-center bg-white rounded-[14px] border border-[#0033cc24] shadow-card p-4 transition hover:-translate-y-[2px] hover:shadow-cardHover">
  <div class="w-full rounded-[10px] overflow-hidden mb-3"><img src="${img}" class="w-full h-40 object-cover" alt=""></div>
  <div class="w-full text-center mb-2">
    <h3 class="font-extrabold text-brandDark text-[1.02rem] mb-1">${e.title||'Untitled'}</h3>
    <div class="inline-flex items-center justify-center gap-2">
      <p class="text-brand opacity-90 text-[0.95rem] m-0">${meta.date} • ${meta.time}</p>
      <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${badge}">${(e.status||'Upcoming')}</span>
    </div>
  </div>
  <div class="w-full flex justify-center gap-2">
    <a href="/events/view/${e.id}" class="rounded-full bg-brand text-white px-4 py-2 text-sm font-semibold">View</a>
   
    <button data-del="${e.id}" class="rounded-full border border-red-200 text-red-600 px-3 py-2 text-sm">Delete</button>
  </div></article>`; }
function render(){ const wrap=$('#cardsWrap'); wrap.innerHTML=''; const list=loadPublishedSorted(); let shown=0; list.forEach(e=>{ const d=document.createElement('div'); d.innerHTML=cardHtml(e); const el=d.firstElementChild; el.dataset.title=(e.title||'').toLowerCase(); el.dataset.meta=(e.meta||'').toLowerCase(); el.dataset.status=(e.status||'').toLowerCase(); wrap.appendChild(el); shown++; }); $('#resultsBadge').textContent=shown?`${shown} result${shown>1?'s':''}`:'No results'; $('#emptyState').classList.toggle('hidden',shown!==0); }
function filter(){ const q=$('#searchInput').value.trim().toLowerCase(); let shown=0; $$('#cardsWrap .card').forEach(c=>{ const ok=!q||c.dataset.title.includes(q)||c.dataset.meta.includes(q)||c.dataset.status.includes(q); c.classList.toggle('hidden',!ok); if(ok)shown++; }); $('#resultsBadge').textContent=shown?`${shown} result${shown>1?'s':''}`:'No results'; $('#emptyState').classList.toggle('hidden',shown!==0); updateHead(); }
function toList(card){ const img=card.querySelector('img').src; const title=card.querySelector('h3').outerHTML; const meta=card.querySelector('p').textContent.split('•').map(s=>s.trim()); const status=card.querySelector('span.inline-flex'); card.className='group card grid grid-cols-[56px_1fr_180px_140px_120px_auto] items-center gap-4 p-3 bg-white rounded-[14px] border border-[#0033cc24] shadow-card transition'; card.innerHTML=`<div class="w-14 h-14 min-w-14 rounded-full overflow-hidden m-0 shadow ring-4 ring-white"><img src="${img}" class="w-full h-full object-cover"></div>
  <div>${title}</div><div class="text-brand">${meta[0]||''}</div><div class="text-brand">${meta[1]||''}</div><div><span class="${status.className}">${status.textContent}</span></div><div class="text-right">${card.querySelector('.w-full.flex').innerHTML}</div>`; }
function setView(mode){ const wrap=$('#cardsWrap'); if(mode==='list'){ wrap.className='mt-3 flex flex-col gap-3'; $$('#cardsWrap .card').forEach(toList);} else { wrap.className='mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3'; render(); filter(); } updateHead(); }
function updateHead(){ const isList=$('#viewType').value==='list'; const any=document.querySelector('#cardsWrap .card:not(.hidden)')!==null; $('#listHead').classList.toggle('hidden',!(isList&&any)); }
document.addEventListener('DOMContentLoaded',()=>{ render(); filter(); $('#viewType').addEventListener('change',()=>setView($('#viewType').value)); $('#searchBtn').addEventListener('click',filter); $('#searchInput').addEventListener('input',()=>{clearTimeout(window.__t); window.__t=setTimeout(filter,120);}); document.addEventListener('click',e=>{ const del=e.target.closest('[data-del]'); if(del){ if(confirm('Delete this published event?')){ const id=del.dataset.del; const list=JSON.parse(localStorage.getItem(LS_PUBLISHED)||'[]').filter(x=>x.id!==id); localStorage.setItem(LS_PUBLISHED, JSON.stringify(list)); render(); filter(); } } }); });
</script>
@endsection
