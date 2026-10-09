{{--
    Commentaires d'une tâche ou d'un projet : liste, modification/suppression des siens, formulaire d'ajout.
    Variables : $commentable (Tache ou Projet, commentaires.user chargés), $routeAjout (adresse d'envoi),
                $hote ('projet' pour la fiche projet, sinon la modale des tâches).
    Le comportement (modifier, confirmer la suppression) est dans taches/_modal-host.
--}}
@php $hote = $hote ?? null; @endphp
<div data-commentaires>
    @forelse($commentable->commentaires as $c)
        @php $modifie = $c->updated_at->gt($c->created_at); @endphp
        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
            @if($c->user)<x-avatars :users="[$c->user]" :size="26" />@endif
            <div style="flex: 1; min-width: 0;">
                <div style="padding: 6px 9px; border-radius: 0 10px 10px 10px; background: #f6f8fb; font-size: 12.5px;" data-commentaire-vue>
                    <b>{{ $c->user?->name ?? 'Ancien utilisateur' }}</b>
                    <span style="color: #9aa0ac;">{{ $c->created_at->format('d/m/Y à H:i') }}@if($modifie) · modifié @endif</span><br>
                    <span style="white-space: pre-line; overflow-wrap: anywhere;">{{ $c->contenu }}</span>
                </div>

                @can('update', $c)
                    <form action="{{ route('commentaires.update', $c) }}" method="POST" class="d-none mt-1" data-commentaire-edition
                          data-ajax-form @if($hote) data-hote="{{ $hote }}" @endif data-id="{{ $commentable->getKey() }}" novalidate>
                        @csrf @method('PUT')
                        <div class="alert alert-danger d-none py-1 px-2 mb-1" data-erreurs role="alert"></div>
                        <textarea name="contenu" class="form-control form-control-sm mb-1" rows="2" maxlength="2000">{{ $c->contenu }}</textarea>
                        <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                        <button type="button" class="btn btn-sm btn-link link-secondary" data-commentaire-annuler>Annuler</button>
                    </form>
                @endcan

                @if(auth()->user()->can('update', $c) || auth()->user()->can('delete', $c))
                    <div class="mt-1" style="font-size: 11.5px;" data-commentaire-actions>
                        @can('update', $c)
                            <button type="button" class="btn btn-link btn-sm p-0 me-2" data-commentaire-modifier>Modifier</button>
                        @endcan
                        @can('delete', $c)
                            <form action="{{ route('commentaires.destroy', $c) }}" method="POST" class="d-inline"
                                  data-ajax-form @if($hote) data-hote="{{ $hote }}" @endif data-id="{{ $commentable->getKey() }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" data-commentaire-suppr>Supprimer</button>
                            </form>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div style="font-size: 12px; color: #9aa0ac;" class="mb-2">Aucun commentaire.</div>
    @endforelse

    @can('comment', $commentable)
        <form action="{{ $routeAjout }}" method="POST" class="mt-2" data-ajax-form @if($hote) data-hote="{{ $hote }}" @endif
              data-id="{{ $commentable->getKey() }}" novalidate>
            @csrf
            <div class="alert alert-danger d-none py-1 px-2 mb-1" data-erreurs role="alert"></div>
            <textarea name="contenu" class="form-control mb-1" rows="2" maxlength="2000" placeholder="Ajouter un commentaire…"></textarea>
            <button type="submit" class="btn btn-sm btn-primary">Commenter</button>
        </form>
    @endcan
</div>
