{{--
    Formulaire de création / modification d'une tâche, affiché dans la fenêtre modale (envoi en Ajax,
    voir _modal-host). Variables : $tache (nouvelle ou existante), $projets, $personnes, $choisis (ids), $liens.
--}}
@php
    $edition = $tache->exists;
    $action = $edition ? route('taches.update', $tache) : route('taches.store');
@endphp

<form action="{{ $action }}" method="POST" data-ajax-form data-apres="{{ $edition ? 'rouvrir' : 'ouvrir-nouvelle' }}" data-id="{{ $tache->id }}" novalidate>
    @csrf
    @if($edition) @method('PUT') @endif

    <div class="modal-header">
        <h5 class="modal-title">{{ $edition ? 'Modifier la tâche' : 'Nouvelle tâche' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
    </div>

    <div class="modal-body">
        <div class="alert alert-danger d-none" data-erreurs role="alert"></div>

        <div class="mb-3">
            <label class="form-label required" for="tacheTitre">Intitulé</label>
            <input type="text" class="form-control" id="tacheTitre" name="titre" value="{{ $tache->titre }}" maxlength="190" autocomplete="off">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="tacheProjet">Projet</label>
                <select class="form-select" id="tacheProjet" name="projet_id">
                    <option value="">Sans projet</option>
                    @foreach($projets as $p)
                        <option value="{{ $p->id }}" @selected((int) $tache->projet_id === $p->id)>
                            {{ $p->nom }}{{ $p->etat->estOuvert() ? '' : ' ('.mb_strtolower($p->etat->label()).')' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label required" for="tacheDate">Date limite</label>
                <input type="date" class="form-control" id="tacheDate" name="date_limite" value="{{ $tache->date_limite?->toDateString() }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label required">Responsable(s)</label>
            <div class="form-selectgroup form-selectgroup-pills">
                @foreach($personnes as $u)
                    <label class="form-selectgroup-item">
                        <input type="checkbox" name="responsables[]" value="{{ $u->id }}" class="form-selectgroup-input" @checked(in_array($u->id, $choisis))>
                        <span class="form-selectgroup-label">{{ $u->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="tacheDetails">Détails</label>
            <textarea class="form-control" id="tacheDetails" name="details" rows="3" maxlength="5000">{{ $tache->details }}</textarea>
        </div>

        <div class="mb-1" x-data="{ liens: {{ \Illuminate\Support\Js::from($liens) }} }">
            <label class="form-label">Liens et documents utiles</label>
            <template x-for="(lien, i) in liens" :key="i">
                <div class="row g-2 mb-2">
                    <div class="col-md-4"><input type="text" class="form-control" :name="`liens[${i}][libelle]`" x-model="lien.libelle" placeholder="Nom (facultatif)" maxlength="150"></div>
                    <div class="col"><input type="url" class="form-control" :name="`liens[${i}][url]`" x-model="lien.url" placeholder="https://…" maxlength="500"></div>
                    <div class="col-auto"><button type="button" class="btn btn-outline-danger btn-icon" @click="liens.splice(i, 1)" aria-label="Retirer ce lien">×</button></div>
                </div>
            </template>
            <button type="button" class="btn btn-sm pt-btn-doux" @click="liens.push({ libelle: '', url: '' })">+ Ajouter un lien</button>
        </div>
    </div>

    <div class="modal-footer">
        @if($edition)
            <button type="button" class="btn btn-link link-secondary" data-ouvrir-tache="{{ $tache->id }}">Annuler</button>
        @else
            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Annuler</button>
        @endif
        <button type="submit" class="btn btn-primary ms-auto">{{ $edition ? 'Enregistrer' : 'Créer la tâche' }}</button>
    </div>
</form>
