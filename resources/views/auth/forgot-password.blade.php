<x-auth-layout titre="Mot de passe oublié" sousTitre="Indiquez votre adresse e-mail : si un compte existe, vous recevrez un lien pour choisir un nouveau mot de passe.">
    @if (session('status'))
        <div class="auth-statut" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="auth-champ">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="username" placeholder="prenom.nom@exemple.fr">
            @error('email') <div class="auth-erreur">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Envoyer le lien de réinitialisation</button>

        <div class="auth-ligne" style="justify-content: center; margin: 18px 0 0;">
            <a class="auth-lien" href="{{ route('login') }}">← Retour à la connexion</a>
        </div>
    </form>
</x-auth-layout>
