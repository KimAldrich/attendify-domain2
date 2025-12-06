<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('styles')

  <style>
    body { background: url("{{ asset('images/background-auth.png') }}") center/cover no-repeat fixed !important; }
  </style>
</head>
<body>
  @yield('content')

  {{-- SweetAlert (CDN + auto-firing support from the package) --}}
  @include('sweetalert::alert') {{-- no ["cdn" => ""] so the package injects the CDN --}}

  {{-- Optional helpers for your own session flashes --}}
  @if (session('success'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.Swal) {
          Swal.fire({toast:true, position:'top-end', icon:'success',
            title:@json(session('success')), showConfirmButton:false, timer:5000});
        }
      });
    </script>
  @endif

  @if (session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.Swal) {
          Swal.fire({toast:true, position:'top-end', icon:'error',
            title:@json(session('error')), showConfirmButton:false, timer:6000});
        }
      });
    </script>
  @endif

  @stack('scripts')
</body>
</html>
