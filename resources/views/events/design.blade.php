@extends('events.dispatcher')

@section('events.content')
<div class="max-w-[1200px] mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-5">
    <h1 class="text-[1.8rem] font-black text-brandDark tracking-wide">Preview & Publish</h1>
    <div class="flex items-center gap-2">
      <a href="{{ route('events.manage') }}" class="text-brand font-semibold hover:underline">Dashboard</a>
      <a href="{{ route('events.drafts') }}" class="text-brand font-semibold hover:underline">Drafts</a>
    </div>
  </div>

  <div class="rounded-2xl bg-white border border-blue-100 shadow p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="text-sm text-slate-600">Title:</div>
        <div id="evtTitle" class="text-sm font-bold text-brandDark">—</div>
        <div class="text-sm text-slate-600">Template:</div>
        <div id="evtFamily" class="text-sm font-bold text-brandDark">—</div>
      </div>

      <div class="flex items-center gap-2">
        <button id="editSections" class="rounded-full bg-white border px-4 py-2 font-semibold">Edit Sections</button>
        <button id="saveDraft" class="rounded-full bg-white border px-4 py-2 font-semibold">Save to Draft</button>
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
          <input id="togglePreview" type="checkbox" class="peer sr-only" checked>
         
        </label>
        <button id="finalizeBtn" class="rounded-full bg-brand text-white px-4 py-2 font-semibold">Publish</button>
      </div>
    </div>

    <div id="previewWrap" class="mt-4">
      <div id="previewPane" class="rounded-xl border border-[#0033cc22] bg-[#f7faff] p-4 overflow-auto min-h-[200px]">
        <div class="text-slate-500">Loading…</div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Drawer -->
