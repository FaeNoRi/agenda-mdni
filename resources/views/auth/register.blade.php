<x-auth-layout titre="Créer un compte">
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="auth-champ">
            <label for="name" class="form-label">Nom</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control" required autofocus autocomplete="name">
            @error('name') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <div class="auth-champ">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autocomplete="username">
            @error('email') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <div class="auth-champ">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="new-password">
            @error('password') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <div class="auth-champ">
            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
            @error('password_confirmation') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Créer le compte</button>

        <div class="auth-ligne" style="justify-content: center; margin: 18px 0 0;">
            <a class="auth-lien" href="{{ route('login') }}">Déjà inscrit ? Se connecter</a>
        </div>
    </form>
</x-auth-layout>
