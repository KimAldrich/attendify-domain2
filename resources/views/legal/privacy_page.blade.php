@extends('layouts.guest')

@section('content')
<style>
  .tac-page { max-width: 900px; margin: 2rem auto; background:#fff; padding:2rem 2.5rem; border-radius:12px; box-shadow:0 2px 16px rgba(0,0,0,0.07); }
  .tac-page h1 { font-size: 2rem; margin-top: 1.5rem; }
  .tac-page h2 { font-size: 1.25rem; margin-top: 1.25rem; }
  .tac-page h3 { font-size: 1.05rem; margin-top: 1rem; }
  .tac-page p, .tac-page li { color:#444; }
  .tac-page hr { margin: 1.5rem 0; }
</style>

<div class="tac-page">
  @include('legal.privacy_body')
</div>
@endsection
