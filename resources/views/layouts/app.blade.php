<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Attendify' }}</title>

    @vite(['resources/css/app.css'])
    @livewireStyles
    @stack('styles')
    <style>
        [x-cloak]{display:none!important}
        :root{
            --logo-h: 60px;           /* header logo height */
            --logo-h-mobile: 48px;    /* mobile drawer logo height */
            /* sizes */
            --header-h: 64px;           /* fixed header height */
            --nav-collapsed: 60px;      /* compact rail width */
            --nav-expanded: 240px;      /* expanded panel width */
            --icon-size: 20px;
            --item-xpad: 10px;
            --rail-xpad: 0;
            --icon-offset: calc((var(--nav-collapsed) - var(--icon-size)) / 2);

            /* palette (yours) */
            --c-main: #001f99;          /* dark blue */
            --c-blue: #0033cc;          /* blue */
            --c-accent: #006dff;        /* accent blue */
            --c-white: #ffffff;

            /* rail theming */
            --nav-bg: #001f99;              /* dark */
            --nav-bg-2: #0033cc;            /* blue */
            --nav-border: rgba(0,0,0,.25);
            --nav-text: #ffffff;
            --nav-text-dim: rgba(255,255,255,0.75);
            --nav-active: var(--c-white);
            /* subtle whites for rail states */
            --nav-active-bg: rgba(255,255,255,0.10);   /* active item (pale) */
            --nav-hover-bg:  rgba(255,255,255,0.16);   /* hover (a bit brighter) */

            --layer-header: 1100;
            --layer-rail: 1200;
            --layer-dropdown: 1300; /* profile menu, etc. */
        }

        /* Ensure SweetAlert2 is always on top of header/rail/dropdowns */
        .swal2-container { z-index: 2000 !important; }

        /* fixed header */
        /* fixed header — now white */
        .app-header{
            position: fixed; inset: 0 0 auto 0;
            height: var(--header-h);
            background: #ffffff;                    /* ← white header */
            color: var(--c-main);                   /* text/icons use dark blue */
            display: grid; grid-template-columns: 1fr auto;
            align-items: center;
            padding: 0 16px;
            z-index: var(--layer-header);;
            border-bottom: 1px solid rgba(2, 6, 23, 0.06);
            box-shadow: 0 2px 10px rgba(2, 6, 23, 0.06);  /* soft shadow */
        }
        .app-header::after{
            content:"";
            position:absolute; left:0; right:0; bottom:-1px; height:2px;
            background: linear-gradient(90deg, var(--c-main), var(--c-accent));
            opacity:.75;
        }

        .app-header__brand{ display:flex; gap:10px; align-items:center; font-weight:600; color: var(--c-main); }
        .app-header__actions{ display:flex; align-items:center; gap:10px; color: var(--c-main); }


        /* page content never under header; leave space for rail (collapsed only) */
        .app-content{
            padding-top: var(--header-h);
            margin-left: var(--nav-collapsed); /* keep space for compact rail */
            min-height: 100dvh;
            background: #f7f9fc;
        }

        .app-header__brand .app-logo{
        height: var(--logo-h);
        width: auto;              /* keep aspect ratio */
        display: block;
        }
        
        /* retracting rail (fixed; overlays body when expanded) */
        .rl-nav{
        position: fixed; inset: var(--header-h) auto 0 0;
        width: var(--nav-collapsed);
        background: linear-gradient(180deg,var(--nav-bg),var(--nav-bg-2));
        border-right: 1px solid var(--nav-border);
        color: var(--nav-text);
        display:flex; flex-direction:column;
        overflow: hidden auto;
        transition: width 240ms cubic-bezier(.22,1,.36,1),
                    transform 240ms cubic-bezier(.22,1,.36,1);
        will-change: width, transform;
        z-index: var(--layer-rail);
        }
        .rl-nav.expanded{ width: var(--nav-expanded); }

        .rl-title{ font-weight:600; white-space:nowrap; overflow:hidden; max-width:160px; opacity:.95; transition:max-width .24s,opacity .14s; }
        .rl-nav:not(.expanded) .rl-title{ max-width:0; opacity:0; }

        .rl-section{ padding:6px var(--rail-xpad); }
        .rl-list{ list-style:none; margin:0; padding:0; }
        .rl-item>a{
            margin-left: calc(-1 * var(--rail-xpad));
            margin-right: calc(-1 * var(--rail-xpad));
            padding-left: var(--icon-offset);
            padding-right: var(--item-xpad);
            display:flex; align-items:center; gap:10px; height:42px;
            color: var(--nav-text-dim); text-decoration:none;
            transition: background-color .14s, color .14s;
        }
        /* hover: a bit brighter than active */
        .rl-item > a:hover,
        .rl-item > a:focus-visible{
        background: var(--nav-hover-bg);
        color: var(--nav-text);
        outline: none;
        }
        /* active: persistent pale white */
        .rl-item.active > a{
        background: var(--nav-active-bg);
        color: var(--nav-active);
        border: none;
        }
        .rl-item.active > a:hover,
        .rl-item.active > a:focus-visible{
        background: var(--nav-hover-bg);
        }
        .rl-icon{ width:20px; height:20px; display:grid; place-items:center; flex:0 0 20px; }
        .rl-icon .bi{ font-size:20px; line-height:1; }
        .rl-label{ white-space:nowrap; overflow:hidden; max-width:180px; transition:max-width .24s, opacity .14s; }
        .rl-nav:not(.expanded) .rl-label{ max-width:0; opacity:0; pointer-events:none; }
        .rl-footer{ margin-top:auto; padding:6px var(--rail-xpad); border-top:1px solid rgba(255,255,255,.08); }

        /* ── Management divider that adapts to collapsed/expanded ─────────────── */
        .rl-divider{
        display:flex; align-items:center; gap:8px;
        padding:6px 12px; /* same rhythm as items */
        }
        .rl-divider__text{
        font-size:11px; letter-spacing:.08em; text-transform:uppercase;
        color:rgba(255,255,255,.80);
        white-space:nowrap; overflow:hidden;
        max-width:160px; opacity:.9;
        transition:max-width .24s, opacity .18s; /* slide/fade in */
        }
        .rl-divider__rule{
        height:1px; background:rgba(255,255,255,.18);
        flex:1;
        transform-origin:left center;
        transition:transform .24s;
        }

        /* Collapsed: hide label, keep a full-width rule (just a line) */
        .rl-nav:not(.expanded) .rl-divider{ padding:6px var(--rail-xpad); } /* align with icon rail */
        .rl-nav:not(.expanded) .rl-divider__text{ max-width:0; opacity:0; }
        .rl-nav:not(.expanded) .rl-divider__rule{ transform:scaleX(1); }

        /* Expanded: show label at left, rule continues to the right */
        .rl-nav.expanded .rl-divider__text{ max-width:160px; opacity:.9; }
        .rl-nav.expanded .rl-divider__rule{ transform:scaleX(1); }

        /* footer look: subtle divider + link affordances */
        .rl-footer { 
        margin-top: auto; 
        padding: 0; 
        border-top: 1px solid rgba(255,255,255,.12);
        }
        .rl-footer a { text-decoration: none; }
        .rl-footer a:hover { text-decoration: underline; }

        /* compact padding symmetry */
        .rl-nav:not(.expanded) .rl-item>a{ padding-right: var(--icon-offset); }
        .rl-nav:not(.expanded) .rl-footer { display: none; }
        
        @media (max-width: 1024px){
        .rl-nav{ inset: 0 auto 0 0; }
        .app-content{ margin-left: 0; }

        /* ✅ brand styles ONLY on mobile */
        .rl-brand{
            display:flex;
            align-items:center;
            gap:10px;
            height: var(--header-h);      /* match header height */
            padding: 0 12px;
            background: #fff;
            color: var(--c-main);
            border-bottom: 1px solid rgba(2,6,23,0.06);
            box-shadow: 0 1px 6px rgba(2,6,23,0.06);
            position: sticky; 
            top: 0; 
            z-index: 1;
        }
        .rl-brand .rl-title{
            color: var(--c-main);
            opacity: 1;
            max-width: 100%;
        }
        .rl-brand .app-logo-mobile{
            height: var(--logo-h-mobile);
            width: auto;
            display: block;
        }
    }
    </style>
</head>
<body class="antialiased" 
    x-data
    x-on:toast.window="
        const d = $event.detail || {};
        if (window.Swal) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: d.type ?? 'info',
            title: d.message ?? '',
            timer: 4000,
            showConfirmButton: false,
        })
        } else {
        console.log(d.message ?? '');
        }
    "
  >
    {{-- HEADER (fixed) --}}
    @include('partials.header')

    {{-- SIDE NAV (fixed; retracting) --}}
    @include('partials.sidebar')

    @include('sweetalert::alert')

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

    {{-- BODY (under header, beside compact rail) --}}
    <main class="app-content">
        @if(session('firebase_offline'))
            <div class="mx-6 mt-4 text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded px-3 py-2">
            It seems you're offline. Try refreshing the page once you have an internet connection.
            </div>
        @endif
        @if (View::hasSection('content'))
            @yield('content')
        @else
            {{ $slot ?? '' }}
        @endif
    </main>

    @livewireScripts
    @vite(['resources/js/app.js'])
    @stack('scripts')
    <div id="modal-root"></div>
</body>
</html>
