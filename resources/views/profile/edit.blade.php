@php
    $voitNotifs = $user->voitNotifications();
    $initiales = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($m) => mb_strtoupper(mb_substr($m, 0, 1)))->join('');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">
            {{ __('Profil') }}
        </h2>
    </x-slot>

    <style>
        /* Page Profil : menu latéral et une carte par section (CSS dans la vue : pas de build front en production). */
        .pf { max-width: 1040px; margin: 0 auto; padding: 1.25rem 1rem 3rem; }
        .pf-bandeau { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; background: #fff; border: 1px solid #e6e7e9; border-radius: 14px; padding: 16px 18px; box-shadow: 0 1px 2px rgba(24, 36, 51, .05); }
        .pf-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--tblr-primary); color: var(--tblr-primary-fg, #fff); display: inline-flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex: none; }
        .pf-nom { margin: 0; font-size: 17px; font-weight: 700; color: #182433; }
        .pf-mail { margin: 2px 0 0; color: #667382; font-size: 14px; }
        .pf-roles { margin-left: auto; display: flex; flex-wrap: wrap; gap: 6px; }
        .pf-role { padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: color-mix(in srgb, var(--tblr-primary) 14%, white); color: #182433; }
        .pf-grille { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 20px; margin-top: 18px; align-items: start; }
        .pf-menu { position: sticky; top: 14px; display: flex; flex-direction: column; gap: 2px; }
        .pf-menu a { display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: 9px; color: #667382; text-decoration: none; font-weight: 600; font-size: 13.5px; }
        .pf-menu a:hover { background: color-mix(in srgb, var(--tblr-primary) 9%, white); color: #182433; }
        .pf-menu a.on { background: color-mix(in srgb, var(--tblr-primary) 14%, white); color: #182433; }
        .pf-sections { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .pf-carte { background: #fff; border: 1px solid #e6e7e9; border-radius: 14px; padding: 20px 22px; box-shadow: 0 1px 2px rgba(24, 36, 51, .05); scroll-margin-top: 14px; }
        .pf-carte h3 { margin: 0 0 4px; font-size: 15.5px; font-weight: 700; color: #182433; }
        .pf-sous { margin: 0 0 14px; color: #667382; font-size: 13px; }
        .pf-btn { border: 1px solid transparent; border-radius: 8px; padding: 8px 18px; font-weight: 600; cursor: pointer; background: color-mix(in srgb, var(--tblr-primary) 14%, white); color: var(--tblr-primary); transition: background .15s, color .15s; }
        .pf-btn:hover { background: var(--tblr-primary); color: var(--tblr-primary-fg, #fff); }
        .pf-btn:disabled { opacity: .6; cursor: default; }
        /* Boutons d'envoi des formulaires existants (Informations, Mot de passe) : même style que .pf-btn. */
        .pf-carte form button[type="submit"] { background: color-mix(in srgb, var(--tblr-primary) 14%, white); color: var(--tblr-primary); border: 1px solid transparent; border-radius: 8px; padding: 8px 18px; font-size: 14px; font-weight: 600; letter-spacing: 0; text-transform: none; box-shadow: none; transition: background .15s, color .15s; }
        .pf-carte form button[type="submit"]:hover { background: var(--tblr-primary); color: var(--tblr-primary-fg, #fff); }

        /* Palette */
        .pf-pal-titre { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin: 14px 0 8px; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #9aa0ac; }
        .pf-puces { display: grid; grid-template-columns: repeat(auto-fill, minmax(86px, 1fr)); gap: 10px; }
        .pf-puce { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 8px 4px; border: 1px solid transparent; border-radius: 10px; background: none; cursor: pointer; }
        .pf-puce:hover { background: #f6f8fb; }
        .pf-puce[aria-pressed="true"] { border-color: #182433; background: #f6f8fb; }
        .pf-puce i { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .12); }
        .pf-puce i svg { opacity: 0; width: 18px; height: 18px; }
        .pf-puce[aria-pressed="true"] i svg { opacity: 1; }
        .pf-puce span { font-size: 12px; color: #667382; }
        .pf-libre { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .pf-libre label { margin: 0; font-size: 12.5px; font-weight: 600; color: #667382; }
        /* Sélecteur de couleur libre : un rond, comme les pastilles. */
        .pf-rond { width: 38px; height: 38px; padding: 0; border: 0; border-radius: 50%; overflow: hidden; background: none; cursor: pointer; -webkit-appearance: none; appearance: none; box-shadow: 0 0 0 1px rgba(0, 0, 0, .15); }
        .pf-rond::-webkit-color-swatch-wrapper { padding: 0; }
        .pf-rond::-webkit-color-swatch { border: 0; border-radius: 50%; }
        .pf-rond::-moz-color-swatch { border: 0; border-radius: 50%; }
        .pf-apercu { margin-top: 16px; border: 1px dashed #dde3ec; border-radius: 12px; padding: 14px; display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px; }
        .pf-apercu .lib { width: 100%; font-size: 12px; color: #9aa0ac; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .pf-ap-btn { border: 0; border-radius: 8px; padding: 8px 16px; font-weight: 600; background: var(--tblr-primary); color: var(--tblr-primary-fg, #fff); }
        .pf-ap-btn2 { border: 1px solid var(--tblr-primary); border-radius: 8px; padding: 7px 15px; font-weight: 600; background: transparent; color: var(--tblr-primary); }
        .pf-ap-badge { padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; background: color-mix(in srgb, var(--tblr-primary) 14%, white); color: var(--tblr-primary); }
        .pf-ratio { margin-left: auto; font-size: 12.5px; color: #667382; }
        .pf-ratio b { color: #182433; }
        .pf-etat { margin-left: 10px; font-size: 13px; color: #667382; }

        @media (max-width: 760px) {
            .pf-grille { grid-template-columns: 1fr; }
            .pf-menu { position: static; flex-direction: row; flex-wrap: wrap; }
            .pf-roles { margin-left: 0; }
        }
    </style>

    <div class="pf">
        {{-- Bandeau d'identité --}}
        <div class="pf-bandeau">
            <span class="pf-avatar" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <p class="pf-nom">{{ $user->name }}</p>
                <p class="pf-mail">{{ $user->email }}</p>
            </div>
            <div class="pf-roles">
                @if($user->is_admin)<span class="pf-role">Administrateur</span>@endif
                @if($user->is_equipe)<span class="pf-role">Équipe</span>@endif
                @if($user->is_civique)<span class="pf-role">Service civique</span>@endif
            </div>
        </div>

        <div class="pf-grille">
            <nav class="pf-menu" id="pfMenu" aria-label="Sections du profil">
                <a href="#pf-infos" class="on">Informations</a>
                <a href="#pf-apparence">Apparence</a>
                <a href="#pf-motdepasse">Mot de passe</a>
                @if($voitNotifs)<a href="#pf-notifications">Notifications</a>@endif
            </nav>

            <div class="pf-sections">
                <section class="pf-carte" id="pf-infos">
                    @include('profile.partials.update-profile-information-form')
                </section>

                <section class="pf-carte" id="pf-apparence">
                    @include('profile.partials.apparence')
                </section>

                <section class="pf-carte" id="pf-motdepasse">
                    @include('profile.partials.update-password-form')
                </section>

                @if($voitNotifs)
                    <section class="pf-carte" id="pf-notifications">
                        @include('profile.partials.notification-preferences')
                    </section>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Menu : la section visible est surlignée.
        (function () {
            const liens = [...document.querySelectorAll('#pfMenu a')];
            const sections = liens.map((a) => document.querySelector(a.getAttribute('href')));
            liens.forEach((a, i) => a.addEventListener('click', (e) => {
                e.preventDefault();
                sections[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }));

            if (!('IntersectionObserver' in window)) return;
            const obs = new IntersectionObserver((entrees) => {
                entrees.forEach((en) => {
                    if (!en.isIntersecting) return;
                    const i = sections.indexOf(en.target);
                    liens.forEach((a, k) => a.classList.toggle('on', k === i));
                });
            }, { rootMargin: '-20% 0px -65% 0px' });
            sections.forEach((s) => s && obs.observe(s));
        })();
    </script>
</x-app-layout>
