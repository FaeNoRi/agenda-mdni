@include('projets._styles')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">{{ __('Projets & tâches') }}</h2>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create', \App\Models\Tache::class)
                    <button type="button" class="btn pt-btn-doux" data-tache-nouvelle data-dock-plus data-dock-label="Ajouter une tâche">Nouvelle tâche</button>
                @endcan
                @include('projets._onglets', ['actif' => 'taches'])
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto py-4 px-4">
        @if($cible)
            <div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2" role="status" data-cible>
                <span style="flex: 1;">
                    Affichage limité à la tâche <strong>{{ $cible->titre }}</strong>@if($cible->projet) · projet {{ $cible->projet->nom }}@endif
                </span>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('taches.index') }}">Afficher toutes les tâches</a>
            </div>
        @endif

        @include('projets._filtres', [
            'libMien' => 'Mes tâches', 'nbRetard' => $nbRetard, 'etats' => $statuts, 'libEtat' => 'Statut',
            'personnes' => $personnes, 'projets' => $projets,
        ])

        @if($groupes->isEmpty())
            <div class="pt-vide">
                <x-icone name="list-check" :size="32" /><br>
                Aucune tâche pour le moment.
            </div>
        @else
            <div class="d-flex justify-content-end mb-2">
                <button type="button" id="ptToutBascule" class="btn btn-sm btn-link link-secondary px-0" style="font-size: 12.5px; font-weight: 600;">
                    <x-icone name="chevron-down" :size="14" /> <span id="ptToutLibelle">Tout réduire</span>
                </button>
            </div>
            <div id="ptGroupes">
            @foreach($groupes as $groupe)
                @php
                    $projet = $groupe['projet'];
                    $taches = $groupe['taches'];
                    $couleur = $projet?->couleur ?: '#667382';
                    $parStatut = $taches->countBy(fn ($t) => $t->statut->value);
                    $actives = $taches->count() - ($parStatut['annule'] ?? 0);
                    $clos = $projet && !$projet->etat->estOuvert();
                @endphp
                <section class="pt-groupe" data-groupe data-ouvert="{{ $clos ? 0 : 1 }}" data-clos="{{ $clos ? 1 : 0 }}" data-sans-projet="{{ $projet ? 0 : 1 }}" data-nom="{{ mb_strtolower($projet?->nom ?? 'sans projet') }}">
                    <button type="button" class="pt-groupe__tete" data-bascule aria-expanded="{{ $clos ? 'false' : 'true' }}">
                        <span class="pt-chevron"><x-icone name="chevron-down" :size="16" /></span>
                        <span class="pt-picto" style="width: 30px; height: 30px; background: {{ $couleur }}1f; color: {{ $couleur }};">
                            <x-icone :name="$projet?->icone ?: 'inbox'" :size="17" />
                        </span>
                        @if($projet)
                            <span class="pt-groupe__nom">{{ $projet->nom }}</span>
                        @else
                            <span class="pt-groupe__nom">Sans projet</span>
                        @endif
                        <span class="pt-groupe__nb" data-total="{{ $taches->count() }}">{{ $taches->count() }} tâche{{ $taches->count() > 1 ? 's' : '' }}</span>
                        <span style="flex: 1;"></span>
                        <span class="pt-compteurs" style="gap: 6px;">
                            @foreach($statutsCarte as $statut)
                                @if($n = $parStatut[$statut->value] ?? 0)
                                    <span class="pt-compteur" style="font-size: 11.5px; gap: 3px;">
                                        <x-statut-carre :statut="$statut" :size="18" :tip="$n.' '.$statut->accorde($n)" />{{ $n }}
                                    </span>
                                @endif
                            @endforeach
                        </span>
                        @if($projet)
                            <span class="pt-groupe__fait" style="color: {{ $couleur }}; background: {{ $couleur }}1f;">{{ $parStatut['termine'] ?? 0 }}/{{ $actives }}</span>
                        @endif
                    </button>
                    <div class="pt-groupe__corps {{ $clos ? 'd-none' : '' }}" data-corps>
                        <div class="pt-grille-mini">
                            @foreach($taches as $tache)
                                @include('taches._mini', ['tache' => $tache])
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
            </div>
            <div class="pt-vide d-none" id="ptAucun">
                <x-icone name="list-check" :size="32" /><br>
                Aucune tâche ne correspond à ces filtres.
            </div>
        @endif
    </div>

    @include('taches._modal-host')

    <script>
        (function () {
            const conteneur = document.getElementById('ptGroupes');
            const cibleId = @json($cible?->id);   // tâche visée par une notification
            const groupes = Array.from(document.querySelectorAll('[data-groupe]'));
            const aucun = document.getElementById('ptAucun');
            groupes.forEach((g, i) => { g.dataset.ordre = i; });
            const toutBascule = document.getElementById('ptToutBascule');
            const toutLibelle = document.getElementById('ptToutLibelle');
            let choixManuel = false;   // vrai dès que l'utilisateur plie/déplie lui-même : un filtre actif ne rouvre plus tout

            function afficher(carte, g, f) {
                if (cibleId && carte.dataset.tache !== String(cibleId)) return false;
                if (f.mien && carte.dataset.mien !== '1') return false;
                if (f.retard && carte.dataset.retard !== '1') return false;
                if (f.pers.length && !carte.dataset.resp.split(',').some(id => f.pers.includes(id))) return false;
                if (f.projet !== '' && carte.dataset.projet !== f.projet) return false;
                if (f.etats.length && !f.etats.includes(carte.dataset.statut)) return false;
                // Intitulé : titre de la tâche ou nom de son projet.
                if (f.texte && !carte.dataset.titre.includes(f.texte) && !g.dataset.nom.includes(f.texte)) return false;
                if (f.de || f.a) {
                    const d = carte.dataset.date;
                    if ((f.de && d < f.de) || (f.a && d > f.a)) return false;
                }
                return true;
            }

            function appliquer(f) {
                const filtre = f.actifs > 0 || !!cibleId;
                let total = 0;

                groupes.forEach(g => {
                    const cartes = Array.from(g.querySelectorAll('[data-tache]'));
                    let n = 0;
                    let proche = '9999-12-31';
                    cartes.forEach(c => {
                        const ok = afficher(c, g, f);
                        c.classList.toggle('d-none', !ok);
                        if (ok) { n++; if (c.dataset.date < proche) proche = c.dataset.date; }
                    });
                    total += n;
                    g.dataset.proche = proche;

                    g.classList.toggle('d-none', n === 0);
                    // Un filtre actif déplie les projets concernés ; sans filtre on retrouve l'état choisi.
                    const ouvert = (filtre && !choixManuel) ? true : g.dataset.ouvert === '1';
                    g.querySelector('[data-corps]').classList.toggle('d-none', !ouvert);
                    g.querySelector('[data-bascule]').setAttribute('aria-expanded', ouvert ? 'true' : 'false');
                    g.classList.toggle('pt-groupe--ferme', !ouvert);

                    const nb = g.querySelector('.pt-groupe__nb');
                    nb.textContent = n + ' tâche' + (n > 1 ? 's' : '') + (filtre && n !== cartes.length ? ' sur ' + cartes.length : '');
                });

                // Tri « Échéance la plus proche » : les projets sont classés par leur tâche la plus proche (« sans projet » en dernier).
                if (conteneur) {
                    const cle = (g) => g.dataset.sansProjet + g.dataset.clos + g.dataset.proche;
                    groupes.slice()
                        .sort((x, y) => f.tri
                            ? (cle(x) < cle(y) ? -1 : cle(x) > cle(y) ? 1 : x.dataset.ordre - y.dataset.ordre)
                            : x.dataset.ordre - y.dataset.ordre)
                        .forEach(g => conteneur.appendChild(g));
                }

                aucun?.classList.toggle('d-none', total > 0);

                // Le bouton propose l'action utile : tout réduire tant qu'un groupe visible est ouvert, sinon tout déplier.
                const visibles = groupes.filter(g => !g.classList.contains('d-none'));
                const unOuvert = visibles.some(g => !g.classList.contains('pt-groupe--ferme'));
                toutLibelle.textContent = unOuvert ? 'Tout réduire' : 'Tout déplier';
                toutBascule.classList.toggle('d-none', visibles.length === 0);
            }

            // Fige l'état affiché dans chaque groupe avant un choix manuel (un filtre peut en avoir ouvert certains).
            function figer() {
                groupes.forEach(g => { g.dataset.ouvert = g.classList.contains('pt-groupe--ferme') ? '0' : '1'; });
                choixManuel = true;
            }

            document.addEventListener('pt-filtres', (e) => { choixManuel = false; appliquer(e.detail); });

            toutBascule.addEventListener('click', () => {
                const reduire = !groupes.filter(g => !g.classList.contains('d-none')).every(g => g.classList.contains('pt-groupe--ferme'));
                figer();
                groupes.forEach(g => { g.dataset.ouvert = reduire ? '0' : '1'; });
                appliquer(window.ptFiltres.lire());
            });

            groupes.forEach(g => g.querySelector('[data-bascule]').addEventListener('click', () => {
                figer();
                g.dataset.ouvert = g.classList.contains('pt-groupe--ferme') ? '1' : '0';
                if (window.ptFiltres) appliquer(window.ptFiltres.lire());
            }));

            if (window.ptFiltres) appliquer(window.ptFiltres.lire());
        })();
    </script>
</x-app-layout>
