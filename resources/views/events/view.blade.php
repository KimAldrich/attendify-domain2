@extends('events.dispatcher')

@section('events.content')
<div class="max-w-[1100px] mx-auto px-4 py-6">
  <div id="out" class="text-slate-600">Loading…</div>
</div>
<script>
const LS_PUBLISHED='events_published';
const $=(s,r=document)=>r.querySelector(s);
const ID = (()=>{ const p=location.pathname.split('/'); return p[p.length-1]; })();

// --- Sections (titles without A–F prefixes) ---
function regHtml(P){
  const u=P.reg?.google_form_url;
  if(!u) return '';
  const isGF = /forms\.google\.com/.test(u);
  if(isGF){
    return `<section class="rounded-xl border bg-white p-3 shadow-sm">
      <div class="font-bold text-brandDark mb-2">Registration</div>
      <div class="aspect-[4/3] w-full overflow-hidden rounded-lg border">
        <iframe src="${u}" class="w-full h-full" loading="lazy"></iframe>
      </div>
    </section>`;
  }
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">Registration</div>
    <a href="${u}" class="inline-flex items-center gap-2 px-3 py-2 rounded-md border bg-[#f8fbff] hover:bg-white transition">Open registration form</a>
  </section>`;
}

function infoHtml(P){
  return P.info?.other
    ? `<section class="rounded-xl border bg-white p-3 shadow-sm">
         <div class="font-bold text-brandDark mb-2">Event Info</div>
         <div class="text-sm whitespace-pre-wrap">${P.info.other}</div>
       </section>`
    : '';
}

function faqHtml(P){
  const f=P.faq||[];
  if(!f.length) return '';
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">FAQ</div>
    ${f.map((qa,i)=>`<div class="mb-2">
        <div class="font-semibold">Q${i+1}: ${qa.q}</div>
        <div class="text-sm">A: ${qa.a}</div>
      </div>`).join('')}
  </section>`;
}

function programHtml(P){
  const d=P.days?.[0];
  if(!d) return '<div class="rounded-xl border bg-white p-3 text-slate-500">No program yet.</div>';
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">Program</div>
    <div class="text-sm mb-2">${d.date||''} • ${d.locations||''}</div>
    ${(d.items||[]).map(it=>`<div class="text-sm">• ${it.name||'Untitled'} (${it.start||'--:--'}–${it.end||'--:--'}) ${it.notes?'- '+it.notes:''}</div>`).join('')}
  </section>`;
}

function postHtml(P){
  if(!P.post?.testimonies && !P.post?.gallery) return '';
  return `<section class="rounded-xl border bg-white p-3 shadow-sm">
    <div class="font-bold text-brandDark mb-2">Post Event</div>
    ${P.post.testimonies?'<div class="text-sm mb-1">• Testimonies enabled</div>':''}
    ${P.post.gallery?'<div class="text-sm">• Gallery enabled</div>':''}
  </section>`;
}

// --- Hero (single definition) ---
function heroHtml(evt){
  const P=evt.payload||{};
  const title=evt.title||'Untitled';
  const cover=P.hero?.coverDataUrl || evt.image || '/images/branding/attendify-brand.png';
  const dt=evt.starts_at ? new Date(evt.starts_at).toLocaleString() : 'TBA';
  const loc=P.location ? ' • '+P.location : (evt.payload?.location ? ' • '+evt.payload.location : '');
  return `<section class="relative overflow-hidden rounded-xl min-h-[220px] text-white shadow-card"
            style="background: linear-gradient(135deg, rgba(0,31,153,.35), rgba(0,109,255,.25)), url('${cover}') center/cover no-repeat;">
            <div class="absolute inset-x-0 bottom-0 p-5">
              <div class="rounded-lg p-0 inline-block">
                <h2 class="text-2xl md:text-3xl font-black leading-tight">${title}</h2>
                <p class="opacity-95 text-sm md:text-base">${dt}${loc}</p>
              </div>
            </div>
          </section>`;
}

// --- Layouts ---
function layout(evt){
  const fam = evt.template_family || evt.payload?.templateFamily || 'template-1';
  const P=evt.payload||{};
  if(fam==='template-1'){
    return `<div class="space-y-4">
      ${heroHtml(evt)}
      ${programHtml(P)}
      <div class="grid md:grid-cols-3 gap-4">${infoHtml(P)} ${regHtml(P)} ${faqHtml(P)}</div>
      ${postHtml(P)}
    </div>`;
  }
  if(fam==='template-2'){
    return `<div class="space-y-4">
      ${heroHtml(evt)}
      <div class="grid md:grid-cols-[2fr_1fr] gap-4">${programHtml(P)} ${faqHtml(P)}</div>
      ${infoHtml(P)}
      <div class="grid md:grid-cols-[2fr_1fr] gap-4">${postHtml(P)} ${regHtml(P)}</div>
    </div>`;
  }
  if(fam==='template-3'){
    return `<div class="space-y-4">
      <div class="grid grid-cols-4 gap-4">
        <div class="col-span-3">${heroHtml(evt)}</div>
        <div>${regHtml(P)}</div>
      </div>
      <div class="grid grid-cols-3 gap-4">
        <div>${programHtml(P)}</div>
        <div>${infoHtml(P)}</div>
        <div>${faqHtml(P)}</div>
      </div>
      ${postHtml(P)}
    </div>`;
  }
  // fallback
  return `<div class="space-y-4">
    ${heroHtml(evt)}
    <div class="grid md:grid-cols-2 gap-4">${programHtml(P)} ${faqHtml(P)}</div>
    <div class="grid md:grid-cols-2 gap-4">${infoHtml(P)} ${regHtml(P)}</div>
    ${postHtml(P)}
  </div>`;
}

function sectionHtml(evt){ return layout(evt); }

// --- Boot ---
document.addEventListener('DOMContentLoaded', ()=>{
  const list=JSON.parse(localStorage.getItem(LS_PUBLISHED)||'[]');
  const evt=list.find(x=>x.id===ID);
  if(!evt){ $('#out').textContent='Event not found.'; return; }
  $('#out').innerHTML = sectionHtml(evt);
});
</script>

@endsection