<div id="editDrawer" class="fixed inset-0 hidden z-50">
  <div class="absolute inset-0 bg-black/30"></div>
  <aside class="absolute right-0 top-0 h-full w-[560px] bg-white shadow-2xl border-l border-blue-100 p-5 overflow-auto">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-bold text-brandDark">Edit Sections</h3>
      <button id="closeEdit" class="rounded-full border px-3 py-1.5">Close</button>
    </div>

    <!-- Sticky footer with extra Close button -->
    <div class="sticky bottom-0 bg-white pt-2 pb-3">
      <div class="flex items-center justify-between">
        <div id="valErr" class="text-red-600 text-sm"></div>
        <div class="flex items-center gap-2">
          <button id="drawerCloseFooter" class="rounded-full border px-4 py-2 font-semibold">Close</button>
          <button id="goPreview" class="rounded-full bg-brand text-white px-4 py-2 font-semibold">Go to Preview</button>
        </div>
      </div>
    </div>

    <div class="space-y-6">
      <!-- HERO / TEMPLATE -->
      <section>
        <div class="font-semibold mb-2">
          A – Hero Section <span class="text-slate-400 font-normal ml-1">(title required, images optional)</span>
        </div>

        <div class="text-xs text-slate-500 mb-2">
          Template options <span class="text-rose-600">*</span> <span class="text-slate-400">(required)</span>
        </div>

        <!-- Template tiles (mini layout boxes) -->
        <div id="tplTiles" class="grid grid-cols-2 gap-2">
          <!-- Template 1 -->
          <button type="button" data-family="template-1"
                  class="tpl group border rounded-lg p-2 hover:shadow transition aria-selected:ring aria-selected:ring-brand aria-selected:bg-[#f3f8ff]"
                  aria-pressed="false" aria-selected="false">
            <div class="text-[10px] font-semibold text-slate-600 mb-1">Hero • Program • Info • Reg • FAQ</div>
            <div class="grid grid-cols-4 grid-rows-3 gap-1 h-20">
              <div class="col-span-4 row-span-1 rounded bg-gradient-to-r from-[#d5e3ff] to-[#e7efff]"></div> <!-- hero -->
              <div class="col-span-4 row-span-1 rounded bg-[#e9f2ff]"></div>                                   <!-- program -->
              <div class="col-span-2 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- info -->
              <div class="col-span-1 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- reg -->
              <div class="col-span-1 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- faq -->
            </div>
            <div class="mt-1 text-xs font-medium">Template A</div>
          </button>

          <!-- Template 2 -->
          <button type="button" data-family="template-2"
                  class="tpl group border rounded-lg p-2 hover:shadow transition aria-selected:ring aria-selected:ring-brand aria-selected:bg-[#f3f8ff]"
                  aria-pressed="false" aria-selected="false">
            <div class="text-[10px] font-semibold text-slate-600 mb-1">Hero • (Program+FAQ) • Info • Reg</div>
            <div class="grid grid-cols-4 grid-rows-3 gap-1 h-20">
              <div class="col-span-4 row-span-1 rounded bg-gradient-to-r from-[#d5e3ff] to-[#e7efff]"></div>
              <div class="col-span-3 row-span-1 rounded bg-[#e9f2ff]"></div>                                   <!-- program -->
              <div class="col-span-1 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- faq -->
              <div class="col-span-3 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- info -->
              <div class="col-span-1 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- reg -->
            </div>
            <div class="mt-1 text-xs font-medium">Template B</div>
          </button>

          <!-- Template 3 -->
          <button type="button" data-family="template-3"
                  class="tpl group border rounded-lg p-2 hover:shadow transition aria-selected:ring aria-selected:ring-brand aria-selected:bg-[#f3f8ff]"
                  aria-pressed="false" aria-selected="false">
            <div class="text-[10px] font-semibold text-slate-600 mb-1">Split Hero • Program • Info • FAQ • Reg</div>
            <div class="grid grid-cols-4 grid-rows-3 gap-1 h-20">
              <div class="col-span-3 row-span-1 rounded bg-gradient-to-r from-[#d5e3ff] to-[#e7efff]"></div>    <!-- hero -->
              <div class="col-span-1 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- reg -->
              <div class="col-span-2 row-span-2 rounded bg-[#e9f2ff]"></div>                                   <!-- program -->
              <div class="col-span-1 row-span-2 rounded bg-[#eef6ff]"></div>                                   <!-- info -->
              <div class="col-span-1 row-span-2 rounded bg-[#eef6ff]"></div>                                   <!-- faq -->
            </div>
            <div class="mt-1 text-xs font-medium">Template C</div>
          </button>

          <!-- Template 4 (fallback described) -->
          <button type="button" data-family="template-4"
                  class="tpl group border rounded-lg p-2 hover:shadow transition aria-selected:ring aria-selected:ring-brand aria-selected:bg-[#f3f8ff]"
                  aria-pressed="false" aria-selected="false">
            <div class="text-[10px] font-semibold text-slate-600 mb-1">Hero • (Program+FAQ) • (Info+Reg)</div>
            <div class="grid grid-cols-4 grid-rows-3 gap-1 h-20">
              <div class="col-span-4 row-span-1 rounded bg-gradient-to-r from-[#d5e3ff] to-[#e7efff]"></div>
              <div class="col-span-2 row-span-1 rounded bg-[#e9f2ff]"></div>                                   <!-- program -->
              <div class="col-span-2 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- faq -->
              <div class="col-span-2 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- info -->
              <div class="col-span-2 row-span-1 rounded bg-[#eef6ff]"></div>                                   <!-- reg -->
            </div>
            <div class="mt-1 text-xs font-medium">Template D</div>
          </button>
        </div>

        <label class="block text-sm font-semibold mt-3" for="edTitle">
          Title <span class="text-rose-600">*</span> <span class="text-slate-400 font-normal">(required)</span>
        </label>
        <input id="edTitle" aria-required="true" class="w-full rounded-md border px-3 py-2 mb-2">

        <label class="block text-sm font-semibold" for="edHeroFile">
           Hero (image) <span class="text-slate-400 font-normal">(optional)</span>
        </label>
        <input id="edHeroFile" type="file" accept="image/*" class="block w-full text-sm text-slate-600 file:px-3 file:py-2 file:border-0 file:bg-[#eef3ff] file:rounded-md">

        <label class="block text-sm font-semibold mt-2" for="edPubmatFile">
           Pubmat (card image) <span class="text-slate-400 font-normal">(optional)</span>
        </label>
        <input id="edPubmatFile" type="file" accept="image/*" class="block w-full text-sm text-slate-600 file:px-3 file:py-2 file:border-0 file:bg-[#eef3ff] file:rounded-md">
      </section>

      <!-- PROGRAM -->
      <section>
        <div class="font-semibold mb-2">
          B – Program Section <span class="text-slate-400 font-normal ml-1">(date & location required, items optional)</span>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <label class="col-span-1 text-sm" for="edDate">
            Date <span class="text-rose-600">*</span> <span class="text-slate-400 font-normal">(required)</span>
          </label>
          <input id="edDate" type="date" aria-required="true" class="rounded-md border px-2 py-1.5">

          <label class="col-span-1 text-sm" for="edStart">
            Start <span class="text-slate-400 font-normal">(optional)</span>
          </label>
          <input id="edStart" type="time" class="rounded-md border px-2 py-1.5">

          <label class="col-span-1 text-sm" for="edEnd">
            End <span class="text-slate-400 font-normal">(optional)</span>
          </label>
          <input id="edEnd" type="time" class="rounded-md border px-2 py-1.5">

          <label class="col-span-1 text-sm" for="edLocation">
            Location <span class="text-rose-600">*</span> <span class="text-slate-400 font-normal">(required)</span>
          </label>
          <input id="edLocation" aria-required="true" class="rounded-md border px-2 py-1.5">
        </div>

        <div class="mt-2">
          <div class="text-sm font-semibold mb-1">
            Program Items <span class="text-slate-400 font-normal">(optional)</span>
          </div>
          <div id="edItems" class="space-y-2"></div>
          <div class="grid grid-cols-5 gap-2 mt-1">
            <input id="edItName" class="rounded-md border px-2 py-1.5 text-sm" placeholder="Program Name">
            <input id="edItStart" type="time" class="rounded-md border px-2 py-1.5 text-sm">
            <input id="edItEnd" type="time" class="rounded-md border px-2 py-1.5 text-sm">
            <input id="edItNotes" class="rounded-md border px-2 py-1.5 text-sm" placeholder="Notes (optional)">
            <button id="edAddItem" class="rounded-md border px-2 py-1.5 text-sm">Add</button>
          </div>
        </div>
      </section>

      <!-- REGISTRATION -->
      <section>
        <div class="font-semibold mb-2">
          C – Registration Section <span class="text-slate-400 font-normal ml-1">(optional)</span>
        </div>
        <label class="block text-sm font-semibold" for="edRegUrl">
          Google Form URL <span class="text-slate-400 font-normal">(optional)</span>
        </label>
        <input id="edRegUrl" class="w-full rounded-md border px-3 py-2" placeholder="https://forms.google.com/...">
      </section>

      <!-- EVENT INFO -->
      <section>
        <div class="font-semibold mb-2">
          D – Event Info <span class="text-slate-400 font-normal ml-1">(optional)</span>
        </div>
        <label class="block text-sm font-semibold" for="edOther">
          Other details <span class="text-slate-400 font-normal">(optional)</span>
        </label>
        <textarea id="edOther" rows="3" class="w-full rounded-md border p-2 mb-2"></textarea>
      </section>

      <!-- FAQ -->
      <section>
        <div class="font-semibold mb-2">
          E – FAQ <span class="text-slate-400 font-normal ml-1">(optional)</span>
        </div>
        <p class="text-xs text-slate-500 -mt-1 mb-2">
          If you add a question, you must also provide an answer.
        </p>
        <div id="edFaqWrap" class="space-y-2"></div>
        <button id="edAddFaq" class="mt-2 rounded-full bg-brand text-white px-3 py-1.5 text-sm">+ Add Q&A</button>
      </section>
    </div>
  </aside>
