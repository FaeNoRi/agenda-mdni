{{--
    Tiroir de notifications (offcanvas à droite, comme le tiroir des filtres du tableau de bord).
    Ouvert par la cloche de la barre de navigation. Inclus une fois par le layout.
    Les notifications sont chargées en JSON à l'ouverture ; le badge se met à jour toutes les minutes.
--}}
@php
    $categories = \App\Support\NotificationTypes::CATEGORIES;
@endphp

<style>
    #offcanvasNotifications { --nt-line: #e6e7e9; --nt-muted: #667382; --nt-faint: #9aa0ac; width: min(440px, 100%); box-shadow: -10px 0 10px -3px rgb(0 0 0 / .1); }
    #offcanvasNotifications .offcanvas-header { padding-bottom: .5rem; }
    #offcanvasNotifications .offcanvas-body { padding: 0; display: flex; flex-direction: column; min-height: 0; }
    .nt-n { font-size: 12px; font-weight: 700; color: var(--tblr-primary); background: color-mix(in srgb, var(--tblr-primary) 12%, white); border-radius: 999px; padding: 2px 9px; }
    .nt-tabs { display: flex; gap: 4px; padding: 0 1rem; border-bottom: 1px solid var(--nt-line); }
    .nt-tab { border: 0; background: none; padding: 8px 10px; font-size: 13.5px; font-weight: 600; color: var(--nt-muted); border-bottom: 2px solid transparent; margin-bottom: -1px; display: inline-flex; gap: 6px; align-items: center; }
    .nt-tab[aria-selected="true"] { color: var(--tblr-primary); border-bottom-color: var(--tblr-primary); }
    .nt-tab .c { font-size: 11px; font-weight: 700; background: #f1f3f5; color: var(--nt-muted); border-radius: 999px; padding: 1px 7px; }
    .nt-tab[aria-selected="true"] .c { background: color-mix(in srgb, var(--tblr-primary) 12%, white); color: var(--tblr-primary); }
    .nt-tools { padding: .6rem 1rem; display: flex; flex-direction: column; gap: 8px; border-bottom: 1px solid var(--nt-line); }
    .nt-row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .nt-pill { --pc: var(--tblr-primary); display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px 4px 8px; border: 1px solid var(--tblr-border-color, #dee2e6); border-radius: 999px; background: #fff; color: #182433; font-size: 12.5px; font-weight: 600; }
    .nt-pill:hover { border-color: var(--pc); }
    .nt-pill[aria-pressed="true"] { border-color: var(--pc); background: color-mix(in srgb, var(--pc) 13%, white); color: var(--pc); }
    .nt-pill i { width: 9px; height: 9px; border-radius: 50%; background: var(--pc); }
    .nt-sort { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--nt-muted); margin: 0; }
    .nt-sort select { font-size: 12.5px; padding: 3px 24px 3px 8px; border: 1px solid var(--tblr-border-color, #dee2e6); border-radius: 8px; background-color: #fff; }
    .nt-link { border: 0; background: none; color: var(--tblr-primary); font-size: 12.5px; font-weight: 600; padding: 4px 6px; border-radius: 6px; margin-left: auto; }
    .nt-link:hover { background: color-mix(in srgb, var(--tblr-primary) 12%, white); }
    .nt-link:disabled { color: var(--nt-faint); background: none; }
    .nt-list { flex: 1; overflow-y: auto; padding-bottom: 1rem; }
    .nt-grp { padding: 14px 1rem 6px; font-size: 11.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--nt-faint); }
    .nt-grp--urgent { color: #d63939; }
    .nt-it { display: grid; grid-template-columns: 34px minmax(0, 1fr) auto; gap: 4px 12px; padding: 11px 1rem; border-bottom: 1px solid var(--nt-line); background: #fff; cursor: pointer; }
    .nt-it:hover { background: #f6f8fb; }
    .nt-it--vue { background: #f7f8fa; }
    .nt-it--vue .nt-ico { filter: grayscale(1); opacity: .5; }
    .nt-it--vue .nt-t, .nt-it--vue .nt-ctx { color: var(--nt-faint); font-weight: 500; }
    .nt-it--vue .nt-ctx b { color: var(--nt-faint); }
    .nt-ico { width: 34px; height: 34px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; color: #fff; }
    .nt-t { font-weight: 700; font-size: 13.5px; line-height: 1.3; }
    .nt-ctx { color: var(--nt-muted); font-size: 12.5px; margin-top: 2px; overflow-wrap: anywhere; }
    .nt-ctx b { font-weight: 600; color: #182433; }
    .nt-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 6px; font-size: 11.5px; color: var(--nt-faint); }
    .nt-tag { display: inline-flex; padding: 1px 8px; border-radius: 6px; font-weight: 700; font-size: 11.5px; background: #f1f3f5; color: var(--nt-muted); }
    .nt-tag--retard { background: #d639391f; color: #d63939; }
    .nt-tag--proche { background: #f59f001f; color: #b07100; }
    .nt-it--vue .nt-tag { background: #f1f3f5; color: var(--nt-faint); }
    .nt-side { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
    .nt-when { font-size: 11.5px; color: var(--nt-faint); white-space: nowrap; }
    .nt-chk { width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--tblr-border-color, #dee2e6); background: #fff; color: var(--nt-muted); display: inline-flex; align-items: center; justify-content: center; padding: 0; }
    .nt-chk:hover { background: var(--tblr-primary); border-color: var(--tblr-primary); color: #fff; }
    .nt-vide { padding: 48px 24px; text-align: center; color: var(--nt-muted); }
    .nt-foot { padding: .6rem 1rem; border-top: 1px solid var(--nt-line); font-size: 12.5px; color: var(--nt-muted); display: flex; justify-content: space-between; gap: 10px; }
    .nt-svg { width: 1em; height: 1em; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .js-notif-badge { position: absolute; top: -6px; right: -6px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: #ff4d4f; color: #fff; font-size: 11px; font-weight: 700; line-height: 14px; border: 2px solid #fff; text-align: center; }
</style>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNotifications" aria-labelledby="offcanvasNotificationsLabel" data-bs-backdrop="false" data-bs-scroll="true">
    <div class="offcanvas-header">
        <h4 id="offcanvasNotificationsLabel" class="mb-0">Notifications <span class="nt-n d-none" id="ntNb"></span></h4>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body">
        <div class="nt-tabs" role="tablist" id="ntTabs"></div>
        <div class="nt-tools">
            <div class="nt-row" id="ntCats" role="group" aria-label="Filtrer par type"></div>
            <div class="nt-row">
                <label class="nt-sort">Trier par
                    <select id="ntSort" class="form-select form-select-sm" style="width: auto;">
                        <option value="recent">Plus récentes</option>
                        <option value="old">Plus anciennes</option>
                        <option value="urgence">Urgence (échéance proche)</option>
                        <option value="type">Type</option>
                    </select>
                </label>
                <button type="button" class="nt-link" id="ntToutLu">Tout marquer comme lu</button>
            </div>
        </div>
        <div class="nt-list" id="ntList"></div>
        <div class="nt-foot">
            <span>Les notifications lues sont conservées {{ \App\Http\Controllers\NotificationController::CONSERVATION_JOURS }} jours.</span>
            <a href="{{ route('profile.edit') }}#notifications">Réglages</a>
        </div>
    </div>
</div>

<script>
(function () {
    const panneau = document.getElementById('offcanvasNotifications');
    if (!panneau) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CATS = @json(array_map(fn ($c) => ['nom' => $c[0], 'couleur' => $c[1]], $categories));
    const TRACES = {
        user: '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M16 19h6"/><path d="M19 16v6"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4"/>',
        msg: '<path d="M8 9h8"/><path d="M8 13h6"/><path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z"/>',
        alert: '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>',
        cal: '<path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/>',
        clock: '<path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 7v5l3 3"/>',
        flag: '<path d="M5 5a5 5 0 0 1 7 0a5 5 0 0 0 7 0v9a5 5 0 0 1 -7 0a5 5 0 0 0 -7 0v-9z"/><path d="M5 21v-7"/>',
        eye: '<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>',
        check: '<path d="M5 12l5 5l10 -10"/>',
        undo: '<path d="M9 14l-4 -4l4 -4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/>',
        pkg: '<path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/>',
        cam: '<path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2"/><path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/>',
        refresh: '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>',
        edit: '<path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415"/>',
        trash: '<path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>'
    };

    let liste = [], onglet = 'non_lues', cats = {}, tri = 'recent', charge = false;
    const $ = (s) => panneau.querySelector(s);

    const svg = (nom, taille) => {
        const e = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        e.setAttribute('viewBox', '0 0 24 24'); e.setAttribute('class', 'nt-svg');
        e.style.fontSize = (taille || 18) + 'px';
        e.innerHTML = TRACES[nom] || TRACES.alert;
        return e;
    };
    const el = (tag, cls, txt) => { const e = document.createElement(tag); if (cls) e.className = cls; if (txt != null) e.textContent = txt; return e; };

    // « **gras** » -> <b>, sans jamais injecter de HTML.
    const contexte = (texte) => {
        const d = el('div', 'nt-ctx');
        String(texte || '').split('**').forEach((morceau, i) => { if (!morceau) return; d.appendChild(i % 2 ? el('b', '', morceau) : document.createTextNode(morceau)); });
        return d;
    };

    const depuis = (iso) => {
        const m = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
        if (m < 1) return 'à l’instant';
        if (m < 60) return 'il y a ' + m + ' min';
        if (m < 1440) return 'il y a ' + Math.round(m / 60) + ' h';
        if (m < 2880) return 'hier';
        return 'il y a ' + Math.round(m / 1440) + ' j';
    };
    const minutes = (n) => Math.max(0, Math.round((Date.now() - new Date(n.cree).getTime()) / 60000));

    function majBadges(n) {
        document.querySelectorAll('.js-notif-badge').forEach((b) => { b.textContent = n > 9 ? '9+' : n; b.classList.toggle('d-none', !n); });
    }

    function visibles() {
        const choisies = Object.keys(cats).filter((k) => cats[k]);
        return liste.filter((n) => {
            if (onglet === 'non_lues' && n.vue) return false;
            if (onglet === 'lues' && !n.vue) return false;
            return !choisies.length || choisies.includes(n.categorie);
        });
    }

    function trier(l) {
        l = l.slice();
        if (tri === 'recent') l.sort((a, b) => minutes(a) - minutes(b));
        if (tri === 'old') l.sort((a, b) => minutes(b) - minutes(a));
        if (tri === 'urgence') l.sort((a, b) => (a.jours ?? 999) - (b.jours ?? 999) || minutes(a) - minutes(b));
        if (tri === 'type') l.sort((a, b) => a.categorie.localeCompare(b.categorie) || minutes(a) - minutes(b));
        return l;
    }

    function groupe(n) {
        if (tri === 'type') return CATS[n.categorie].nom;
        if (tri === 'urgence') {
            if (n.jours == null) return 'Sans échéance';
            return n.jours < 0 ? 'En retard' : n.jours <= 3 ? 'Dans les 3 jours' : n.jours <= 7 ? 'Cette semaine' : 'Plus tard';
        }
        const m = minutes(n);
        return m < 1440 ? 'Aujourd’hui' : m < 2880 ? 'Hier' : 'Plus ancien';
    }

    function appel(url, methode, corps) {
        return fetch(url, {
            method: methode || 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
            body: corps ? JSON.stringify(corps) : undefined,
        }).then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); });
    }

    function marquer(n, vue) {
        n.vue = vue;
        dessiner();
        appel('/notifications/' + n.id + '/vue', 'POST', { vue }).then((j) => majBadges(j.non_lues)).catch(() => { n.vue = !vue; dessiner(); });
    }

    function dessiner() {
        const nonLues = liste.filter((n) => !n.vue).length;
        const nb = $('#ntNb'); nb.textContent = nonLues + ' non lue' + (nonLues > 1 ? 's' : ''); nb.classList.toggle('d-none', !nonLues);
        $('#ntToutLu').disabled = !nonLues;
        if (charge) majBadges(nonLues);   // avant le premier chargement, le badge reste celui du serveur

        const tabs = $('#ntTabs'); tabs.textContent = '';
        [['non_lues', 'Non lues', nonLues], ['lues', 'Lues', liste.length - nonLues], ['toutes', 'Toutes', liste.length]].forEach(([k, lib, c]) => {
            const b = el('button', 'nt-tab'); b.type = 'button'; b.setAttribute('role', 'tab'); b.setAttribute('aria-selected', String(onglet === k));
            b.append(lib + ' ', el('span', 'c', c));
            b.onclick = () => { onglet = k; dessiner(); };
            tabs.appendChild(b);
        });

        const pills = $('#ntCats'); pills.textContent = '';
        Object.keys(CATS).forEach((k) => {
            const b = el('button', 'nt-pill'); b.type = 'button'; b.style.setProperty('--pc', CATS[k].couleur);
            b.setAttribute('aria-pressed', String(!!cats[k])); b.append(document.createElement('i'), CATS[k].nom);
            b.onclick = () => { cats[k] = !cats[k]; dessiner(); };
            pills.appendChild(b);
        });

        const zone = $('#ntList'); zone.textContent = '';
        const lignes = trier(visibles());
        if (!lignes.length) {
            const v = el('div', 'nt-vide');
            v.append(svg('check', 30), el('p', 'mt-2 mb-0', !charge ? 'Chargement…' : (onglet === 'lues' ? 'Aucune notification lue.' : 'Tout est à jour. Rien à traiter pour le moment.')));
            zone.appendChild(v);
            return;
        }

        let dernier = null;
        lignes.forEach((n) => {
            const g = groupe(n);
            if (g !== dernier) { dernier = g; zone.appendChild(el('div', 'nt-grp' + (g === 'En retard' ? ' nt-grp--urgent' : ''), g)); }

            const it = el('div', 'nt-it' + (n.vue ? ' nt-it--vue' : '')); it.tabIndex = 0;
            const ico = el('span', 'nt-ico'); ico.style.background = CATS[n.categorie].couleur; ico.appendChild(svg(n.icone));

            const corps = el('div');
            corps.appendChild(el('div', 'nt-t', n.titre));
            if (n.contexte) corps.appendChild(contexte(n.contexte));
            const meta = el('div', 'nt-meta');
            if (n.jalon) meta.appendChild(el('span', 'nt-tag' + (n.jours != null && n.jours < 0 ? ' nt-tag--retard' : (n.jours != null && n.jours <= 3 ? ' nt-tag--proche' : '')), n.jalon));
            if (n.projet) meta.appendChild(el('span', 'nt-tag', n.projet));
            meta.appendChild(el('span', '', CATS[n.categorie].nom));
            corps.appendChild(meta);

            const cote = el('div', 'nt-side'); cote.appendChild(el('span', 'nt-when', depuis(n.cree)));
            const chk = el('button', 'nt-chk'); chk.type = 'button';
            chk.title = chk.ariaLabel = n.vue ? 'Remettre en non lue' : 'Marquer comme lue';
            chk.appendChild(svg(n.vue ? 'undo' : 'check', 15));
            chk.onclick = (e) => { e.stopPropagation(); marquer(n, !n.vue); };
            cote.appendChild(chk);

            const ouvrir = () => {
                if (!n.vue) marquer(n, true);
                if (n.url) location.href = n.url;
            };
            it.onclick = ouvrir;
            it.onkeydown = (e) => { if (e.key === 'Enter') ouvrir(); };

            it.append(ico, corps, cote);
            zone.appendChild(it);
        });
    }

    function recharger() {
        return appel('/notifications', 'GET').then((j) => { liste = j.notifications; charge = true; dessiner(); }).catch(() => { charge = true; dessiner(); });
    }

    $('#ntSort').onchange = function () { tri = this.value; dessiner(); };
    $('#ntToutLu').onclick = () => {
        liste.forEach((n) => { n.vue = true; });
        dessiner();
        appel('/notifications/tout-vu', 'POST').then((j) => majBadges(j.non_lues)).catch(recharger);
    };
    panneau.addEventListener('show.bs.offcanvas', recharger);

    // Le badge suit les nouvelles notifications sans recharger la page.
    const compter = () => { if (!document.hidden) appel('/notifications/compte', 'GET').then((j) => majBadges(j.non_lues)).catch(() => {}); };
    setInterval(compter, 60000);
    document.addEventListener('visibilitychange', compter);

    dessiner();
})();
</script>
