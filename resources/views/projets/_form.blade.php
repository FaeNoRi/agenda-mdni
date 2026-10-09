{{--
    Formulaire de création / modification d'un projet, affiché dans la fenêtre modale (envoi en Ajax,
    voir _modal-host). Variables : $projet, $personnes, $referents (ids), $palette,
    $icones, $etatsCreation.
--}}
@php
    $edition = $projet->exists;
    $action = $edition ? route('projets.update', $projet) : route('projets.store');
    $couleurActuelle = $projet->couleur ?: $palette[0];
    $iconeActuelle = $projet->icone ?: 'folder';
@endphp

<form action="{{ $action }}" method="POST" data-ajax-form data-hote="projet" data-apres="ouvrir-projet" novalidate>
    @csrf
    @if($edition) @method('PUT') @endif

    <div class="modal-header">
        <h5 class="modal-title">{{ $edition ? 'Modifier le projet' : 'Nouveau projet' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
    </div>

    <div class="modal-body">
        <div class="alert alert-danger d-none" data-erreurs role="alert"></div>

        <div class="mb-3">
            <label class="form-label required" for="projetNom">Nom du projet</label>
            <input type="text" class="form-control" id="projetNom" name="nom" value="{{ $projet->nom }}" maxlength="150" autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label" for="projetDescription">Description</label>
            <textarea class="form-control" id="projetDescription" name="description" rows="3" maxlength="2000">{{ $projet->description }}</textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="projetDate">Date limite</label>
                <input type="date" class="form-control" id="projetDate" name="date_limite" value="{{ $projet->date_limite?->toDateString() }}">
            </div>
            @unless($edition)
                <div class="col-md-6">
                    <label class="form-label">État de départ</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($etatsCreation as $e)
                            <label class="pt-pick-etat">
                                <input type="radio" name="etat" value="{{ $e->value }}" class="visually-hidden" @checked($projet->etat === $e)>
                                <span class="pt-pastille" style="--pc: {{ $e->couleur() }};"><x-statut-carre :statut="$e" :size="22" />{{ $e->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endunless
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Couleur</label>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($palette as $c)
                        <label class="pt-pick" title="{{ $c }}">
                            <input type="radio" name="couleur" value="{{ $c }}" class="visually-hidden" @checked($couleurActuelle === $c)>
                            <span class="pt-pick__pastille" style="background: {{ $c }};"></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Icône</label>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($icones as $nom)
                        <label class="pt-pick">
                            <input type="radio" name="icone" value="{{ $nom }}" class="visually-hidden" @checked($iconeActuelle === $nom)>
                            <span class="pt-pick__icone"><x-icone :name="$nom" :size="18" /></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label required">Référent(s)</label>
            <div class="form-selectgroup form-selectgroup-pills">
                @foreach($personnes as $u)
                    <label class="form-selectgroup-item">
                        <input type="checkbox" name="referents[]" value="{{ $u->id }}" class="form-selectgroup-input" @checked(in_array($u->id, $referents))>
                        <span class="form-selectgroup-label">{{ $u->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-hint">Les personnes impliquées s'ajoutent automatiquement : ce sont les responsables des tâches du projet.</div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary ms-auto">{{ $edition ? 'Enregistrer' : 'Créer le projet' }}</button>
    </div>
</form>
