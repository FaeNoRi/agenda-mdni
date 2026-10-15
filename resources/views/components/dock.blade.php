{{--
    Menu flottant (dock) dans la marge de droite, sur grand écran : « + » contextuel, cloche des notifications,
    filtres du tableau de bord. Il ne fait que déclencher des éléments déjà présents dans la page :
      - [data-dock-plus data-dock-label="Ajouter un …"] : le bouton d'ajout de la page (proxy du clic) ;
      - [data-dock-filtres] : le bouton qui ouvre le tiroir des filtres ;
      - la cloche ouvre #offcanvasNotifications.
    Sur grand écran ces boutons d'origine sont masqués, que le dock soit affiché ou rangé. Le dock se range comme un tiroir
    (choix mémorisé) ; par défaut il est rangé sous 1400 px, où la marge est trop étroite.

    Sous 992 px (téléphone, tablette portrait) le même menu devient un « speed dial » : un bouton rond en bas à droite
    qui déploie les mêmes actions vers le haut. Les boutons d'origine (ajout, filtres, cloche de la barre) y sont
    masqués aussi, pour que mobile et PC se comportent de la même façon.
--}}
@php
    $dockCloche = auth()->check() && auth()->user()->voitNotifications();
    $dockNb = $dockCloche ? auth()->user()->notificationsApp()->nonVues()->count() : 0;
@endphp

