{{--
    Contenu de la fenêtre « Détail tâche » (même structure que « Détail événement »).
    Variables : $tache (projet, responsables, createur, liens, historiques.user, commentaires.user chargés),
                $referents (collection d'utilisateurs), $statuts (cases de TacheStatut).
--}}
@php
    $s = $tache->statut;
    $projet = $tache->projet;
    $couleur = $projet?->couleur ?: '#667382';
    $retard = $tache->joursDeRetard();
    $moiResponsable = $tache->responsables->contains('id', auth()->id());
    $impliques = $tache->personnesImpliquees();
@endphp

<div class="modal-header">
    <h5 class="modal-title">Détail tâche</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<div class="modal-body">
    @can('delete', $tache)
        <div class="alert alert-danger d-none" data-suppr-confirm role="alert">
            <div class="d-flex align-items-center gap-2">
                <span style="flex: 1;">Supprimer définitivement cette tâche, son historique et ses commentaires ?</span>
                <button type="button" class="btn pt-btn-doux pt-btn-danger" data-supprimer="{{ $tache->id }}">Oui, supprimer</button>
                <button type="button" class="btn btn-sm btn-link link-secondary" data-suppr-annuler>Annuler</button>
            </div>
        </div>
    @endcan

    {{-- Bandeau : identifiant, statut, titre ; pastilles à droite --}}
    <div class="w-full p-3 mb-4 rounded d-flex justify-content-between align-items-center gap-3" style="background: {{ $couleur }}1f;">
        <div style="min-width: 0;">
            <span class="pt-badge me-1 align-text-top" style="background: {{ $couleur }}1f; color: {{ $couleur }};">ID:{{ $tache->id }}</span>
            <span class="pt-badge me-1 align-text-top" style="background: {{ $s->couleur() }}; color: #fff;">
                <x-icone :name="$s->icone()" :size="13" /> {{ $s->label() }}
            </span>
            <span class="h4 mb-0" style="color: {{ $couleur }};">{{ $tache->titre }}</span>
        </div>
        <span style="display: inline-flex; gap: 6px;">
            @if($moiResponsable)
                <span class="pt-carre" data-tip="Vous êtes responsable" style="width: 30px; height: 30px; background: {{ $couleur }};"><x-icone name="user-check" :size="18" /></span>
            @endif
            @if($retard)
                <span class="pt-carre" data-tip="En retard de {{ $retard }} j" style="width: 30px; height: 30px; background: #d63939;"><x-icone name="alert-triangle" :size="18" /></span>
            @endif
            @if($tache->liens->isNotEmpty())
                <span class="pt-carre" data-tip="Liens et documents" style="width: 30px; height: 30px; background: {{ $couleur }};"><x-icone name="paperclip" :size="18" /></span>
            @endif
        </span>
    </div>

    <div class="row gx-4 mb-4">
        <div class="col-md-6">
            <p class="mb-1"><strong>Projet</strong></p>
            <p class="mb-0">
                @if($projet)
                    <a href="{{ route('projets.show', $projet) }}" class="d-inline-flex align-items-center gap-1" style="color: {{ $couleur }}; font-weight: 600;">
                        <x-icone :name="$projet->icone ?: 'folder'" :size="15" /> {{ $projet->nom }}
                    </a>
                @else
                    Sans projet
                @endif
            </p>
        </div>
        <div class="col-md-6">
            <p class="mb-1"><strong>Responsable{{ $tache->responsables->count() > 1 ? 's' : '' }}</strong></p>
            <p class="mb-0">{{ $tache->responsables->pluck('name')->join(', ') ?: 'Aucun' }}</p>
        </div>
    </div>

    <div class="row gx-4 mb-4">
        <div class="col-md-6">
            <p class="mb-1"><strong>Date limite</strong></p>
            <p class="mb-0">
                {{ \Illuminate\Support\Str::title($tache->date_limite->translatedFormat('l d F Y')) }}
                <x-echeance :item="$tache" />
            </p>
        </div>
        <div class="col-md-6">
            <p class="mb-1"><strong>Créée par</strong></p>
            <p class="mb-0">{{ $tache->createur?->name ?? 'Ancien utilisateur' }}, le {{ $tache->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    @if($tache->recurrence)
        <div class="mb-4 p-2 rounded d-flex align-items-center gap-2" style="background: #f1f3f5; font-size: 13px;">
            <x-icone name="repeat" :size="15" />
            <span>
                <strong>{{ $tache->recurrence->libelle() }}</strong> jusqu'au {{ $tache->recurrence->date_fin->format('d/m/Y') }}
                @if($tache->recurrence->arretee) · <span style="color: #d63939;">série arrêtée</span>
                @else <span style="color: #667382;">· l'occurrence suivante est créée quand celle-ci est terminée</span>@endif
            </span>
        </div>
    @endif

    @if($tache->raison && $s->exigeRaison())
        <div class="mb-4 p-2 rounded" style="background: {{ $s->couleur() }}1f; color: {{ $s->couleur() }}; font-size: 13px;">
            <x-icone name="message-2" :size="14" /> <strong>{{ $s->label() }} :</strong> {{ $tache->raison }}
        </div>
    @endif

    @can('changeStatus', $tache)
        <div class="mb-4">
            <p class="mb-2"><strong>Changer le statut</strong></p>
            <form action="{{ route('taches.statut', $tache) }}" method="POST" data-ajax-form data-apres="rouvrir" data-id="{{ $tache->id }}" novalidate>
                @csrf
                <input type="hidden" name="statut" value="{{ $s->value }}" data-statut-valeur data-courant="{{ $s->value }}">
                <div class="alert alert-danger d-none" data-erreurs role="alert"></div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($statuts as $st)
                        <button type="button" class="pt-pastille {{ $st === $s ? 'on' : '' }}" data-choix-statut="{{ $st->value }}"
                                data-exige="{{ $st->exigeRaison() ? 1 : 0 }}" style="--pc: {{ $st->couleur() }};">
                            <x-statut-carre :statut="$st" :size="22" />{{ $st->label() }}
                        </button>
                    @endforeach
                </div>
                <div class="d-none mb-2" data-raison-bloc>
                    <textarea name="raison" class="form-control" rows="2" maxlength="1000" placeholder="Pourquoi ? (obligatoire)"></textarea>
                </div>
                @if($tache->recurrence)
                    <div class="d-none mb-2" data-portee-bloc>
                        <div class="form-label mb-1">Cette tâche est récurrente :</div>
                        <label class="form-check mb-1"><input type="radio" class="form-check-input" name="portee" value="occurrence" checked>
                            <span class="form-check-label">Annuler cette occurrence seulement (la suivante sera créée)</span></label>
                        <label class="form-check mb-0"><input type="radio" class="form-check-input" name="portee" value="serie">
                            <span class="form-check-label">Annuler toutes les occurrences jusqu'au {{ $tache->recurrence->date_fin->format('d/m/Y') }}</span></label>
                    </div>
                @endif
                <button type="submit" class="btn pt-btn-doux d-none" data-enregistrer-statut>Enregistrer le statut</button>
            </form>
        </div>
    @endcan

    @if($tache->details)
        <div class="mb-4">
            <p class="mb-1"><strong>Détails</strong></p>
            <p class="text-muted mb-0" style="white-space: pre-line;">{{ $tache->details }}</p>
        </div>
    @endif

    {{-- Toutes les personnes concernées : responsables et référents --}}
    <div class="mb-4">
        <p class="mb-2"><strong>Personnes impliquées</strong></p>
        <div class="d-flex flex-wrap gap-2">
            @forelse($impliques as $u)
                @php
                    $roles = array_filter([
                        $tache->responsables->contains('id', $u->id) ? 'Responsable' : null,
                        $referents->contains('id', $u->id) ? 'Référent' : null,
                    ]);
                @endphp
                <span class="d-inline-flex align-items-center gap-2 pe-2" style="border: 1px solid #e6e7e9; border-radius: 999px; font-size: 12.5px;">
                    <x-avatars :users="[$u]" :size="26" />
                    <span><strong>{{ $u->name }}</strong> <span style="color: #667382;">· {{ implode(' et ', $roles) }}</span></span>
                </span>
            @empty
                <span style="color: #9aa0ac; font-size: 12.5px;">Personne</span>
            @endforelse
        </div>
    </div>

    @if($tache->liens->isNotEmpty())
        <div class="mb-4">
            <p class="mb-1"><strong>Liens et documents utiles</strong></p>
            @foreach($tache->liens as $lien)
                <div style="font-size: 12.5px;"><a href="{{ $lien->url }}" target="_blank" rel="noopener noreferrer">{{ $lien->libelle ?: $lien->url }}</a></div>
            @endforeach
        </div>
    @endif

    {{-- Historique des statuts --}}
    <div class="mb-4">
        <p class="mb-2"><strong>Historique</strong></p>
        @foreach($tache->historiques as $h)
            <div class="d-flex align-items-start gap-2 mb-2" style="font-size: 12.5px;">
                <x-statut-carre :statut="$h->statut" :size="20" />
                <div>
                    <strong>{{ $h->statut->label() }}</strong>
                    <span style="color: #667382;">· {{ $h->user?->name ?? 'Ancien utilisateur' }}, le {{ $h->created_at->format('d/m/Y') }}</span>
                    @if($h->raison)<div style="color: #667382;">{{ $h->raison }}</div>@endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Commentaires --}}
    <div class="mb-2">
        <p class="mb-2"><strong>Commentaires</strong></p>
        @include('commentaires._liste', ['commentable' => $tache, 'routeAjout' => route('taches.commentaires.store', $tache)])
    </div>
</div>

<div class="modal-footer">
    @can('delete', $tache)
        <button type="button" class="btn pt-btn-doux pt-btn-danger" data-supprimer-demande>Supprimer</button>
    @endcan
    <p class="text-muted small mb-0" style="margin: auto;">
        Dernière modification le <b>{{ $tache->updated_at->translatedFormat('d F Y') }}</b>
    </p>
    @can('update', $tache)
        <button type="button" class="btn pt-btn-doux" data-modifier-tache="{{ $tache->id }}">Modifier</button>
    @endcan
    <button type="button" class="btn pt-btn-doux pt-btn-neutre" data-bs-dismiss="modal">Fermer</button>
</div>
