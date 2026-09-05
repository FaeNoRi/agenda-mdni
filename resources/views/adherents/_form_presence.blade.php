<form id="adherentEditForm" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Nom Prénom</label>
            <input type="text" name="nom_adh" class="form-control" required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Situation professionnelle</label>
            <select name="situation_adh" class="form-select" required>
                @php
                    $situations = [
                        'Étudiant', 'Bénévole', "Chef d'entreprise", 'Salarié',
                        'Particulier', 'Porteur de projet', 'MDNI'
                    ];
                @endphp
                @foreach($situations as $situation)
                    <option value="{{ $situation }}">{{ $situation }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Domaine</label>
            <input type="text" name="dom_adh" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Fin d'adhésion</label>
            <input type="date" name="date_adh" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Photo (fichier)</label>
            <input type="file" name="photo_adh" class="form-control" accept="image/*">
            <small class="form-hint">JPG, PNG, WEBP — 4 Mo max.</small>
        </div>

        <div class="col-md-6 mb-3">
            <div class="alert-danger text-danger">
                <label class="form-label">Mot de passe administrateur</label>
                <input type="password" name="admin_password" class="form-control" required>
                <small id="adminPasswordHelp" class="text-danger-lt">Obligatoire pour valider la modification.</small>
            </div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-4 px-3 mt-2">
            <div class="form-check">
                <input type="hidden" name="isCGU" value="0">
                <input class="form-check-input" type="checkbox" name="isCGU" id="isCGU" value="1">
                <label class="form-check-label" for="isCGU">CGU MDNI acceptées</label>
            </div>

            <div class="form-check">
                <input type="hidden" name="isPresent" value="0">
                <input class="form-check-input" type="checkbox" name="isPresent" id="isPresent" value="1">
                <label class="form-check-label" for="isPresent">Présent à MDNI</label>
            </div>
        </div>
    </div>

    <div class="text-end mt-4">
        <button id="formSubmitBtn" type="submit" class="btn btn-info d-inline-flex align-items-center">
            <svg id="iconEdit" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                class="icon icon-tabler icons-tabler-outline icon-tabler-edit me-2">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                <path d="M16 5l3 3"/>
            </svg>

            <svg id="iconAdd" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                class="icon icon-tabler icon-tabler-plus me-2 d-none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M12 5v14" />
                <path d="M5 12h14" />
            </svg>

            <span id="formSubmitLabel">Modifier</span>
        </button>
    </div>

</form>