</div>

<script>
const SERVER_TOKEN = @json($token ?? null);
const OPEN_DRAWER = new URL(location.href).searchParams.get('open')==='1';
const LS_DRAFTS = 'event_drafts';
const LS_PUBLISHED = 'events_published';
const token = SERVER_TOKEN || (function(){ const p=location.pathname.split('/'); return p[p.length-1]; })();
const $=(s,r=document)=>r.querySelector(s);

function loadDraft(){ try{ const d=JSON.parse(localStorage.getItem(LS_DRAFTS)||'{}'); return d[token]||null; }catch{return null;} }
function saveDraft(obj){ const d=JSON.parse(localStorage.getItem(LS_DRAFTS)||'{}'); d[token]=obj; localStorage.setItem(LS_DRAFTS, JSON.stringify(d)); }
function unshiftPublished(evt){ const list=JSON.parse(localStorage.getItem(LS_PUBLISHED)||'[]').filter(x=>x.id!==evt.id); list.unshift(evt); localStorage.setItem(LS_PUBLISHED, JSON.stringify(list)); }

function computeStatus(iso){
  if(!iso) return 'Upcoming';
  const start=new Date(iso); const now=new Date(); if(start>now) return 'Upcoming';
  const end=new Date(start.getTime()+3*60*60*1000); return end>=now ? 'Ongoing' : 'Past';
}

