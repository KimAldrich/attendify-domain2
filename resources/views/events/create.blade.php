@extends('events.dispatcher')
@section('events.content')
<div class="max-w-[900px] mx-auto px-4 py-10">
  <div class="rounded-xl border bg-white p-6 shadow">
    <h1 class="text-2xl font-black text-[#001f99] mb-2">Create Event</h1>
    <p class="text-slate-600">Preparing your event workspace…</p>
  </div>
</div>
<script>
const LS_DRAFTS='event_drafts';
function newToken(){ return (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2)); }
(function(){
  const token = newToken();
  const drafts = JSON.parse(localStorage.getItem(LS_DRAFTS)||'{}');
  // seed minimal empty draft if not exists
  if(!drafts[token]) drafts[token] = {
    title:'',
    templateFamily:null,
    starts_at:'',
    location:'',
    days:[{label:'Day 1', date:'', locations:'', items:[]}],
    reg:{ google_form_url:'' },
    info:{ other:'' },
    faq:[],
    post:{ testimonies:false, gallery:false },
    hero:{},
    pubmat:null
  };
  localStorage.setItem(LS_DRAFTS, JSON.stringify(drafts));
  window.location.href = '/events/design/' + token + '?open=1';
})();
</script>
@endsection
