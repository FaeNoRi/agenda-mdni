@include('projets._styles')

@php
    $couleur = $projet->couleur ?: '#4299e1';
    $etat = $projet->etat;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">{{ __('Projets & tâches') }}</h2>
            @include('projets._onglets', ['actif' => 'projets'])
        </div>
    </x-slot>

    <div class="container mx-auto py-4 px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <a href="{{ route('projets.index') }}" class="d-inline-flex align-items-center gap-1" style="font-size: 12.5px; font-weight: 600;">
                <x-icone name="arrow-left" :size="14" /> Tous les projets
            </a>
            <div class="d-flex gap-2">
                @can('update', $projet)
                    <button type="button" class="btn pt-btn-doux" data-modifier-projet="{{ $projet->id }}">Modifier</button>
                @endcan
                @can('delete', $projet)
                    <button type="button" class="btn pt-btn-doux pt-btn-danger" data-supprimer-projet-demande>Supprimer</button>
                @endcan
            </div>
        </div>

        @can('delete', $projet)
            <div class="alert alert-danger d-none" data-suppr-projet-confirm role="alert">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span style="flex: 1;">
                        Supprimer définitivement ce projet
                        @if($projet->taches->isNotEmpty())
                            et ses <strong>{{ $projet->taches->count() }} tâche{{ $projet->taches->count() > 1 ? 's' : '' }}</strong>
                        @endif
                        (historique et commentaires compris) ?
                    </span>
                    <button type="button" class="btn pt-btn-doux pt-btn-danger" data-supprimer-projet="{{ $projet->id }}">Oui, supprimer</button>
                    <button type="button" class="btn btn-sm btn-link link-secondary" data-suppr-projet-annuler>Annuler</button>
                </div>
            </div>
        @endcan

        @foreach($avertissements as $avertissement)
            <div class="alert alert-warning d-flex align-items-center gap-2 py-2" role="alert">
                <x-icone name="alert-triangle" :size="18" /> <span>{{ $avertissement }}</span>
            </div>
        @endforeach

        <div class="pt-fiche">
            <div class="pt-fiche__entete" style="background: {{ $couleur }}1f;">
                <span class="pt-picto" style="width: 44px; height: 44px; background: #fff; color: {{ $couleur }};">
                    <x-icone :name="$projet->icone ?: 'folder'" :size="24" />
                </span>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 19px; font-weight: 700; color: {{ $couleur }};">{{ $projet->nom }}</div>
                    @if($projet->description)<div style="font-size: 12.5px; color: #495057;">{{ $projet->description }}</div>@endif
                </div>
                <span class="pt-badge" style="background: {{ $etat->couleur() }}; color: #fff;">
                    <x-icone :name="$etat->icone()" :size="13" /> {{ $etat->label() }}
                </span>
            </div>

            @if($projet->raison && $etat->exigeRaison())
                <div class="mb-3" style="padding: 8px 12px; border-radius: 8px; background: {{ $etat->couleur() }}1f; color: {{ $etat->couleur() }}; font-size: 12.5px;">
                    <x-icone name="message-2" :size="14" /> {{ $projet->raison }}
                </div>
            @endif

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 22px;">
                <x-anneau :resume="$resume" :couleur="$couleur" :size="92" />
                <div style="flex: 1; min-width: 220px;">
                    <div class="pt-compteurs">
                        @foreach($statutsCarte as $statut)
                            @if($n = $resume['par_statut'][$statut->value] ?? 0)
                                <span class="pt-compteur"><x-statut-carre :statut="$statut" :size="28" :tip="$n.' '.$statut->accorde($n)" />{{ $n }}</span>
                            @endif
                        @endforeach
                    </div>
                    <div style="margin-top: 8px; font-size: 12.5px; color: #495057;">{{ $resume['texte'] }}</div>

                    <div class="pt-meta">
                        <span class="pt-meta__k">Référent{{ $referents->count() > 1 ? 's' : '' }}</span>
                        <span class="pt-meta__k">Impliqués</span>
                        <span class="pt-meta__k">Date limite</span>
                        <span>
                            @forelse($referents as $u)
                                <x-avatars :users="[$u]" :size="26" /> <b>{{ $u->name }}</b>@if(!$loop->last), @endif
                            @empty
                                <span style="color: #9aa0ac;">—</span>
                            @endforelse
                        </span>
                        <span>
                            @if($impliques->isEmpty())<span style="color: #9aa0ac;">—</span>@else<x-avatars :users="$impliques" :size="26" :max="6" />@endif
                        </span>
                        <span><x-echeance :item="$projet" :jours="true" /></span>
                    </div>

                    <div style="margin-top: 8px; font-size: 11.5px; color: #9aa0ac;">
                        Créé{{ $projet->createur ? ' par '.$projet->createur->name : '' }} le {{ $projet->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>

        @can('update', $projet)
            <div class="pt-panneau mb-3">
                <h3>Changer l'état du projet</h3>
                <form action="{{ route('projets.etat', $projet) }}" method="POST" data-ajax-form data-hote="projet" data-apres="recharger" novalidate>
                    @csrf
                    <input type="hidden" name="etat" value="{{ $etat->value }}" data-etat-valeur data-courant="{{ $etat->value }}">
                    <div class="alert alert-danger d-none" data-erreurs role="alert"></div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach($etats as $e)
                            @php $verrouille = $e === \App\Enums\ProjetEtat::Termine && $e !== $etat && $projet->tachesOuvertes() > 0; @endphp
                            <button type="button" class="pt-pastille {{ $e === $etat ? 'on' : '' }}" data-choix-etat="{{ $e->value }}"
                                    @if($verrouille) disabled title="{{ $projet->phraseTachesOuvertes() }}" @endif
                                    data-exige="{{ $e->exigeRaison() ? 1 : 0 }}" style="--pc: {{ $e->couleur() }};">
                                <x-statut-carre :statut="$e" :size="22" />{{ $e->label() }}
                            </button>
                        @endforeach
                    </div>
                    <div class="d-none mb-2" data-raison-bloc>
                        <textarea name="raison" class="form-control" rows="2" maxlength="1000" placeholder="Pourquoi ? (obligatoire)"></textarea>
                    </div>
                    <button type="submit" class="btn pt-btn-doux d-none" data-enregistrer-etat>Enregistrer l'état</button>
                    <div class="form-hint mt-1">
                        @if($projet->etat !== \App\Enums\ProjetEtat::Termine && $projet->tachesOuvertes() > 0)
                            « Terminé » est indisponible : {{ $projet->phraseTachesOuvertes() }}.
                        @else
                            « Terminé » n'est possible que lorsque toutes les tâches sont terminées ou annulées.
                        @endif
                    </div>
                </form>
            </div>
        @endcan

        <div class="d-flex align-items-center justify-content-between mb-2">
            <span style="font-weight: 700; font-size: 15px;">Tâches</span>
            @can('create', \App\Models\Tache::class)
                <button type="button" class="btn pt-btn-doux" data-tache-nouvelle data-projet="{{ $projet->id }}">+ Ajouter une tâche</button>
            @endcan
        </div>
        @if($taches->isEmpty())
            <div class="pt-vide">Aucune tâche dans ce projet.</div>
        @else
            <div class="pt-grille-mini">
                @foreach($taches as $tache)
                    @include('taches._mini', ['tache' => $tache])
                @endforeach
            </div>
        @endif

        <div class="row g-3 mt-2">
            <div class="col-12">
                <div class="pt-panneau">
                    <h3><x-icone name="clock" :size="16" /> Historique du projet <span style="font-weight: 400; color: #9aa0ac;">({{ $historiques->count() }})</span></h3>
                    @if($historiques->isEmpty())
                        <div style="font-size: 12px; color: #9aa0ac;">Aucun changement enregistré.</div>
                    @else
                        <div class="pt-histo" tabindex="0" aria-label="Historique du projet, défilable">
                            @foreach($historiques as $h)
                                <div class="pt-histo__ligne">
                                    <span class="pt-histo__puce" style="background: {{ $h->type === 'tache' ? '#4299e1' : '#f59f00' }};" title="{{ $h->type === 'tache' ? 'Tâche' : 'Projet' }}"></span>
                                    <div style="min-width: 0;">
                                        @if($h->tache_id)
                                            <a href="#" data-tache="{{ $h->tache_id }}" onclick="event.preventDefault()" class="pt-histo__texte">{{ $h->libelle }}</a>
                                        @else
                                            <span class="pt-histo__texte">{{ $h->libelle }}</span>
                                        @endif
                                        <div class="pt-histo__meta">{{ $h->user?->name ?? 'Ancien utilisateur' }} · {{ $h->created_at->format('d/m/Y à H:i') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="pt-panneau">
                    <h3><x-icone name="messages" :size="16" /> Commentaires</h3>
                    @include('commentaires._liste', ['commentable' => $projet, 'routeAjout' => route('projets.commentaires.store', $projet), 'hote' => 'projet'])
                </div>
            </div>
            <div class="col-md-6">
                <div class="pt-panneau">
                    <h3><x-icone name="paperclip" :size="16" /> Liens et documents utiles</h3>
                    @forelse($projet->liens as $lien)
                        <div style="margin-bottom: 6px; font-size: 12.5px;">
                            <a href="{{ $lien->url }}" target="_blank" rel="noopener noreferrer">{{ $lien->libelle ?: $lien->url }}</a>
                        </div>
                    @empty
                        <div style="font-size: 12px; color: #9aa0ac;">Aucun lien.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @include('taches._modal-host')
    @include('projets._modal-host')
</x-app-layout>
