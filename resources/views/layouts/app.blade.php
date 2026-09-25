@php
$themeColor = auth()->check()
? (auth()->user()->theme ?? 'blue')
: 'blue';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme-primary="{{ $themeColor }}" style="--tblr-primary: {{ $themeColor }};">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Floppy Bord') }}</title>

    {{-- Polices --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tabler UI --}}
    <link href="{{ asset('assets/tabler/css/tabler.min.css') }}" rel="stylesheet">

    {{-- DataTables Bootstrap5 --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    {{-- Styles additionnels --}}
    <style>
        /* Pas d'overflow-y sur <html> : Bootstrap verrouille le scroll via body { overflow: hidden }
           à l'ouverture d'une modale, ce qui ne se propage au viewport (et ne conserve la position
           de la page) que si <html> a un overflow "visible". Le gutter évite le décalage de mise en page. */
        html {
            scrollbar-gutter: stable;
        }

        body {
            padding-right: 0 !important;
        }

        .dataTables_filter {
            padding-bottom: .5rem;
        }

    </style>

    {{-- Placez ici des CSS spécifiques à certaines pages --}}
    @stack('styles')

    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        {{-- Navigation --}}
        @include('layouts.navigation')

        {{-- En-tête de page --}}
        @isset($header)
        <header class="shadow-lg">
            <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        {{-- Contenu principal --}}
        <main>
            {{ $slot }}
        </main>
    </div>

    {{-- jQuery (chargé une seule fois) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- Bootstrap, Tabler JS & Alpine --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/tabler/js/tabler.min.js') }}"></script>
    <script src="{{ asset('assets/tabler/js/tabler-theme.min.js') }}"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script src=" https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js "></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.18/locales-all.global.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4/dist/signature_pad.umd.min.js"></script>

    <script>
    // Durée de session récupérée côté backend (en minutes)
        const sessionLifetime = {{ env('SESSION_LIFETIME', 120) }};

        // Définir une alerte 5 minutes avant expiration (en ms)
        const notifyBefore = 5 * 60 * 1000;

        // Timer d'alerte
        const timeoutDuration = (sessionLifetime * 60 * 1000) - notifyBefore;

        setTimeout(() => {
            // Notification Tabler (si tu veux styliser proprement)
            if (window.Toastify) {
                Toastify({
                    text: "⚠️ Votre session va expirer dans 5 minutes. Pensez à sauvegarder !",
                    duration: 10000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#f59f00", // jaune
                }).showToast();
            } else {
                alert("⚠️ Votre session va expirer dans 5 minutes !");
            }
        }, timeoutDuration);
    </script>

    {{-- Scripts additionnels --}}
    @stack('scripts')
</body>
</html>