function heroHtml(P){
  const title = P.title || 'Untitled Event';
  const cover = P.hero?.coverDataUrl || '/img/ex.jpg';
  const dateTxt = P.starts_at ? new Date(P.starts_at).toLocaleString() : 'TBA';
  const loc = P.location ? ' • '+P.location : '';
  return `<section class="relative overflow-hidden rounded-xl min-h-[220px] text-white shadow-card" style="background: linear-gradient(135deg, rgba(0,31,153,.35), rgba(0,109,255,.25)), url('${cover}') center/cover no-repeat;">
    <div class="absolute inset-x-0 bottom-0 p-5">
      <div class="bg-black/25 rounded-lg backdrop-blur-sm p-3 inline-block">
        <h2 class="text-2xl md:text-3xl font-black leading-tight">${title}</h2>
        <p class="opacity-95 text-sm md:text-base">${dateTxt}${loc}</p>
      </div>
    </div>
  </section>`;
}

function regHtml(P){
  if(!P.reg?.google_form_url) return '';
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">Registration</div>
    <a href="${P.reg.google_form_url}" target="_blank" class="text-brand underline break-all">Open Google Form</a>
  </section>`;
}

function infoHtml(P){
  if(!P.info?.other) return '';
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">Event Info</div>
    <div class="text-sm whitespace-pre-wrap">${P.info.other}</div>
  </section>`;
}

function faqHtml(P){
  const f=P.faq||[]; if(!f.length) return '';
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">FAQ</div>
    ${f.map((qa,i)=>`<div class="mb-2"><div class="font-semibold">Q${i+1}: ${qa.q}</div><div class="text-sm">A: ${qa.a}</div></div>`).join('')}
  </section>`;
}

function programHtml(P){
  const d=P.days?.[0]; if(!d) return '<div class="rounded-xl border bg-white p-3 text-slate-500">No program yet.</div>';
  const items=(d.items||[]).map(it=>`<div class="flex items-center justify-between text-sm">
    <div class="font-medium">${it.name||'Untitled'}</div>
    <div>${it.start||'--:--'}–${it.end||'--:--'}</div>
    <div class="text-slate-500">${it.notes||''}</div>
  </div>`).join('');
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">Program</div>
    <div class="text-sm mb-2">${d.date||''} • ${d.locations||''}</div>
    <div class="space-y-1">${items}</div>
  </section>`;
}

function layout(P,fam){
  if(fam==='template-1'){ return `<div class="space-y-4">${heroHtml(P)} ${programHtml(P)} <div class="grid md:grid-cols-3 gap-4">${infoHtml(P)} ${regHtml(P)} ${faqHtml(P)}</div> </div>`; }
  if(fam==='template-2'){ return `<div class="space-y-4">${heroHtml(P)} <div class="grid md:grid-cols-[2fr_1fr] gap-4">${programHtml(P)} ${faqHtml(P)}</div> ${infoHtml(P)} <div class="grid md:grid-cols-[2fr_1fr] gap-4">${regHtml(P)}</div></div>`; }
  if(fam==='template-3'){ return `<div class="space-y-4"><div class="grid grid-cols-4 gap-4"><div class="col-span-3">${heroHtml(P)}</div><div>${regHtml(P)}</div></div><div class="grid grid-cols-3 gap-4"><div>${programHtml(P)}</div><div>${infoHtml(P)}</div><div>${faqHtml(P)}</div></div></div>`; }
  return `<div class="space-y-4">${heroHtml(P)} <div class="grid md:grid-cols-2 gap-4">${programHtml(P)} ${faqHtml(P)}</div><div class="grid md:grid-cols-2 gap-4">${infoHtml(P)} ${regHtml(P)}</div></div>`;
}

