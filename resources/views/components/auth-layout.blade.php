{{--
    Page d'authentification (connexion, mot de passe oublié, réinitialisation…) : même langage visuel que le reste
    du site — carte arrondie, bandeau à la couleur du thème avec le logo, champs et boutons Tabler.
    Avant connexion, la couleur du thème est celle par défaut. Variables : $titre, $sousTitre (facultatif).
--}}
@props(['titre' => null, 'sousTitre' => null])

@php
    $theme = \App\Support\ThemeColors::resolve(null);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titre ? $titre.' — ' : '' }}{{ config('app.name', 'Floppy Bord') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('assets/tabler/css/tabler.min.css') }}" rel="stylesheet">

    {{-- Couleur du thème (déclarée comme dans la page principale pour que Tabler ne la redéfinisse pas) --}}
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

    <style>
        html, body { min-height: 100%; }
        body.auth-page {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0; padding: 24px 16px;
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            color: #182433;
            background:
                radial-gradient(900px 500px at 12% -10%, color-mix(in srgb, var(--tblr-primary) 16%, #fff) 0%, transparent 60%),
                radial-gradient(800px 500px at 100% 110%, color-mix(in srgb, var(--tblr-primary) 12%, #fff) 0%, transparent 60%),
                #f3f4f6;
        }
        .auth-colonne { width: min(420px, 100%); }
        .auth-carte { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 12px 40px rgba(24, 36, 51, .12), 0 2px 6px rgba(24, 36, 51, .06); }
        .auth-entete { display: flex; align-items: center; justify-content: center; padding: 6px 20px; background: var(--tblr-primary); }
        .auth-entete img { display: block; height: 116px; width: auto; max-width: 100%; }
        @if($theme['sombre'])
        .auth-entete img { filter: brightness(0); }
        @endif
        .auth-corps { padding: 26px 28px 24px; }
        .auth-titre { margin: 0; font-size: 21px; font-weight: 700; line-height: 1.25; }
        .auth-sous { margin: 6px 0 20px; color: #667382; font-size: 13.5px; line-height: 1.5; }
        .auth-champ { margin-bottom: 16px; }
        .auth-champ label.form-label { margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #182433; }
        .auth-corps .form-control { padding: .65rem .85rem; border-radius: 10px; border-color: #dfe3e8; font-size: 15px; }
        .auth-corps .form-control:focus { border-color: var(--tblr-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--tblr-primary) 20%, transparent); }
        .auth-mdp { position: relative; }
        .auth-mdp .form-control { padding-right: 46px; }
        .auth-oeil { position: absolute; top: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; width: 44px; border: 0; background: none; color: #667382; cursor: pointer; }
        .auth-oeil:hover { color: #182433; }
        .auth-corps .btn-primary { width: 100%; padding: .7rem 1rem; border-radius: 10px; font-size: 15px; font-weight: 700; }
        .auth-ligne { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 18px; font-size: 13.5px; }
        .auth-corps a.auth-lien { color: var(--tblr-primary); font-weight: 600; text-decoration: none; }
        .auth-corps a.auth-lien:hover { text-decoration: underline; }
        .auth-erreur { margin-top: 6px; color: #d63939; font-size: 12.5px; font-weight: 600; }
        .auth-statut { margin-bottom: 16px; padding: 10px 12px; border-radius: 10px; background: #e6f6ea; color: #1e7a3b; font-size: 13.5px; font-weight: 600; }
        .auth-pied { margin-top: 18px; text-align: center; color: #9aa0ac; font-size: 12px; }
        @media (max-width: 420px) { .auth-corps { padding: 22px 20px 20px; } }
    </style>
</head>
<body class="auth-page antialiased">
    <div class="auth-colonne">
        <div class="auth-carte">
            <div class="auth-entete">
                <img src="{{ asset('assets/img/application-logo.svg') }}" alt="Logo MDNI">
            </div>
            <div class="auth-corps">
                @if($titre)
                    <h1 class="auth-titre">{{ $titre }}</h1>
                @endif
                @if($sousTitre)
                    <p class="auth-sous">{{ $sousTitre }}</p>
                @elseif($titre)
                    <div style="height: 18px;"></div>
                @endif

                {{ $slot }}
            </div>
        </div>
        <div class="auth-pied">Floppy Bord · MDNI Calaisis</div>
    </div>

    <script>
        // Afficher / masquer le mot de passe
        document.querySelectorAll('[data-auth-oeil]').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                var champ = document.getElementById(bouton.dataset.authOeil);
                var visible = champ.type === 'text';
                champ.type = visible ? 'password' : 'text';
                bouton.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
                bouton.setAttribute('aria-pressed', visible ? 'false' : 'true');
            });
        });
    </script>
</body>
</html>
