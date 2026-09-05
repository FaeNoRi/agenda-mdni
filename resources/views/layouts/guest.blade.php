<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Floppy Là') }}</title>

    {{-- Polices --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tabler UI --}}
    <link href="{{ asset('assets/tabler/css/tabler.min.css') }}" rel="stylesheet">

    {{-- DataTables Bootstrap5 (si besoin sur cette page) --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    {{-- Styles additionnels --}}
    <style>
        html[data-theme-initializing] body {
            visibility: hidden;
        }
        .dataTables_filter {
            padding-bottom: .5rem;
        }
    </style>

    @stack('styles')
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
</head>
<body class="font-sans bg-gray-100 antialiased">

    {{-- Contenu principal --}}
    <main class="min-h-screen flex flex-col items-center  sm:pt-0 @if(Route::is('presence')) pt-2 bg-white @else pt-6 bg-gray-100 @endif">
        <div class="w-full px-6 bg-white shadow-md overflow-hidden sm:rounded-lg  @if(Route::is('presence')) @else mt-6 py-4 @endif">
            {{ $slot }}
        </div>
    </main>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- Bootstrap, Tabler JS & Alpine --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/tabler/js/tabler.min.js') }}"></script>
    <script src="{{ asset('assets/tabler/js/tabler-theme.min.js') }}"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- FullCalendar (si besoin plus tard) --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>

    @stack('scripts')
</body>
</html>