document.addEventListener('DOMContentLoaded',()=>{
  const draft=loadDraft();
  if(!draft){ alert('Draft not found. Complete Phase 1 first.'); location.href='{{ route('events.create') }}'; return; }

  $('#evtTitle').textContent = draft.title || 'Untitled Event';
  $('#evtFamily').textContent = draft.templateFamily || '—';

  const render=()=>{ $('#previewPane').innerHTML = layout(draft, draft.templateFamily); };
  render();

  // Toggle preview
  $('#togglePreview').addEventListener('change', e=> $('#previewWrap').classList.toggle('hidden', !e.target.checked));

  // Save draft
  $('#saveDraft').addEventListener('click', ()=>{ saveDraft(draft); alert('Draft saved.'); });

  // Publish
  $('#finalizeBtn').addEventListener('click', ()=>{
    if(!draft.days?.[0]?.date){ alert('Date is required.'); return; }
    if(!draft.location){ alert('Location is required.'); return; }
    const id = token;
    const evt = {
      id,
      title: draft.title || 'Untitled Event',
      template_family: draft.templateFamily,
      meta: draft.starts_at,
      status: (function(){ if(!draft.starts_at) return 'Upcoming'; const s=new Date(draft.starts_at); const now=new Date(); if(s>now) return 'Upcoming'; const end=new Date(s.getTime()+3*60*60*1000); return end>=now?'Ongoing':'Past'; })(),
      starts_at: draft.starts_at,
      image: draft.hero?.coverDataUrl || '/images/branding/attendify-brand.png',
      pubmat: draft.pubmat || null,
      payload: draft,
      published_at: new Date().toISOString()
    };
    const list = JSON.parse(localStorage.getItem('events_published')||'[]').filter(x=>x.id!==evt.id);
    list.unshift(evt);
    localStorage.setItem('events_published', JSON.stringify(list));
    const d = JSON.parse(localStorage.getItem(LS_DRAFTS)||'{}'); delete d[token]; localStorage.setItem(LS_DRAFTS, JSON.stringify(d));
    location.href = "{{ route('events.manage') }}";
  });

  // --- Drawer open/close (including footer Close) ---
  const openEdit = ()=>{ $('#editDrawer').classList.remove('hidden'); fill(); selectCurrentTemplate(); };
  const closeEdit= ()=> $('#editDrawer').classList.add('hidden');
  $('#editSections').addEventListener('click', openEdit);
  $('#closeEdit').addEventListener('click', closeEdit);
  $('#drawerCloseFooter').addEventListener('click', closeEdit);
  $('#editDrawer').addEventListener('click', (e)=>{ if(e.target.id==='editDrawer') closeEdit(); });
  //if(OPEN_DRAWER){ openEdit(); }

  // --- Template tile interactions ---
  function selectTile(btn){
    document.querySelectorAll('#tplTiles .tpl').forEach(b=>{
      b.classList.remove('ring','ring-brand','bg-[#f3f8ff]');
      b.setAttribute('aria-selected','false');
      b.setAttribute('aria-pressed','false');
    });
    btn.classList.add('ring','ring-brand');
    btn.setAttribute('aria-selected','true');
    btn.setAttribute('aria-pressed','true');
  }
  function selectCurrentTemplate(){
    const fam = draft.templateFamily || 'template-1';
    const btn = document.querySelector(`#tplTiles .tpl[data-family="${fam}"]`);
    if(btn) selectTile(btn);
  }
  document.querySelectorAll('#tplTiles .tpl').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      selectTile(btn);
      draft.templateFamily = btn.dataset.family;
      $('#evtFamily').textContent = draft.templateFamily;
      render();
    });
  });

  // Program items UI
  function renderItems(){
    const wrap = document.getElementById('edItems'); wrap.innerHTML='';
    (draft.days?.[0]?.items||[]).forEach((it,idx)=>{
      const row=document.createElement('div');
      row.className='text-xs border rounded-md p-2 flex items-center justify-between';
      row.innerHTML = `<div><span class="font-semibold">${it.name||'Untitled'}</span> • ${it.start||'--:--'}–${it.end||'--:--'} ${it.notes?('• '+it.notes):''}</div>
      <button data-i="${idx}" class="rm text-red-600 text-xs">Remove</button>`;
      row.querySelector('.rm').addEventListener('click',e=>{ const i=+e.target.dataset.i; draft.days[0].items.splice(i,1); renderItems(); render(); });
      wrap.appendChild(row);
    });
  }
  document.getElementById('edAddItem').addEventListener('click',()=>{
    draft.days = draft.days||[{label:'Day 1',date:'',locations:'',items:[]}];
    const name=document.getElementById('edItName').value.trim();
    const start=document.getElementById('edItStart').value;
    const end=document.getElementById('edItEnd').value;
    const notes=document.getElementById('edItNotes').value.trim();
    draft.days[0].items.push({name,start,end,notes});
    document.getElementById('edItName').value='';
    document.getElementById('edItStart').value='';
    document.getElementById('edItEnd').value='';
    document.getElementById('edItNotes').value='';
    renderItems(); render();
  });
  renderItems();

  // Validate & close to preview
  document.getElementById('goPreview').addEventListener('click', ()=>{
    const errs=[];
    if(!draft.title || !draft.title.trim()) errs.push('Title is required.');
    if(!draft.templateFamily) errs.push('Choose a template.');
    const d = draft.days?.[0]?.date; if(!d) errs.push('Date is required.');
    if(!draft.location || !draft.location.trim()) errs.push('Location is required.');
    let badFaq=false; (draft.faq||[]).forEach(qa=>{ if((qa.q||'').trim() && !(qa.a||'').trim()) badFaq=true; });
    if(badFaq) errs.push('Each FAQ question must have an answer.');
    const box=document.getElementById('valErr');
    if(errs.length){ box.textContent = errs.join(' '); return; }
    box.textContent='';
    document.getElementById('editDrawer').classList.add('hidden');
  });

  // Fill drawer inputs from draft
  function fill(){
    $('#edTitle').value = draft.title||'';
    $('#edDate').value = draft.days?.[0]?.date||'';
    $('#edStart').value= (draft.starts_at? new Date(draft.starts_at).toTimeString().slice(0,5):'') || '';
    $('#edEnd').value='';
    $('#edLocation').value = draft.location||'';
    $('#edRegUrl').value = draft.reg?.google_form_url||'';
    $('#edOther').value = draft.info?.other||'';
    const wrap=$('#edFaqWrap'); wrap.innerHTML='';
    (draft.faq||[]).forEach(qa=> addFaqRow(qa.q, qa.a));
  }
  function addFaqRow(q='',a=''){
    const row=document.createElement('div'); row.className='grid grid-cols-5 gap-2 items-center';
    row.innerHTML=`<input class="q col-span-2 rounded-md border px-2 py-1.5" placeholder="Question" value="${q}">
                   <input class="a col-span-2 rounded-md border px-2 py-1.5" placeholder="Answer" value="${a}">
                   <button class="rm rounded-full border border-red-200 text-red-600 px-2 py-1 text-xs">Remove</button>`;
    row.querySelector('.rm').addEventListener('click',()=>row.remove());
    $('#edFaqWrap').appendChild(row);
  }
  $('#edAddFaq').addEventListener('click', ()=> { addFaqRow(); syncFaqFromDrawer(); });

  function syncFaqFromDrawer(){
    const rows = Array.from(document.querySelectorAll('#edFaqWrap > div'));
    draft.faq = rows.map(r=>({ q: r.querySelector('.q').value, a: r.querySelector('.a').value }));
    render();
  }
  document.getElementById('edFaqWrap').addEventListener('input', syncFaqFromDrawer);

  // Persist edits -> live preview
  $('#edTitle').addEventListener('input', e=>{ draft.title=e.target.value; render(); });
  $('#edDate').addEventListener('change', e=>{ draft.days = draft.days||[{label:'Day 1',date:'',locations:draft.location||'',items:[]}]; draft.days[0].date=e.target.value; draft.starts_at = e.target.value + ( $('#edStart').value? ('T'+$('#edStart').value): 'T00:00'); render(); });
  $('#edStart').addEventListener('change', e=>{ const d=(draft.days&&draft.days[0]?.date)||''; if(d) draft.starts_at = d + 'T' + e.target.value; render(); });
  $('#edEnd').addEventListener('change', e=>{ render(); });
  $('#edLocation').addEventListener('input', e=>{ draft.location=e.target.value; if(draft.days?.[0]) draft.days[0].locations=e.target.value; render(); });
  $('#edRegUrl').addEventListener('input', e=>{ draft.reg = draft.reg||{}; draft.reg.google_form_url = e.target.value; render(); });
  $('#edOther').addEventListener('input', e=>{ draft.info = draft.info||{}; draft.info.other = e.target.value; render(); });

  // Image replacements
  function readAsDataURL(f){ return new Promise((res,rej)=>{ const r=new FileReader(); r.onload=()=>res(r.result); r.onerror=rej; r.readAsDataURL(f); }); }
  $('#edHeroFile').addEventListener('change', async (e)=>{ const f=e.target.files?.[0]; if(!f) return; draft.hero=draft.hero||{}; draft.hero.coverDataUrl = await readAsDataURL(f); render(); });
  $('#edPubmatFile').addEventListener('change', async (e)=>{ const f=e.target.files?.[0]; if(!f) return; draft.pubmat = await readAsDataURL(f); render(); });
});
</script>
@endsection
