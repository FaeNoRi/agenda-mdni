{{--
    Filtres des pages Projets et Tâches : une barre discrète dans la page + un tiroir (même présentation que
    le tiroir des filtres du tableau de bord). Les filtres s'appliquent instantanément, côté navigateur.

    Variables : $libMien ("Mes projets" / "Mes tâches"), $nbRetard, $etats (enum : value, label, couleur),
                $libEtat ("État" / "Statut"), $personnes (utilisateurs), $projets (facultatif : liste déroulante).

    Le tiroir ne filtre rien lui-même : il émet l'événement « pt-filtres » (detail = valeurs courantes) que la page écoute,
    et expose window.ptFiltres.lire() / .effacer().
--}}
<style>
    .pf-barre { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 14px; }
    /* Avec le menu flottant, le bouton « Filtres » est dans le dock : la barre n'apparaît que s'il y a des filtres actifs. */
    body.dock-ok .pf-barre:not(.pf-actif) { display: none; }
    body.dock-ok .pf-barre [data-dock-filtres] { display: none !important; }

    .fpill { --pc: var(--tblr-primary); display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border: 1px solid var(--tblr-border-color, #dfe3e8); border-radius: 999px; background: #fff; color: var(--tblr-body-color, #182433); font-size: 13px; font-weight: 600; line-height: 1.3; cursor: pointer; transition: background .12s, border-color .12s, color .12s; }
    .fpill:hover { border-color: var(--pc); }
    .fpill.on { background: color-mix(in srgb, var(--pc) 13%, white); border-color: var(--pc); color: var(--pc); }
    .fpill--carre { padding: 4px 12px 4px 4px; border-radius: 8px; }
    .fsec { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #4b5563; }
    details.fgroup > summary { list-style: none; cursor: pointer; user-select: none; }
    details.fgroup > summary::-webkit-details-marker { display: none; }
    details.fgroup > summary::after { content: ""; margin-left: auto; width: 8px; height: 8px; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: rotate(45deg); transition: transform .15s; }
    details.fgroup[open] > summary::after { transform: rotate(-135deg); }
    .fcount { min-width: 20px; padding: 0 6px; border-radius: 999px; background: var(--tblr-primary); color: var(--tblr-primary-fg); font-size: 11px; font-weight: 700; line-height: 20px; text-align: center; letter-spacing: 0; }
</style>

<div class="pf-barre" id="pfBarre">
    <button type="button" class="btn pt-btn-doux" data-dock-filtres data-bs-toggle="offcanvas" data-bs-target="#pfTiroir" aria-controls="pfTiroir">
        <x-icone name="filter" :size="16" /> Filtres <span class="fcount d-none" id="pfCompte"></span>
    </button>
    <a href="#" id="pfEffacerBarre" class="d-none" style="font-size: 12.5px; font-weight: 600;">Effacer les filtres</a>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="pfTiroir" aria-labelledby="pfTiroirTitre" data-bs-backdrop="false" data-bs-scroll="true" style="box-shadow: -10px 0px 10px -3px rgb(0 0 0 / .1);">
    <div class="offcanvas-header">
        <h4 id="pfTiroirTitre">Filtres</h4>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body">

        {{-- Intitulé --}}
        <div class="mb-3">
            <label class="form-label" for="pfTexte">Intitulé</label>
            <input type="search" id="pfTexte" class="form-control" placeholder="Rechercher par intitulé…" autocomplete="off">
        </div>

        {{-- Date limite, avec période facultative (même principe que le tableau de bord) --}}
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label mb-0" for="pfDe">Date limite</label>
                <button type="button" id="pfBasculePeriode" class="btn btn-link btn-sm px-0 text-muted" aria-expanded="false">Ajouter une période</button>
            </div>
            <input type="date" id="pfDe" class="form-control">
            <div id="pfAMur" class="mt-2 d-none">
                <label class="form-label" for="pfA">Date de fin</label>
                <input type="date" id="pfA" class="form-control">
            </div>
        </div>

        {{-- Filtres rapides --}}
        <div class="mb-4">
            <div class="fsec">Filtres rapides</div>
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filtres rapides">
                <button type="button" class="fpill" data-pf-rapide="mien" aria-pressed="false" style="--pc: var(--tblr-primary);">
                    <x-icone name="user" :size="16" /> {{ $libMien }}
                </button>
                <button type="button" class="fpill" data-pf-rapide="retard" aria-pressed="false" style="--pc: #d63939;">
                    <x-icone name="alert-triangle" :size="16" /> En retard <b>{{ $nbRetard }}</b>
                </button>
                {{-- Un tri, pas un filtre : il ne masque rien et n'est pas compté dans les filtres actifs. --}}
                <button type="button" class="fpill" data-pf-rapide="tri" aria-pressed="false" style="--pc: #4299e1;" title="Classer de l'échéance la plus proche à la plus éloignée">
                    <x-icone name="calendar" :size="16" /> Échéance la plus proche
                </button>
            </div>
        </div>

        {{-- État / statut --}}
        <div class="mb-4">
            <div class="fsec">{{ $libEtat }} <span class="fcount d-none" data-pf-compte="etat"></span></div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($etats as $etat)
                    <button type="button" class="fpill fpill--carre" data-pf-etat="{{ $etat->value }}" aria-pressed="false" style="--pc: {{ $etat->couleur() }};">
                        <x-statut-carre :statut="$etat" :size="22" />{{ $etat->label() }}
                    </button>
                @endforeach
            </div>
        </div>

        @isset($projets)
            <div class="mb-4">
                <label class="form-label" for="pfProjet">Projet</label>
                <select id="pfProjet" class="form-select">
                    <option value="">Tous les projets</option>
                    <option value="0">Sans projet</option>
                    @foreach($projets as $p)
                        <option value="{{ $p->id }}">{{ $p->nom }}</option>
                    @endforeach
                </select>
            </div>
        @endisset

        {{-- Personne : repliée par défaut, noms complets --}}
        <details class="fgroup mb-4">
            <summary class="fsec">Personne <span class="fcount d-none" data-pf-compte="pers"></span></summary>
            <div class="d-flex flex-wrap gap-2">
                @foreach($personnes as $u)
                    <button type="button" class="fpill" data-pf-pers="{{ $u->id }}" aria-pressed="false">{{ $u->name }}</button>
                @endforeach
            </div>
        </details>

        <div class="offcanvas-footer p-3 d-flex justify-content-between">
            <button id="pfReinit" type="button" class="btn btn-outline-secondary">Réinitialiser</button>
            <button type="button" class="btn btn-primary" data-bs-dismiss="offcanvas">Fermer</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const racine = document.getElementById('pfTiroir');
        if (!racine) return;
        const texte = document.getElementById('pfTexte');
        const de = document.getElementById('pfDe');
        const a = document.getElementById('pfA');
        const mur = document.getElementById('pfAMur');
        const bascule = document.getElementById('pfBasculePeriode');
        const projet = document.getElementById('pfProjet');
        const barre = document.getElementById('pfBarre');
        const compte = document.getElementById('pfCompte');
        const effacerBarre = document.getElementById('pfEffacerBarre');
        let periode = false;

        const actifs = (attr) => Array.from(racine.querySelectorAll('[' + attr + '].on')).map(b => b.getAttribute(attr));

        function lire() {
            const f = {
                texte: texte.value.trim().toLowerCase(),
                de: de.value,
                a: periode ? a.value : de.value,     // sans période : le jour choisi, exactement
                mien: !!racine.querySelector('[data-pf-rapide="mien"].on'),
                retard: !!racine.querySelector('[data-pf-rapide="retard"].on'),
                tri: !!racine.querySelector('[data-pf-rapide="tri"].on'),   // tri par échéance : n'est pas un filtre
                etats: actifs('data-pf-etat'),
                pers: actifs('data-pf-pers'),
                projet: projet ? projet.value : '',
            };
            // Nombre de filtres actifs (un champ = un filtre).
            f.actifs = (f.texte ? 1 : 0) + ((f.de || f.a) ? 1 : 0) + (f.mien ? 1 : 0) + (f.retard ? 1 : 0)
                + (f.etats.length ? 1 : 0) + (f.pers.length ? 1 : 0) + (f.projet !== '' ? 1 : 0);

            return f;
        }

        function pastille(el, n) {
            if (!el) return;
            el.textContent = n;
            el.classList.toggle('d-none', n === 0);
        }

        function emettre() {
            const f = lire();
            pastille(racine.querySelector('[data-pf-compte="etat"]'), f.etats.length);
            pastille(racine.querySelector('[data-pf-compte="pers"]'), f.pers.length);

            const n = f.actifs;
            pastille(compte, n);
            effacerBarre.classList.toggle('d-none', n === 0);
            barre.classList.toggle('pf-actif', n > 0);

            document.dispatchEvent(new CustomEvent('pt-filtres', { detail: f }));
        }

        function effacer() {
            texte.value = '';
            de.value = '';
            a.value = '';
            definirPeriode(false);
            if (projet) projet.value = '';
            racine.querySelectorAll('.fpill.on').forEach(b => { b.classList.remove('on'); b.setAttribute('aria-pressed', 'false'); });
            emettre();
        }

        function definirPeriode(on) {
            periode = on;
            mur.classList.toggle('d-none', !on);
            bascule.textContent = on ? 'Retirer la période' : 'Ajouter une période';
            bascule.setAttribute('aria-expanded', on ? 'true' : 'false');
            if (!on) a.value = '';
        }

        racine.addEventListener('click', (e) => {
            const p = e.target.closest('.fpill');
            if (!p) return;
            p.classList.toggle('on');
            p.setAttribute('aria-pressed', p.classList.contains('on') ? 'true' : 'false');
            emettre();
        });

        texte.addEventListener('input', emettre);
        de.addEventListener('input', emettre);
        a.addEventListener('input', emettre);
        if (projet) projet.addEventListener('change', emettre);
        bascule.addEventListener('click', () => { definirPeriode(!periode); emettre(); });
        document.getElementById('pfReinit').addEventListener('click', effacer);
        effacerBarre.addEventListener('click', (e) => { e.preventDefault(); effacer(); });

        window.ptFiltres = { lire: lire, effacer: effacer };
        emettre();
    })();
</script>
