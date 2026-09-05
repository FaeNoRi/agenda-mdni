<form method="POST" action="{{ isset($salle) ? route('salles.update', $salle) : route('salles.store') }}">
    @csrf
    @if(isset($salle))
        @method('PUT')
    @endif

    <div class="mb-3">
        <label class="form-label">Nom de la salle</label>
        <input type="text" name="nom_salle" class="form-control" value="{{ old('nom_salle', $salle->nom_salle ?? '') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Type de salle</label>
        <select name="type_salle" class="form-select" required>
            @php
                $types = ['Bureau', 'Salle', 'Espace coworking', 'Extérieur'];
            @endphp
            @foreach($types as $type)
                <option value="{{ $type }}" {{ old('type_salle', $salle->type_salle ?? '') === $type ? 'selected' : '' }}>
                    {{ $type }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>