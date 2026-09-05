<form method="POST" action="{{ isset($adherent) ? route('adherents.update', $adherent) : route('adherents.store') }}" enctype="multipart/form-data">
    @csrf
    @if(isset($adherent))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Nom Prénom</label>
            <input type="text" name="nom_adh" class="form-control" value="{{ old('nom_adh', $adherent->nom_adh ?? '') }}" required>
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
                    <option value="{{ $situation }}"
                        {{ old('situation_adh', $adherent->situation_adh ?? '') === $situation ? 'selected' : '' }}>
                        {{ $situation }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Domaine</label>
            <input type="text" name="dom_adh" class="form-control" value="{{ old('dom_adh', $adherent->dom_adh ?? '') }}">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Date d'adhésion</label>
            <input type="date" name="date_adh" class="form-control"
                   value="{{ old('date_adh', isset($adherent) ? $adherent->date_adh->format('Y-m-d') : '') }}">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Photo (fichier)</label>
            <input type="file" name="photo_adh" class="form-control">
        </div>

        <div class="col-12 d-flex flex-wrap gap-4 px-3 mt-2">
            <div class="form-check">
                <input type="hidden" name="isCGU" value="0">
                <input class="form-check-input" type="checkbox" name="isCGU" id="isCGU" value="1"
                    {{ old('isCGU', $adherent->isCGU ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="isCGU">CGU MDNI acceptées</label>
            </div>

            <div class="form-check">
                <input type="hidden" name="isPresent" value="0">
                <input class="form-check-input" type="checkbox" name="isPresent" id="isPresent" value="1"
                    {{ old('isPresent', $adherent->isPresent ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="isPresent">Présent à MDNI</label>
            </div>
        </div>

    </div>

    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>

