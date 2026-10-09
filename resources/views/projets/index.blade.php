@include('projets._styles')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">{{ __('Projets & tâches') }}</h2>
        </div>
    </x-slot>

    <div class="container mx-auto py-4 px-4">
        {{-- Chiffres clés --}}
        <div class="pt-kpis">
            @foreach([
                ['Projets actifs', $kpis['projets'], '#4263eb', 'folders'],
                ['Tâches ouvertes', $kpis['ouvertes'], '#4299e1', 'list-check'],
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

        {{-- Filtres on/off --}}
        <div class="pt-filtres" id="ptFiltres">
            <button type="button" class="pt-pastille pt-pastille--texte" data-filtre="mien" style="--pc: var(--tblr-primary);">
                <x-icone name="user" :size="14" /> Mes projets
            </button>
            <button type="button" class="pt-pastille pt-pastille--texte" data-filtre="retard" style="--pc: #d63939;">
                <x-icone name="alert-triangle" :size="14" /> En retard <b>{{ $retardProjets }}</b>
            </button>
            <span class="pt-filtres__sep"></span>
            <span class="pt-filtres__t">État</span>
            @foreach($etats as $etat)
                <button type="button" class="pt-pastille" data-etat-filtre="{{ $etat->value }}" style="--pc: {{ $etat->couleur() }};">
                    <x-statut-carre :statut="$etat" :size="22" />{{ $etat->label() }}
                </button>
            @endforeach
            <a href="#" id="ptEffacer" class="d-none" style="margin-left: 6px; font-size: 11.5px; font-weight: 600;">Effacer les filtres</a>
        </div>

        {{-- Légende --}}
        <div class="pt-legende">
            <span class="pt-legende__t">Légende</span>
            @foreach($statutsCarte as $statut)
                <span><x-statut-carre :statut="$statut" :size="18" />{{ $statut->label() }}</span>
            @endforeach
            <span style="color: #667382;"><x-icone name="circle-dashed" :size="16" /> Terminées : dans l'anneau</span>
        </div>

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

    <script>
        (function () {
            const filtres = document.getElementById('ptFiltres');
            const cartes = Array.from(document.querySelectorAll('[data-projet]'));
            const aucun = document.getElementById('ptAucun');
            const effacer = document.getElementById('ptEffacer');
            if (!filtres) return;

            function appliquer() {
                const mien = filtres.querySelector('[data-filtre="mien"]').classList.contains('on');
                const retard = filtres.querySelector('[data-filtre="retard"]').classList.contains('on');
                const etats = Array.from(filtres.querySelectorAll('[data-etat-filtre].on')).map(b => b.dataset.etatFiltre);

                let visibles = 0;
                cartes.forEach(c => {
                    const ok = (!mien || c.dataset.mien === '1')
                        && (!retard || c.dataset.retard === '1')
                        && (!etats.length || etats.includes(c.dataset.etat));
                    c.classList.toggle('d-none', !ok);
                    if (ok) visibles++;
                });

                aucun?.classList.toggle('d-none', visibles > 0 || !cartes.length);
                effacer.classList.toggle('d-none', !(mien || retard || etats.length));
            }

            filtres.addEventListener('click', (e) => {
                const bouton = e.target.closest('.pt-pastille');
                if (!bouton) return;
                bouton.classList.toggle('on');
                appliquer();
            });

            effacer.addEventListener('click', (e) => {
                e.preventDefault();
                filtres.querySelectorAll('.pt-pastille.on').forEach(b => b.classList.remove('on'));
                appliquer();
            });
        })();
    </script>
</x-app-layout>
