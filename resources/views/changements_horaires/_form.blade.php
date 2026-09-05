@php
$isEdit = isset($changements_horaire) && !empty($changements_horaire->id);
@endphp

<form method="POST" action="{{ $isEdit ? route('changements_horaires.update', $changements_horaire->id) : route('changements_horaires.store') }}" x-data="{
        form: {
            type: '{{ old('type_chgmt', $isEdit ? $changements_horaire->type_chgmt : 'add') }}',
            old_start: '{{ old('old_start', $isEdit ? \Carbon\Carbon::parse($changements_horaire->old_start)->format('Y-m-d\TH:i') : '2000-01-01T00:00') }}',
            old_end: '{{ old('old_end', $isEdit ? \Carbon\Carbon::parse($changements_horaire->old_end)->format('Y-m-d\TH:i') : '2000-01-01T00:00') }}',
        }
    }">
    @csrf
    @if ($isEdit)
    @method('PUT')
    @endif

    <div class="row g-2 mb-3">
        {{-- Utilisateur --}}
        <div class="col-md-6">
            <label class="form-label">Utilisateur</label>
            <select name="user_id" class="form-select" required>
                @foreach ($users as $user)
                @if (!in_array($user->id, [0, 16, 17]))
                <option value="{{ $user->id }}" @selected(old('user_id', $isEdit ? $changements_horaire->user_id : '') == $user->id)>
                    {{ $user->name }}
                </option>
                @endif
                @endforeach
            </select>
        </div>

        {{-- Type de changement --}}
        <div class="col-md-6">
            <label class="form-label">Type de changement</label>
            <select name="type_chgmt" class="form-select" x-model="form.type">
                <option value="add">Ajout</option>
                <option value="change">Modification</option>
            </select>
        </div>
    </div>

    {{-- Ancien horaire --}}
    <div class="row g-2 mb-3">
        <div class="col-md-6" x-show="form.type === 'change'" x-transition>
            <label class="form-label">Ancien horaire - Début</label>
            <input type="datetime-local" name="old_start" class="form-control" x-model="form.old_start">
        </div>
        <template x-if="form.type !== 'change'">
            <input type="hidden" name="old_start" :value="form.old_start">
        </template>

        <div class="col-md-6" x-show="form.type === 'change'" x-transition>
            <label class="form-label">Ancien horaire - Fin</label>
            <input type="datetime-local" name="old_end" class="form-control" x-model="form.old_end">
        </div>
        <template x-if="form.type !== 'change'">
            <input type="hidden" name="old_end" :value="form.old_end">
        </template>
    </div>

    <template x-effect="if (form.type !== 'change') {
        form.old_start = '2000-01-01T00:00';
        form.old_end = '2000-01-01T00:00';
    }"></template>

    {{-- Nouvel horaire --}}
    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label">Nouvel horaire - Début</label>
            <input type="datetime-local" name="new_start" class="form-control" value="{{ old('new_start', $isEdit ? \Carbon\Carbon::parse($changements_horaire->new_start)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nouvel horaire - Fin</label>
            <input type="datetime-local" name="new_end" class="form-control" value="{{ old('new_end', $isEdit ? \Carbon\Carbon::parse($changements_horaire->new_end)->format('Y-m-d\TH:i') : now()->addHour()->format('Y-m-d\TH:i')) }}" required>
        </div>
    </div>

    {{-- Bouton submit --}}
    <div class="text-end">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Mettre à jour' : 'Enregistrer' }}
        </button>
    </div>
</form>
