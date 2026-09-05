<form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if(isset($user))
    @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nom</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        @if(!isset($user))
        {{-- En création : mot de passe requis --}}
        <div class="col-md-6">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Confirmation mot de passe</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        @else
        {{-- En modification : mot de passe facultatif --}}
        <div class="col-6">
            <label class="form-label">Nouveau mot de passe </label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="col-6">
            <label class="form-label">Confirmation nouveau mot de passe</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>
        <span class="text-muted">(Laisser vide si aucune modification de mot de passe)</span>
        @endif
        <div class="row g-2" style="width: 100%;">
            <label class="form-label">Rôle</label>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="hidden" name="is_admin" value="0">
                    <input class="form-check-input" type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">Administrateur</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="hidden" name="is_equipe" value="0">
                    <input class="form-check-input" type="checkbox" name="is_equipe" value="1" {{ old('is_equipe', $user->is_equipe ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">Équipe</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="hidden" name="is_email" value="0">
                    <input class="form-check-input" type="checkbox" name="is_email" value="1" {{ old('is_email', $user->is_email ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label">Envoi d'email</label>
                </div>
            </div>

        </div>
    </div>

    <hr class="my-4">

    <h4>Planning horaire</h4>

    <input type="hidden" name="horaire_id" value="{{ $horaire->id ?? '' }}">

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
            <thead>
                <tr>
                    <th>Jour</th>
                    <th>Début</th>
                    <th>Pause début</th>
                    <th>Pause fin</th>
                    <th>Fin</th>
                    <th>Matin</th>
                    <th>Après-midi</th>
                    <th>Repos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jours as $jour)
                @php
                $ligne = $horaire->jours->first(function ($item) use ($jour) {
                return strtolower($item->jour) === strtolower($jour);
                });
                @endphp
                <tr>
                    <td>{{ $jour }}</td>
                    <td><input type="time" name="jours[{{ $jour }}][debut]" class="form-control" value="{{ isset($ligne->debut) ? \Carbon\Carbon::parse($ligne->debut)->format('H:i') : '' }}"></td>
                    <td><input type="time" name="jours[{{ $jour }}][pause_debut]" class="form-control" value="{{ isset($ligne->pause_debut) ? \Carbon\Carbon::parse($ligne->pause_debut)->format('H:i') : '' }}"></td>
                    <td><input type="time" name="jours[{{ $jour }}][pause_fin]" class="form-control" value="{{ isset($ligne->pause_fin) ? \Carbon\Carbon::parse($ligne->pause_fin)->format('H:i') : '' }}"></td>
                    <td><input type="time" name="jours[{{ $jour }}][fin]" class="form-control" value="{{ isset($ligne->fin) ? \Carbon\Carbon::parse($ligne->fin)->format('H:i') : '' }}"></td>
                    <td class="text-center"><input type="checkbox" name="jours[{{ $jour }}][matin]" value="1" {{ !empty($ligne) && $ligne->matin ? 'checked' : '' }}></td>
                    <td class="text-center"><input type="checkbox" name="jours[{{ $jour }}][aprem]" value="1" {{ !empty($ligne) && $ligne->aprem ? 'checked' : '' }}></td>
                    <td class="text-center"><input type="checkbox" name="jours[{{ $jour }}][repos]" value="1" {{ !empty($ligne) && $ligne->repos ? 'checked' : '' }}></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-end">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>
