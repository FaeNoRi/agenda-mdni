{{--
    Menu flottant (dock) dans la marge de droite, sur grand écran : « + » contextuel, cloche des notifications,
    filtres du tableau de bord. Il ne fait que déclencher des éléments déjà présents dans la page :
      - [data-dock-plus data-dock-label="Ajouter un …"] : le bouton d'ajout de la page (proxy du clic) ;
      - [data-dock-filtres] : le bouton qui ouvre le tiroir des filtres ;
      - la cloche ouvre #offcanvasNotifications.
    Quand le dock est affiché, ces boutons d'origine sont masqués ; sous 992 px de large (téléphone, tablette
    portrait) le dock n'existe pas et la page garde ses boutons habituels. Le dock se range comme un tiroir
    (choix mémorisé) ; par défaut il est rangé sous 1400 px, où la marge est trop étroite.
--}}
@php
    $dockCloche = auth()->check() && auth()->user()->voitNotifications();
    $dockNb = $dockCloche ? auth()->user()->notificationsApp()->nonVues()->count() : 0;
@endphp

<style>
    .dock { position: fixed; top: 50%; right: 10px; transform: translateY(-50%); z-index: 1030; display: none; flex-direction: column; align-items: center; gap: 14px; transition: opacity .15s; }
    body.dock-ok:not(.dock-masque) .dock { display: flex; }
    .dock-item { display: flex; flex-direction: column; align-items: center; gap: 5px; width: 78px; padding: 0; border: 0; background: none; color: #495057; text-align: center; cursor: pointer; }
    .dock-item[hidden] { display: none; }
    .dock-rond { position: relative; display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; transition: transform .12s, box-shadow .12s; }
    .dock-rond--plein { background: var(--tblr-primary); color: #fff; box-shadow: 0 6px 16px color-mix(in srgb, var(--tblr-primary) 40%, transparent); }
    .dock-rond--blanc { background: #fff; color: var(--tblr-primary); box-shadow: 0 4px 14px rgba(24, 36, 51, .2); }
    .dock-item:hover .dock-rond { transform: translateY(-2px); }
    .dock-item:focus-visible { outline: 2px solid var(--tblr-primary); outline-offset: 4px; border-radius: 12px; }
    .dock-cap { font-size: 11px; font-weight: 600; line-height: 1.15; }
    .dock-ranger { display: flex; align-items: center; justify-content: center; width: 30px; height: 22px; border: 0; border-radius: 11px; background: rgba(24, 36, 51, .08); color: #667382; padding: 0; }
    .dock-ranger:hover { background: rgba(24, 36, 51, .16); }
    .dock-onglet { position: fixed; top: 50%; right: 0; transform: translateY(-50%); z-index: 1030; display: none; align-items: center; justify-content: center; width: 20px; height: 68px; padding: 0; border: 0; border-radius: 10px 0 0 10px; background: #fff; color: var(--tblr-primary); box-shadow: -2px 2px 10px rgba(24, 36, 51, .18); }
    body.dock-ok.dock-masque .dock-onglet { display: flex; }
    body.modal-open .dock, body:has(.offcanvas.show) .dock,
    body.modal-open .dock-onglet, body:has(.offcanvas.show) .dock-onglet { opacity: 0; pointer-events: none; }

    /* Dock affiché : les boutons d'origine de la page sont remplacés par ceux du dock. */
    body.dock-on [data-dock-plus], body.dock-on [data-dock-filtres], body.dock-on .js-nav-bell { display: none !important; }
</style>

<div class="dock" id="dock" data-dock role="toolbar" aria-label="Actions rapides" aria-orientation="vertical">
    <button type="button" class="dock-item" id="dockPlus" hidden>
        <span class="dock-rond dock-rond--plein">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
        </span>
        <span class="dock-cap" id="dockPlusLibelle">Ajouter</span>
    </button>

    @if($dockCloche)
        <button type="button" class="dock-item" id="dockCloche" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotifications" aria-controls="offcanvasNotifications">
            <span class="dock-rond dock-rond--blanc">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                <span class="js-notif-badge {{ $dockNb ? '' : 'd-none' }}">{{ $dockNb > 9 ? '9+' : $dockNb }}</span>
            </span>
            <span class="dock-cap">Notifications</span>
        </button>
    @endif

    <button type="button" class="dock-item" id="dockFiltres" hidden>
        <span class="dock-rond dock-rond--blanc">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z"/></svg>
        </span>
        <span class="dock-cap">Filtres</span>
    </button>

    <button type="button" class="dock-ranger" id="dockRanger" title="Ranger le menu" aria-label="Ranger le menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6l-6 6"/></svg>
    </button>
</div>

<button type="button" class="dock-onglet" id="dockOnglet" title="Afficher le menu" aria-label="Afficher le menu">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6l6 6"/></svg>
</button>

<script>
(function () {
    const body = document.body;
    const large = window.matchMedia('(min-width: 992px)');
    const bPlus = document.getElementById('dockPlus');
    const bFiltres = document.getElementById('dockFiltres');
    const libelle = document.getElementById('dockPlusLibelle');
    const cloche = document.getElementById('dockCloche');

    const lire = () => { try { return localStorage.getItem('dock-masque'); } catch (e) { return null; } };
    const ecrire = (v) => { try { localStorage.setItem('dock-masque', v); } catch (e) {} };

    function majDock() {
        const plus = document.querySelector('[data-dock-plus]');
        const filtres = document.querySelector('[data-dock-filtres]');

        bPlus.hidden = !plus;
        if (plus) libelle.textContent = plus.dataset.dockLabel || 'Ajouter';
        bFiltres.hidden = !filtres;

        const contenu = !!(plus || filtres || cloche);
        const choix = lire();
        const masque = choix === null ? window.innerWidth < 1400 : choix === '1';

        body.classList.toggle('dock-ok', large.matches && contenu);
        body.classList.toggle('dock-masque', masque);
        body.classList.toggle('dock-on', large.matches && contenu && !masque);
    }

    // Le dock ne fait que relayer le clic vers l'élément déjà présent dans la page.
    bPlus.addEventListener('click', () => document.querySelector('[data-dock-plus]')?.click());
    bFiltres.addEventListener('click', () => document.querySelector('[data-dock-filtres]')?.click());
    document.getElementById('dockRanger').addEventListener('click', () => { ecrire('1'); majDock(); });
    document.getElementById('dockOnglet').addEventListener('click', () => { ecrire('0'); majDock(); });

    large.addEventListener('change', majDock);
    window.addEventListener('resize', majDock);
    majDock();
})();
</script>
