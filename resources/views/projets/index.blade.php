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
            <div class="pt-grille" id="ptGrille">
                @foreach($projets as $projet)
                    @include('projets._carte', ['projet' => $projet, 'mienne' => $mienne($projet)])
                @endforeach
            </div>
            <div class="pt-vide d-none" id="ptAucun">Aucun projet ne correspond à ces filtres.</div>
        @endif
    </div>

    @include('projets._modal-host')

    <script>
        (function () {
            const cartes = Array.from(document.querySelectorAll('[data-projet]'));
            const grille = document.getElementById('ptGrille');
            const aucun = document.getElementById('ptAucun');
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

            function appliquer(f) {
                let visibles = 0;
                cartes.forEach(c => {
                    const ok = visible(c, f);
                    c.classList.toggle('d-none', !ok);
                    if (ok) visibles++;
                });

                // « Mes projets » : du plus proche au plus éloigné de l'échéance (sans échéance en dernier, projets clos après).
                if (grille) {
                    const cle = (c) => (c.classList.contains('pt-carte--clos') ? '1' : '0') + (c.dataset.date || '9999-12-31');
                    cartes.slice()
                        .sort((x, y) => f.mien
                            ? (cle(x) < cle(y) ? -1 : cle(x) > cle(y) ? 1 : x.dataset.ordre - y.dataset.ordre)
                            : x.dataset.ordre - y.dataset.ordre)
                        .forEach(c => grille.appendChild(c));
                }

                aucun?.classList.toggle('d-none', visibles > 0 || !cartes.length);
            }

            document.addEventListener('pt-filtres', (e) => appliquer(e.detail));
            if (window.ptFiltres) appliquer(window.ptFiltres.lire());
        })();
    </script>
</x-app-layout>
