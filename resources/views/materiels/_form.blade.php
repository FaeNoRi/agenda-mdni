<form method="POST" action="{{ isset($materiel) ? route('materiels.update', $materiel) : route('materiels.store') }}">
    @csrf
    @if(isset($materiel))
        @method('PUT')
    @endif

    <div class="mb-3">
        <label class="form-label">Nom du matériel</label>
        <input type="text" name="nom_mat" class="form-control" value="{{ old('nom_mat', $materiel->nom_mat ?? '') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Stock disponible</label>
        <input type="number" name="nb_stock" class="form-control" min="0" value="{{ old('nb_stock', $materiel->nb_stock ?? 0) }}" required>
    </div>

    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>
