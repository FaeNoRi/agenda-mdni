@include('projets._styles')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">{{ __('Projets & tâches') }}</h2>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create', \App\Models\Projet::class)
                    <button type="button" class="btn pt-btn-doux" data-projet-nouveau data-dock-plus data-dock-label="Ajouter un projet">Nouveau projet</button>
                @endcan
                @include('projets._onglets', ['actif' => 'projets'])
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto py-4 px-4">
        {{-- Chiffres clés --}}
        <div class="pt-kpis">
            @foreach([
                ['Projets actifs', $kpis['projets'], '#4263eb', 'folders'],
                ['Tâches en cours', $kpis['ouvertes'], '#4299e1', 'list-check'],
                ['En retard', $kpis['retard'], '#d63939', 'alert-triangle'],
                ['À valider', $kpis['a_valider'], '#ae3ec9', 'eye-check'],
            ] as [$libelle, $nb, $couleur, $icone])
                <div class="pt-kpi">
                    <span class="pt-kpi__rond" style="background: {{ $couleur }}1f; color: {{ $couleur }};"><x-icone :name="$icone" :size="19" /></span>
                    <div>
                        <div class="pt-kpi__nb" style="color: {{ $couleur }};">{{ $nb }}</div>
                        <div class="pt-kpi__lib">{{ $libelle }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @include('projets._filtres', [
            'libMien' => 'Mes projets', 'nbRetard' => $retardProjets, 'etats' => $etats, 'libEtat' => 'État',
            'personnes' => $personnes,
        ])

        @if($projets->isEmpty())
            <div class="pt-vide">
                <x-icone name="folders" :size="32" /><br>
                Aucun projet pour le moment.
            </div>
        @else
            @php
                [$enCours, $clos] = [$projets->filter(fn ($p) => $p->etat->estOuvert()), $projets->reject(fn ($p) => $p->etat->estOuvert())];
            @endphp

            <div class="pt-grille" id="ptGrille">
                @foreach($enCours as $projet)
                    @include('projets._carte', ['projet' => $projet, 'mienne' => $mienne($projet)])
                @endforeach
            </div>

            {{-- Projets terminés ou annulés : rangés sous les projets en cours, repliés par défaut --}}
            @if($clos->isNotEmpty())
                <section class="pt-groupe pt-groupe--ferme" id="ptClos" style="margin-top: 18px;">
                    <button type="button" class="pt-groupe__tete" id="ptClosBascule" aria-expanded="false">
                        <span class="pt-chevron"><x-icone name="chevron-down" :size="16" /></span>
                        <span class="pt-groupe__nom">Projets terminés ou annulés</span>
                        <span class="pt-groupe__nb" id="ptClosNb" data-total="{{ $clos->count() }}">{{ $clos->count() }}</span>
                    </button>
                    <div class="d-none" id="ptClosCorps" style="padding: 14px;">
                        <div class="pt-grille pt-grille--mini" id="ptGrilleClos">
                            @foreach($clos as $projet)
                                @include('projets._mini', ['projet' => $projet, 'mienne' => $mienne($projet)])
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
            <div class="pt-vide d-none" id="ptAucun">Aucun projet ne correspond à ces filtres.</div>
        @endif
    </div>

    @include('projets._modal-host')

    <script>
        (function () {
            const cartes = Array.from(document.querySelectorAll('[data-projet]'));
            const aucun = document.getElementById('ptAucun');
            const clos = document.getElementById('ptClos');
            const closCorps = document.getElementById('ptClosCorps');
            const closNb = document.getElementById('ptClosNb');
            let closManuel = null;   // choix de l'utilisateur ; à défaut, un filtre actif déplie le rangement s'il contient des résultats
            cartes.forEach((c, i) => { c.dataset.ordre = i; });

            function visible(c, f) {
                if (f.mien && c.dataset.mien !== '1') return false;
                if (f.retard && c.dataset.retard !== '1') return false;
                if (f.etats.length && !f.etats.includes(c.dataset.etat)) return false;
                if (f.pers.length && !c.dataset.pers.split(',').some(id => f.pers.includes(id))) return false;
                if (f.texte && !c.dataset.titre.includes(f.texte)) return false;
                if (f.de || f.a) {
                    const d = c.dataset.date;             // un projet sans échéance ne peut pas être dans une période
                    if (!d || (f.de && d < f.de) || (f.a && d > f.a)) return false;
                }
                return true;
            }

            let closEtat = false;

            function appliquer(f) {
                let visibles = 0;
                cartes.forEach(c => {
                    const ok = visible(c, f);
                    c.classList.toggle('d-none', !ok);
                    if (ok) visibles++;
                });

                // « Mes projets » : du plus proche au plus éloigné de l'échéance (sans échéance en dernier).
                // Chaque carte reste dans sa propre grille (en cours / terminés).
                const cle = (c) => c.dataset.date || '9999-12-31';
                cartes.slice()
                    .sort((x, y) => f.mien
                        ? (cle(x) < cle(y) ? -1 : cle(x) > cle(y) ? 1 : x.dataset.ordre - y.dataset.ordre)
                        : x.dataset.ordre - y.dataset.ordre)
                    .forEach(c => c.parentElement.appendChild(c));

                if (clos) {
                    const dedans = cartes.filter(c => clos.contains(c));
                    const n = dedans.filter(c => !c.classList.contains('d-none')).length;
                    const total = dedans.length;
                    closNb.textContent = f.actifs > 0 && n !== total ? n + ' sur ' + total : String(total);
                    clos.classList.toggle('d-none', f.actifs > 0 && n === 0);
                    const ouvert = closManuel ?? (f.actifs > 0 && n > 0);
                    closEtat = ouvert;
                    closCorps.classList.toggle('d-none', !ouvert);
                    clos.classList.toggle('pt-groupe--ferme', !ouvert);
                    document.getElementById('ptClosBascule').setAttribute('aria-expanded', ouvert ? 'true' : 'false');
                }

                aucun?.classList.toggle('d-none', visibles > 0 || !cartes.length);
            }

            document.addEventListener('pt-filtres', (e) => { closManuel = null; appliquer(e.detail); });
            document.getElementById('ptClosBascule')?.addEventListener('click', () => {
                closManuel = !closEtat;
                if (window.ptFiltres) appliquer(window.ptFiltres.lire());
            });
            if (window.ptFiltres) appliquer(window.ptFiltres.lire());
        })();
    </script>
</x-app-layout>
