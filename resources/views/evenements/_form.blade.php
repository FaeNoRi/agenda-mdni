{{-- <pre>{!! json_encode($formData, JSON_PRETTY_PRINT) !!}</pre> --}}

<form method="POST" action="{{ $route }}" x-data='() => ({
        step: 1,
        form: @json($formData)
    })' x-init="
        if (form.animateurs.length === 0) form.animateurs.push('');
        if (form.salles.length === 0) form.salles.push('');
    ">

    @csrf

    @if(isset($evenement))
    @method('PUT')
    @endif

    {{-- ÉTAPE 1 – Détails de l'événement --}}
    <div x-show="step === 1">

        {{-- Nom --}}
        <div class="mb-3">
            <label class="form-label">Nom de l'événement *</label>
            <input type="text" name="nom_event" class="form-control" x-model="form.nom_event" required>
        </div>

        <div class="row g-2 mb-3">
            {{-- Commanditaire --}}
            <div class="col-md-6">
                <label class="form-label">Client *</label>
                <input type="text" name="commanditaire_event" class="form-control" x-model="form.commanditaire_event" required>
            </div>

            {{-- Nombre de participants --}}
            <div class="col-md-6">
                <label class="form-label">Nombre de participants</label>
                <input type="number" name="nbpart" class="form-control" x-model="form.nbpart" value="0" min="0">
            </div>
        </div>

        {{-- Type d'événement + Type de public (conditionnel) --}}
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Type d'événement *</label>
                <select name="type_event" class="form-select" x-model="form.type_event" required>
                    <option value="">-- Sélectionner --</option>
                    <option>Atelier</option>
                    <option>Réunion</option>
                    <option>Location</option>
                    <option>Permanence</option>
                    <option>Événement</option>
                    <option>RDV</option>
                    <option>Interne</option>
                    <option>Fonctionnement</option>
                    <option>FN-RDV</option>
                    <option>FN-Atelier</option>
                    <option>Exceptionnel</option>
                    <option>Annule</option>
                </select>
            </div>

            <div class="col-md-6" x-show="form.type_event === 'Atelier'" x-transition>
                <label class="form-label">Type de public *</label>
                <select name="type_public" class="form-select" x-model="form.type_public">
                    <option>Autres</option>
                    <option>École Primaire</option>
                    <option>Collège</option>
                    <option>Lycée</option>
                    <option>Post-bac</option>
                    <option>CLSH</option>
                    <option>Grand Public</option>
                </select>
            </div>
            <template x-if="form.type_event !== 'Atelier'">
                <input type="hidden" name="type_public" value="Autres">
            </template>
            <template x-effect="if (form.type_event !== 'Atelier') form.type_public = 'Autres'"></template>
        </div>

        {{-- Devis + Num devis --}}
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Devis ?</label>
                <select name="devis" class="form-select" x-model="form.devis">
                    <option>Non</option>
                    <option>Oui</option>
                    <option>A faire</option>
                </select>
            </div>

            <div class="col-md-6" x-show="form.devis === 'Oui'" x-transition>
                <label class="form-label">Numéro de devis</label>
                <input type="text" name="numdevis" class="form-control" x-model="form.numdevis">
            </div>
            <template x-if="form.devis !== 'Oui'">
                <input type="hidden" name="numdevis" value="">
            </template>
            <template x-effect="if (form.devis !== 'Oui') form.numdevis = null"></template>
        </div>

        {{-- Facture + Num facture --}}
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Facture ?</label>
                <select name="facture" class="form-select" x-model="form.facture">
                    <option>Non</option>
                    <option>Oui</option>
                    <option>A faire</option>
                </select>
            </div>

            <div class="col-md-6" x-show="form.facture === 'Oui'" x-transition>
                <label class="form-label">Numéro de facture</label>
                <input type="text" name="numfact" class="form-control" x-model="form.numfact">
            </div>
            <template x-if="form.facture !== 'Oui'">
                <input type="hidden" name="numfact" value="">
            </template>
            <template x-effect="if (form.facture !== 'Oui') form.numfact = null"></template>
        </div>

        {{-- Règlement ? + Type + Numéro --}}
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Règlement ?</label>
                <select name="reglement" class="form-select" x-model="form.reglement">
                    <option>Non</option>
                    <option>Oui</option>
                    <option>A faire</option>
                </select>
            </div>

            <div class="col-md-4" x-show="form.reglement === 'Oui'" x-transition>
                <label class="form-label">Type de règlement</label>
                <select name="type_reglement" class="form-select" x-model="form.type_reglement">
                    <option value="">--</option>
                    <option>CB</option>
                    <option>CH</option>
                    <option>ESP</option>
                </select>
            </div>
            <template x-if="form.reglement !== 'Oui'">
                <input type="hidden" name="type_reglement" value="">
            </template>

            <div class="col-md-4" x-show="form.reglement === 'Oui'" x-transition>
                <label class="form-label">Numéro de règlement</label>
                <input type="text" name="num_reglement" class="form-control" x-model="form.num_reglement">
            </div>
            <template x-if="form.reglement !== 'Oui'">
                <input type="hidden" name="num_reglement" value="">
            </template>

            <template x-effect="if (form.reglement !== 'Oui') {
                form.type_reglement = '';
                form.num_reglement = '';}">
            </template>
        </div>

        {{-- Description --}}
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="desc_event" class="form-control" rows="3" x-model="form.desc_event"></textarea>
        </div>

        {{-- Suivant --}}
        <div class="d-flex justify-content-between mt-4">
            <p class="text-muted small" style="margin: auto;padding-left: 85px;">
                Dernière modification par : {{ $evenement->auteur ?? 'Inconnu' }}
            </p>
            <button type="button" class="btn btn-primary" @click="step = 2">Suivant</button>
        </div>
    </div>

    {{-- ÉTAPE 2 – Réservation & Affectations --}}
    <div x-show="step === 2" x-cloak>
        {{-- Date/heure début/fin --}}
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label">Date et heure de début *</label>
                <input type="datetime-local" name="date_heure_debut" class="form-control" x-model="form.date_heure_debut" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date et heure de fin *</label>
                <input type="datetime-local" name="date_heure_fin" class="form-control" x-model="form.date_heure_fin" required>
            </div>
        </div>

        {{-- Animateurs et Salles côte à côte --}}
        <div class="row mb-3">
            <div class="col-md-6 border-end">
                <label class="form-label d-flex justify-content-between align-items-center">
                    Animateurs *
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="form.animateurs.push(''); $nextTick(() => checkDisponibilites())">Ajouter un animateur
                    </button>

                </label>
                <template x-for="(user, index) in form.animateurs" :key="index">
                    <div class="dispo-row mb-2">
                        <div class="d-flex align-items-center">
                            <select class="form-select me-2" name="users[]" data-type="user" x-model="form.animateurs[index]">
                                @foreach ($users as $groupe => $userList)
                                <optgroup label="{{ $groupe }}">
                                    @foreach ($userList as $userItem)
                                    <option value="{{ $userItem->id }}">{{ $userItem->name }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-danger" @click="form.animateurs.splice(index, 1)">-</button>
                        </div>
                        <small class="dispo-reason text-danger d-none"></small>
                    </div>
                </template>
            </div>

            <div class="col-md-6 ps-3">
                <label class="form-label d-flex justify-content-between align-items-center">
                    Salles
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="form.salles.push(''); $nextTick(() => checkDisponibilites())">Ajouter une salle
                    </button>
                </label>
                <template x-for="(salle, index) in form.salles" :key="index">
                    <div class="dispo-row mb-2">
                        <div class="d-flex align-items-center">
                            <select class="form-select me-2" name="salles[]" data-type="salle" x-model="form.salles[index].id">
                                @foreach ($salles as $type => $sallesGroup)
                                <optgroup label="{{ $type }}">
                                    @foreach ($sallesGroup as $salleItem)
                                    <option value="{{ $salleItem->id }}">{{ $salleItem->nom_salle }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-danger" @click="form.salles.splice(index, 1)">-</button>
                        </div>
                        <small class="dispo-reason text-danger d-none"></small>
                    </div>
                </template>
            </div>
        </div>

        {{-- Matériels --}}
        <div class="mb-3">
            <label class="form-label d-flex justify-content-between align-items-center">
                Matériels
                <button type="button" class="btn btn-sm btn-outline-primary" @click="form.materiels.push({ id: '', quantite: 1 })">Ajouter du matériel</button>
            </label>
            <template x-for="(m, index) in form.materiels" :key="index">
                <div class="row g-2 mb-2 align-items-end">
                    <div class="col-md-6">
                        <select class="form-select" name="materiels[]" x-model="form.materiels[index].id">
                            @foreach ($materiels as $materielItem)
                            <option value="{{ $materielItem->id }}">{{ $materielItem->nom_mat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="quantites[]" class="form-control" min="1" x-model="form.materiels[index].quantite">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger" @click="form.materiels.splice(index, 1)">-</button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Objets --}}
        <div class="mb-3">
            <label class="form-label">Objets à produire ?</label>
            <select name="objet" class="form-select" x-model="form.objet">
                <option>Non</option>
                <option>Oui</option>
            </select>
        </div>

        <div x-show="form.objet === 'Oui'" x-transition>
            <label class="form-label d-flex justify-content-between align-items-center">
                Objets
                <button type="button" class="btn btn-sm btn-outline-primary" @click="form.objets.push({ id: '', etat: 'A faire' })">Ajouter un objet</button>
            </label>
            <template x-for="(o, index) in form.objets" :key="index">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <select class="form-select" :name="'objets[' + index + ']'" x-model="form.objets[index].id">
                            <option value="">Sélectionner un objet</option>
                            @foreach ($objets as $objet)
                            <option value="{{ $objet->id }}">{{ $objet->nom_obj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" :name="'etat[' + index + ']'" x-model="form.objets[index].etat">
                            <option value="A faire">A faire</option>
                            <option value="Fait">Fait</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-danger" @click="form.objets.splice(index, 1)">-</button>
                    </div>
                </div>
            </template>
        </div>

        <template x-effect="if (form.objet !== 'Oui') { form.objets = [];}"></template>

        {{-- Soumettre --}}
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" @click="step = 1">Précédent</button>
            <p class="text-muted small" style="margin: auto;">
                Dernière modification par : {{ $evenement->auteur ?? 'Inconnu' }}
            </p>
            <button type="submit" class="btn btn-success">Enregistrer l'événement</button>
        </div>
    </div>

</form>
