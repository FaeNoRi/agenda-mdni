<form method="POST" action="{{ isset($objet) ? route('objets.update', $objet) : route('objets.store') }}">
    @csrf
    @if(isset($objet))
        @method('PUT')
    @endif

    <div class="mb-3">
        <label class="form-label">Nom de l’objet</label>
        <input type="text" name="nom_obj" class="form-control"
               value="{{ old('nom_obj', $objet->nom_obj ?? '') }}" required>
    </div>

    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>
