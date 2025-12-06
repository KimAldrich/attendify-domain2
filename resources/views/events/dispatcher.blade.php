{{-- resources/views/events/dispatcher.blade.php --}}
@extends('layouts.app')

@push('styles')
  @vite('resources/css/events-temp.css')
@endpush

@section('content')
  {{-- Their old <header> (keep, but NO <html>/<head>/<body> here) --}}
  <section class="events-scope min-h-[calc(100dvh-56px)]">
  <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-blue-100">
    <div class="max-w-[1200px] mx-auto px-4 h-14 flex items-center justify-between">
      <div class="font-extrabold text-brandDark">Events Management</div>
      <nav class="text-sm text-slate-600 flex items-center gap-4">
        <a href="{{ route('events.manage') }}" class="hover:text-brand">Published</a>
        <a href="{{ route('events.drafts') }}" class="hover:text-brand">Drafts</a>
        <a href="{{ route('events.create') }}" class="hover:text-brand">Create</a>
      </nav>
    </div>
  </header>

  {{-- Page content area that their views will fill --}}
  <div class="min-h-[calc(100dvh-56px)]">
    @yield('events.content')
  </div>
  </section>

@endsection
