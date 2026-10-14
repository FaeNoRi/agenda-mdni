<x-auth-layout titre="Connexion" sousTitre="Connectez-vous pour accéder à votre agenda.">
    @if (session('status'))
        <div class="auth-statut" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="auth-champ">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="username" placeholder="prenom.nom@exemple.fr">
            @error('email') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <div class="auth-champ">
            <label for="password" class="form-label">Mot de passe</label>
            <div class="auth-mdp">
                <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
                <button type="button" class="auth-oeil" data-auth-oeil="password" aria-label="Afficher le mot de passe" aria-pressed="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                </button>
            </div>
            @error('password') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <div class="auth-ligne">
            <label class="form-check mb-0" for="remember_me">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <span class="form-check-label">Rester connecté</span>
            </label>
            @if (Route::has('password.request'))
                <a class="auth-lien" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
</x-auth-layout>
