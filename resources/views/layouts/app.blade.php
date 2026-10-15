@php
$theme = \App\Support\ThemeColors::resolve(auth()->check() ? auth()->user()->theme : null);
$themeColor = $theme['nom'];
$modeSombre = auth()->check() && auth()->user()->modeSombre();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if($modeSombre) data-bs-theme="dark" @endif data-bs-theme-primary="{{ $themeColor }}" data-primary-fg="{{ $theme['sombre'] ? 'dark' : 'light' }}"
      style="--tblr-primary: {{ $theme['hex'] }}; --tblr-primary-rgb: {{ $theme['rgb'] }}; --tblr-primary-fg: {{ $theme['fg'] }}; --tblr-primary-darken: {{ $theme['darken'] }}; --tblr-primary-lt: {{ $theme['lt'] }}; --tblr-primary-lt-rgb: {{ $theme['lt_rgb'] }};">

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

    {{-- Couleur du thème : déclarée en !important (spécificité renforcée) pour qu'aucun script ni attribut
         posé après le chargement (thème Tabler, etc.) ne puisse la remplacer. Le profil la met à jour en direct. --}}
    <style id="theme-vars">
        :root:root:root {
            --tblr-primary: {{ $theme['hex'] }} !important;
            --tblr-primary-rgb: {{ $theme['rgb'] }} !important;
            --tblr-primary-fg: {{ $theme['fg'] }} !important;
            --tblr-primary-darken: {{ $theme['darken'] }} !important;
            --tblr-primary-lt: {{ $theme['lt'] }} !important;
            --tblr-primary-lt-rgb: {{ $theme['lt_rgb'] }} !important;
        }
    </style>

    {{-- Couleurs de l'interface en variables (clair par défaut ; sombre via data-bs-theme="dark" sur <html>) --}}
    @include('layouts._variables-ui')

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

        /* La couleur du thème est posée sur <html> : aucun élément en dessous ne doit la redéfinir
           (Tabler le fait pour tout élément portant data-bs-theme). Chaque élément reprend celle de son parent. */
        html *, html *::before, html *::after {
            --tblr-primary: inherit !important;
            --tblr-primary-rgb: inherit !important;
            --tblr-primary-fg: inherit !important;
            --tblr-primary-darken: inherit !important;
            --tblr-primary-lt: inherit !important;
            --tblr-primary-lt-rgb: inherit !important;
        }

        /* Teinte claire (pastel, couleur libre) : le texte de la barre de navigation devient sombre. */
        nav.bg-primary, nav.bg-primary a, nav.bg-primary .text-white { color: var(--tblr-primary-fg) !important; }
        nav.bg-primary .border-white { border-color: var(--tblr-primary-fg) !important; }
        nav.bg-primary .bg-white, nav.bg-primary .bg-white * { color: #182433 !important; }
        /* Menus déroulants : texte de la surface (sombre en mode clair, clair en mode sombre), quelle que soit la couleur du thème. */
        nav.bg-primary .dropdown-menu, nav.bg-primary .dropdown-menu a, nav.bg-primary .dropdown-menu .dropdown-item { color: var(--c-text) !important; }
        html[data-primary-fg="dark"] nav img[alt="Logo"] { filter: brightness(0); }

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
    {{-- tabler-theme.min.js n'est plus chargé : il réécrivait data-bs-theme depuis le stockage du navigateur.
         Le mode (clair / sombre) est celui du profil, posé côté serveur. --}}
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

    {{-- Menu flottant : « + » contextuel, notifications, filtres --}}
    <x-dock />

    {{-- Tiroir de notifications (ouvert par la cloche de la barre de navigation) --}}
    @if(auth()->check() && auth()->user()->voitNotifications())
        @include('notifications._tiroir')
    @endif

    {{-- Tiroirs (filtres, notifications) : un clic en dehors les referme, sans voile sombre.
         Bootstrap est présent deux fois (bundle + Tabler) : un voile serait créé en double et resterait affiché,
         d'où data-bs-backdrop="false" sur les tiroirs et cette fermeture par clic extérieur. --}}
    <script>
        document.addEventListener('click', function (e) {
            const chemin = e.composedPath();   // calculé à l'envoi : reste valable si le clic a re-rendu le tiroir

            if (chemin.some(function (n) {
                return n.nodeType === 1 && (n.matches('[data-bs-toggle="offcanvas"], .offcanvas, .modal, .dropdown-menu, .flatpickr-calendar, .swal2-container, .toastify, [data-dock], [data-dock-plus], [data-dock-filtres]'));
            })) return;

            document.querySelectorAll('.offcanvas.show').forEach(function (tiroir) {
                const fermer = tiroir.querySelector('[data-bs-dismiss="offcanvas"]');
                if (fermer) fermer.click();   // même chemin que la croix
            });
        });
    </script>

    {{-- Retour visuel : voile « Application du filtre… » + barre de chargement en haut de page + surbrillance des éléments
         ciblés par une notification. Sans lui, un filtrage quasi instantané passe inaperçu quand on est déjà sur la page. --}}
    <style>
        /* Barre fine en haut de l'écran */
        #chargementHaut { position: fixed; top: 0; left: 0; right: 0; z-index: 2001; height: 4px; overflow: hidden; pointer-events: none;
                          background: color-mix(in srgb, var(--tblr-primary) 20%, transparent); }
        #chargementHaut::after { content: ""; position: absolute; top: 0; bottom: 0; left: -40%; width: 40%; border-radius: 3px; background: var(--tblr-primary);
                                 animation: chargement-barre .9s ease-in-out infinite; }
        @keyframes chargement-barre { from { left: -40%; } to { left: 100%; } }

        /* Voile avec pastille « chargement » : bien visible, sans bloquer les clics */
        #chargementVoile { position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center; pointer-events: none;
                           background: color-mix(in srgb, var(--c-surface) 58%, transparent); animation: voile-entree .15s ease-out both; }
        #chargementVoile.voile-sortie { animation: voile-sortie .35s ease-in both; }
        .voile-carte { display: flex; align-items: center; gap: 12px; padding: 14px 22px; border-radius: 14px; background: var(--c-surface); color: var(--c-text);
                       font-size: 15px; font-weight: 600; box-shadow: 0 10px 34px rgba(24, 36, 51, .22), 0 0 0 1px var(--c-bord); }
        .voile-rond { flex: none; width: 22px; height: 22px; border-radius: 50%; border: 3px solid color-mix(in srgb, var(--tblr-primary) 25%, transparent);
                      border-top-color: var(--tblr-primary); animation: voile-tour .75s linear infinite; }
        @keyframes voile-entree { from { opacity: 0; } to { opacity: 1; } }
        @keyframes voile-sortie { from { opacity: 1; } to { opacity: 0; } }
        @keyframes voile-tour { to { transform: rotate(360deg); } }

        [data-cible], [data-cible-evenements] { animation: cible-entree .6s ease-out both; }
        @keyframes cible-entree { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }

        .nt-it--ouvre { opacity: .55; }
        .cible-flash { animation: cible-flash 2.2s ease-out 1; position: relative; z-index: 1; }
        @keyframes cible-flash {
            0%   { box-shadow: 0 0 0 0 color-mix(in srgb, var(--tblr-primary) 60%, transparent); }
            25%  { box-shadow: 0 0 0 8px color-mix(in srgb, var(--tblr-primary) 38%, transparent); }
            55%  { box-shadow: 0 0 0 4px color-mix(in srgb, var(--tblr-primary) 28%, transparent); }
            100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--tblr-primary) 0%, transparent); }
        }
        /* « Réduire les animations » : plus de mouvement, mais le retour visuel reste (voile fixe, contour statique). */
        @media (prefers-reduced-motion: reduce) {
            #chargementHaut::after { animation: none; left: 0; width: 100%; }
            .voile-rond { animation: none; border-top-color: var(--tblr-primary); }
            [data-cible], [data-cible-evenements] { animation: none; }
            .cible-flash { animation: none; outline: 3px solid var(--tblr-primary); outline-offset: 2px; }
        }
    </style>
    <script>
        // Retour visuel de chargement. duree (ms) : disparaît tout seul ; message : ajoute le voile avec pastille.
        window.afficherChargement = function (duree, message) {
            var barre = document.getElementById('chargementHaut');
            if (!barre) {
                barre = document.createElement('div');
                barre.id = 'chargementHaut';
                barre.setAttribute('role', 'progressbar');
                barre.setAttribute('aria-label', 'Chargement');
                document.body.appendChild(barre);
            }

            var voile = document.getElementById('chargementVoile');
            if (message && !voile) {
                voile = document.createElement('div');
                voile.id = 'chargementVoile';
                voile.setAttribute('role', 'status');
                voile.innerHTML = '<div class="voile-carte"><span class="voile-rond"></span><span></span></div>';
                voile.querySelector('span:last-child').textContent = message;
                document.body.appendChild(voile);
            }

            if (duree) {
                setTimeout(function () {
                    if (voile) {
                        voile.classList.add('voile-sortie');
                        setTimeout(function () { voile.remove(); }, 380);
                    }
                    barre.remove();
                }, duree);
            }
        };

        // Retour arrière (cache de navigation) : ne jamais laisser le voile affiché.
        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) return;
            ['chargementVoile', 'chargementHaut'].forEach(function (id) { var el = document.getElementById(id); if (el) el.remove(); });
        });

        // Arrivée depuis une notification : voile « Application du filtre… », puis bandeau animé et surbrillance des éléments affichés.
        (function () {
            if (!document.querySelector('[data-cible], [data-cible-evenements]')) return;

            function surbrillance() {
                var cibles = document.querySelectorAll('[data-tache]:not(.d-none), [data-projet]:not(.d-none), #cardsContainer .card');
                cibles.forEach(function (el) {
                    el.classList.add('cible-flash');
                    setTimeout(function () { el.classList.remove('cible-flash'); }, 2300);
                });
            }

            window.afficherChargement(1400, 'Application du filtre…');
            setTimeout(surbrillance, 1100);   // pendant que le voile s'efface : on voit où est l'élément

            // Tableau de bord : les cartes sont rechargées en Ajax juste après l'ouverture de la page.
            var cartes = document.getElementById('cardsContainer');
            if (cartes && window.MutationObserver) {
                var rappel = null;
                var observateur = new MutationObserver(function () {
                    clearTimeout(rappel);
                    rappel = setTimeout(function () { surbrillance(); observateur.disconnect(); }, 80);
                });
                observateur.observe(cartes, { childList: true });
                setTimeout(function () { observateur.disconnect(); }, 4500);
            }
        })();
    </script>

    {{-- Scripts additionnels --}}
    @stack('scripts')
</body>
</html>