<style>
    .dock { position: fixed; top: 50%; right: 10px; transform: translateY(-50%); z-index: 1030; display: none; flex-direction: column; align-items: center; gap: 14px; padding: 16px 6px 10px; border-radius: 44px; background: var(--c-surface); box-shadow: 0 6px 24px rgba(24, 36, 51, .18); transition: opacity .15s; }
    body.dock-ok:not(.dock-masque) .dock { display: flex; }
    .dock-item { display: flex; flex-direction: column; align-items: center; gap: 5px; width: 72px; padding: 0; border: 0; background: none; color: var(--c-muted); text-align: center; cursor: pointer; }
    .dock-item[hidden] { display: none; }
    .dock-rond { position: relative; display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; transition: transform .12s, box-shadow .12s; }
    .dock-rond--plein { background: var(--tblr-primary); color: #fff; box-shadow: 0 6px 16px color-mix(in srgb, var(--tblr-primary) 40%, transparent); }
    .dock-rond--blanc { background: color-mix(in srgb, var(--tblr-primary) 11%, var(--c-surface)); color: var(--tblr-primary); }
    .dock-item:hover .dock-rond--blanc { background: color-mix(in srgb, var(--tblr-primary) 20%, var(--c-surface)); }
    .dock-item:hover .dock-rond { transform: translateY(-2px); }
    .dock-item:focus-visible { outline: 2px solid var(--tblr-primary); outline-offset: 4px; border-radius: 12px; }
    .dock-cap { font-size: 11px; font-weight: 600; line-height: 1.15; }
    .dock-ranger { display: flex; align-items: center; justify-content: center; width: 34px; height: 22px; border: 0; border-radius: 11px; background: var(--c-surface-3); color: var(--c-muted); padding: 0; }
    .dock-ranger:hover { background: var(--c-fill); }
    .dock-onglet { position: fixed; top: 50%; right: 0; transform: translateY(-50%); z-index: 1030; display: none; align-items: center; justify-content: center; width: 20px; height: 68px; padding: 0; border: 0; border-radius: 10px 0 0 10px; background: var(--c-surface); color: var(--tblr-primary); box-shadow: -2px 2px 10px rgba(24, 36, 51, .18); }
    body.dock-ok.dock-masque .dock-onglet { display: flex; }
    body.modal-open .dock, body:has(.offcanvas.show) .dock,
    body.modal-open .dock-onglet, body:has(.offcanvas.show) .dock-onglet { opacity: 0; pointer-events: none; }

    /* Grand écran : les boutons d'origine de la page sont remplacés par ceux du dock, même rangé (languette). */
    body.dock-ok [data-dock-plus], body.dock-ok [data-dock-filtres], body.dock-ok .js-nav-bell { display: none !important; }

    /* ---- Speed dial (téléphone, tablette portrait) ---- */
    .sd { position: fixed; right: 16px; bottom: calc(16px + env(safe-area-inset-bottom, 0px)); z-index: 1030; display: none; flex-direction: column-reverse; align-items: flex-end; gap: 12px; transition: opacity .15s; }
    body.speed-ok .sd { display: flex; }
    body.speed-ok [data-dock-plus], body.speed-ok [data-dock-filtres], body.speed-ok .js-nav-bell { display: none !important; }
    body.speed-ok main { padding-bottom: calc(84px + env(safe-area-inset-bottom, 0px)); }
    body.modal-open .sd, body:has(.offcanvas.show) .sd { opacity: 0; pointer-events: none; }

    .sd-fab { position: relative; display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; padding: 0; border: 0; border-radius: 50%;
              background: var(--tblr-primary); color: var(--tblr-primary-fg, #fff); box-shadow: 0 8px 22px color-mix(in srgb, var(--tblr-primary) 45%, transparent); cursor: pointer; transition: transform .2s; }
    .sd-fab:active { transform: scale(.94); }
    .sd-fab:focus-visible { outline: 3px solid color-mix(in srgb, var(--tblr-primary) 40%, transparent); outline-offset: 3px; }
    .sd-fab .sd-ico-fermer { display: none; }
    .sd.sd-ouvert .sd-fab .sd-ico-ouvrir { display: none; }
    .sd.sd-ouvert .sd-fab .sd-ico-fermer { display: block; }
    .sd.sd-ouvert .sd-fab .js-notif-badge { display: none; }

    .sd-liste { display: flex; flex-direction: column-reverse; align-items: flex-end; gap: 12px; }
    .sd-item { display: flex; align-items: center; gap: 10px; padding: 0; border: 0; background: none; cursor: pointer;
               opacity: 0; transform: translateY(14px) scale(.9); pointer-events: none; transition: opacity .18s, transform .18s; }
    .sd-item[hidden] { display: none; }
    .sd.sd-ouvert .sd-item { opacity: 1; transform: none; pointer-events: auto; }
    .sd.sd-ouvert .sd-item:nth-child(2) { transition-delay: .04s; }
    .sd.sd-ouvert .sd-item:nth-child(3) { transition-delay: .08s; }
    .sd-libelle { padding: 6px 12px; border-radius: 8px; background: var(--c-surface); color: var(--c-text); font-size: 13px; font-weight: 600; line-height: 1.2; box-shadow: 0 4px 14px rgba(24, 36, 51, .18); white-space: nowrap; }
    .sd-rond { position: relative; display: flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 50%; box-shadow: 0 4px 14px rgba(24, 36, 51, .22); }
    .sd-rond--plein { background: var(--tblr-primary); color: var(--tblr-primary-fg, #fff); }
    .sd-rond--clair { background: var(--c-surface); color: var(--tblr-primary); }
    @media (prefers-reduced-motion: reduce) { .sd-item, .sd-fab { transition: none; } }
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

{{-- Speed dial : mêmes actions que le dock, sous 992 px --}}
<div class="sd" id="sd" data-dock role="group" aria-label="Actions rapides">
    <button type="button" class="sd-fab" id="sdFab" aria-expanded="false" aria-controls="sdListe" aria-label="Actions rapides">
        <svg class="sd-ico-ouvrir" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h.01"/><path d="M12 12h.01"/><path d="M19 12h.01"/></svg>
        <svg class="sd-ico-fermer" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
        @if($dockCloche)
            <span class="js-notif-badge {{ $dockNb ? '' : 'd-none' }}">{{ $dockNb > 9 ? '9+' : $dockNb }}</span>
        @endif
    </button>

    <div class="sd-liste" id="sdListe">
        <button type="button" class="sd-item" id="sdPlus" hidden>
            <span class="sd-libelle" id="sdPlusLibelle">Ajouter</span>
            <span class="sd-rond sd-rond--plein">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
            </span>
        </button>

        @if($dockCloche)
            <button type="button" class="sd-item" id="sdCloche" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotifications" aria-controls="offcanvasNotifications">
                <span class="sd-libelle">Notifications</span>
                <span class="sd-rond sd-rond--clair">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                    <span class="js-notif-badge {{ $dockNb ? '' : 'd-none' }}">{{ $dockNb > 9 ? '9+' : $dockNb }}</span>
                </span>
            </button>
        @endif

        <button type="button" class="sd-item" id="sdFiltres" hidden>
            <span class="sd-libelle">Filtres</span>
            <span class="sd-rond sd-rond--clair">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z"/></svg>
            </span>
        </button>
    </div>
</div>

<script>
(function () {
    const body = document.body;
    const large = window.matchMedia('(min-width: 992px)');
    const bPlus = document.getElementById('dockPlus');
    const bFiltres = document.getElementById('dockFiltres');
    const libelle = document.getElementById('dockPlusLibelle');
    const cloche = document.getElementById('dockCloche');
    const sd = document.getElementById('sd');
    const sdFab = document.getElementById('sdFab');
    const sdPlus = document.getElementById('sdPlus');
    const sdPlusLibelle = document.getElementById('sdPlusLibelle');
    const sdFiltres = document.getElementById('sdFiltres');

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

        // Petit écran : le speed dial reprend les mêmes actions.
        body.classList.toggle('speed-ok', !large.matches && contenu);
        sdPlus.hidden = !plus;
        if (plus) sdPlusLibelle.textContent = plus.dataset.dockLabel || 'Ajouter';
        sdFiltres.hidden = !filtres;
        if (large.matches) fermerSd();
    }

    function fermerSd() {
        sd.classList.remove('sd-ouvert');
        sdFab.setAttribute('aria-expanded', 'false');
    }
    function basculerSd() {
        const ouvert = sd.classList.toggle('sd-ouvert');
        sdFab.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    }

    // Le dock ne fait que relayer le clic vers l'élément déjà présent dans la page.
    bPlus.addEventListener('click', () => document.querySelector('[data-dock-plus]')?.click());
    bFiltres.addEventListener('click', () => document.querySelector('[data-dock-filtres]')?.click());
    document.getElementById('dockRanger').addEventListener('click', () => { ecrire('1'); majDock(); });
    document.getElementById('dockOnglet').addEventListener('click', () => { ecrire('0'); majDock(); });

    // Speed dial : un toucher déploie / replie ; une action replie puis relaie le clic vers l'élément de la page.
    sdFab.addEventListener('click', basculerSd);
    sdPlus.addEventListener('click', () => { fermerSd(); document.querySelector('[data-dock-plus]')?.click(); });
    sdFiltres.addEventListener('click', () => { fermerSd(); document.querySelector('[data-dock-filtres]')?.click(); });
    document.getElementById('sdCloche')?.addEventListener('click', fermerSd);
    document.addEventListener('click', (e) => { if (!e.target.closest('#sd')) fermerSd(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fermerSd(); });

    large.addEventListener('change', majDock);
    window.addEventListener('resize', majDock);
    majDock();
})();
</script>
