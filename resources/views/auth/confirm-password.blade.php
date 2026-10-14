<x-auth-layout titre="Confirmation" sousTitre="Zone sécurisée : confirmez votre mot de passe avant de continuer.">
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="auth-champ">
            <label for="password" class="form-label">Mot de passe</label>
            <div class="auth-mdp">
                <input id="password" type="password" name="password" class="form-control" required autofocus autocomplete="current-password">
                <button type="button" class="auth-oeil" data-auth-oeil="password" aria-label="Afficher le mot de passe" aria-pressed="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                </button>
            </div>
            @error('password') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Confirmer</button>
    </form>
</x-auth-layout>
