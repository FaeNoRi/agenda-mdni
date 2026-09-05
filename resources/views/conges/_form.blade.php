@php
$isEdit = $isEdit ?? false;
@endphp

<form method="POST" action="{{ $isEdit ? route('conges.update', $conge) : route('conges.store') }}">
    @csrf
    @if ($isEdit)
    @method('PUT')
    @endif

    <div class="mb-3">
        <label for="user_id" class="form-label">Utilisateur</label>
        <select class="form-select" id="user_id" name="user_id" required>
            @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected(old('user_id', $isEdit ? $conge->user_id : '') == $user->id)>
                {{ $user->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="start" class="form-label">Date de début</label>
        <input type="datetime-local" name="start" id="start" class="form-control" value="{{ old('start', $isEdit ? \Carbon\Carbon::parse($conge->start)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
    </div>

    <div class="mb-3">
        <label for="end" class="form-label">Date de fin</label>
        <input type="datetime-local" name="end" id="end" class="form-control" value="{{ old('end', $isEdit ? \Carbon\Carbon::parse($conge->end)->format('Y-m-d\TH:i') : now()->addHours(8)->format('Y-m-d\TH:i')) }}" required>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Mettre à jour' : 'Enregistrer' }}
        </button>
    </div>
</form>
