@include('projets._styles')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">{{ __('Projets & tâches') }}</h2>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create', \App\Models\Tache::class)
                    <button type="button" class="btn btn-primary" data-tache-nouvelle>Nouvelle tâche</button>
                @endcan
                @include('projets._onglets', ['actif' => 'taches'])
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto py-4 px-4">
        {{-- Filtres on/off, instantanés --}}
        <div class="pt-filtres" id="ptFiltres" style="flex-direction: column; align-items: stretch; gap: 10px;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
                <button type="button" class="pt-pastille pt-pastille--texte" data-filtre="mien" style="--pc: var(--tblr-primary);">
                    <x-icone name="user" :size="14" /> Mes tâches
                </button>
                <button type="button" class="pt-pastille pt-pastille--texte" data-filtre="retard" style="--pc: #d63939;">
                    <x-icone name="alert-triangle" :size="14" /> En retard <b>{{ $nbRetard }}</b>
                </button>
                <span class="pt-filtres__sep"></span>
                <span class="pt-filtres__t">Personne</span>
                @foreach($personnes as $u)
                    <button type="button" class="pt-personne" data-personne="{{ $u->id }}" title="{{ $u->name }}">
                        <x-avatars :users="[$u]" :size="28" />
                    </button>
                @endforeach
                <span style="flex: 1;"></span>
                <select id="ptProjet" class="form-select form-select-sm" style="width: auto; max-width: 220px;" aria-label="Projet">
                    <option value="">Tous les projets</option>
                    <option value="0">Sans projet</option>
                    @foreach($projets as $p)
                        <option value="{{ $p->id }}">{{ $p->nom }}</option>
                    @endforeach
                </select>
                <select id="ptEcheance" class="form-select form-select-sm" style="width: auto;" aria-label="Date limite">
                    <option value="">Toutes les dates limites</option>
                    <option value="jour">Aujourd'hui</option>
                    <option value="semaine">Cette semaine</option>
                    <option value="mois">Ce mois-ci</option>
                </select>
            </div>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px;">
                <span class="pt-filtres__t" style="margin-right: 2px;">Statut</span>
                @foreach($statuts as $statut)
                    <button type="button" class="pt-pastille" data-statut-filtre="{{ $statut->value }}" style="--pc: {{ $statut->couleur() }};">
                        <x-statut-carre :statut="$statut" :size="22" />{{ $statut->label() }}
                    </button>
                @endforeach
                <a href="#" id="ptEffacer" class="d-none" style="margin-left: 6px; font-size: 11.5px; font-weight: 600;">Effacer les filtres</a>
            </div>
        </div>

        @if($groupes->isEmpty())
            <div class="pt-vide">
                <x-icone name="list-check" :size="32" /><br>
                Aucune tâche pour le moment.
            </div>
        @else
            @foreach($groupes as $groupe)
                @php
                    $projet = $groupe['projet'];
                    $taches = $groupe['taches'];
                    $couleur = $projet?->couleur ?: '#667382';
                    $parStatut = $taches->countBy(fn ($t) => $t->statut->value);
                    $actives = $taches->count() - ($parStatut['annule'] ?? 0);
                    $clos = $projet && !$projet->etat->estOuvert();
                @endphp
                <section class="pt-groupe" data-groupe data-ouvert="{{ $clos ? 0 : 1 }}">
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
            <div class="pt-vide d-none" id="ptAucun">
                <x-icone name="list-check" :size="32" /><br>
                Aucune tâche ne correspond à ces filtres.
            </div>
        @endif
    </div>

    @include('taches._modal-host')

    <script>
        (function () {
            const racine = document.getElementById('ptFiltres');
            const groupes = Array.from(document.querySelectorAll('[data-groupe]'));
            const aucun = document.getElementById('ptAucun');
            const effacer = document.getElementById('ptEffacer');
            const selProjet = document.getElementById('ptProjet');
            const selEcheance = document.getElementById('ptEcheance');
            const aujourdhui = @json($aujourdhui->toDateString());
            if (!racine) return;

            const ymd = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            const base = new Date(aujourdhui + 'T00:00:00');
            const finSemaine = new Date(base); finSemaine.setDate(base.getDate() + ((7 - base.getDay()) % 7)); // dimanche
            const finMois = new Date(base.getFullYear(), base.getMonth() + 1, 0);
            const bornes = { jour: ymd(base), semaine: ymd(finSemaine), mois: ymd(finMois) };

            const etat = () => ({
                mien: racine.querySelector('[data-filtre="mien"]').classList.contains('on'),
                retard: racine.querySelector('[data-filtre="retard"]').classList.contains('on'),
                qui: racine.querySelector('[data-personne].on')?.dataset.personne || '',
                projet: selProjet.value,
                echeance: selEcheance.value,
                statuts: Array.from(racine.querySelectorAll('[data-statut-filtre].on')).map(b => b.dataset.statutFiltre),
            });

            function afficher(carte, f) {
                if (f.mien && carte.dataset.mien !== '1') return false;
                if (f.retard && carte.dataset.retard !== '1') return false;
                if (f.qui && !carte.dataset.resp.split(',').includes(f.qui)) return false;
                if (f.projet !== '' && carte.dataset.projet !== f.projet) return false;
                if (f.statuts.length && !f.statuts.includes(carte.dataset.statut)) return false;
                if (f.echeance) {
                    const d = carte.dataset.date;
                    if (f.echeance === 'jour' ? d !== bornes.jour : d > bornes[f.echeance] || d < bornes.jour) return false;
                }
                return true;
            }

            function appliquer() {
                const f = etat();
                const filtre = f.mien || f.retard || f.qui || f.projet !== '' || f.echeance || f.statuts.length;
                let total = 0;

                groupes.forEach(g => {
                    const cartes = Array.from(g.querySelectorAll('[data-tache]'));
                    let n = 0;
                    cartes.forEach(c => {
                        const ok = afficher(c, f);
                        c.classList.toggle('d-none', !ok);
                        if (ok) n++;
                    });
                    total += n;

                    g.classList.toggle('d-none', n === 0);
                    // Un filtre actif déplie les projets concernés ; sans filtre on retrouve l'état choisi.
                    const ouvert = filtre ? true : g.dataset.ouvert === '1';
                    g.querySelector('[data-corps]').classList.toggle('d-none', !ouvert);
                    g.querySelector('[data-bascule]').setAttribute('aria-expanded', ouvert ? 'true' : 'false');
                    g.classList.toggle('pt-groupe--ferme', !ouvert);

                    const nb = g.querySelector('.pt-groupe__nb');
                    nb.textContent = n + ' tâche' + (n > 1 ? 's' : '') + (filtre && n !== cartes.length ? ' sur ' + cartes.length : '');
                });

                aucun?.classList.toggle('d-none', total > 0);
                effacer.classList.toggle('d-none', !filtre);
            }

            racine.addEventListener('click', (e) => {
                const bouton = e.target.closest('.pt-pastille, .pt-personne');
                if (!bouton) return;
                if (bouton.matches('[data-personne]')) {
                    const dejaActif = bouton.classList.contains('on');
                    racine.querySelectorAll('[data-personne].on').forEach(b => b.classList.remove('on'));
                    bouton.classList.toggle('on', !dejaActif);
                } else {
                    bouton.classList.toggle('on');
                }
                appliquer();
            });

            selProjet.addEventListener('change', appliquer);
            selEcheance.addEventListener('change', appliquer);

            effacer.addEventListener('click', (e) => {
                e.preventDefault();
                racine.querySelectorAll('.on').forEach(b => b.classList.remove('on'));
                selProjet.value = '';
                selEcheance.value = '';
                appliquer();
            });

            groupes.forEach(g => g.querySelector('[data-bascule]').addEventListener('click', () => {
                g.dataset.ouvert = g.dataset.ouvert === '1' ? '0' : '1';
                appliquer();
            }));

            appliquer();
        })();
    </script>
</x-app-layout>
