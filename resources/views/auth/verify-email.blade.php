<x-auth-layout titre="Vérifiez votre adresse e-mail" sousTitre="Un lien de vérification vous a été envoyé par e-mail. Cliquez dessus pour continuer ; sinon, nous pouvons vous en renvoyer un.">
    @if (session('status') == 'verification-link-sent')
        <div class="auth-statut" role="status">Un nouveau lien de vérification vient d'être envoyé à votre adresse e-mail.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Renvoyer le lien</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 16px; text-align: center;">
        @csrf
        <button type="submit" class="auth-lien" style="border: 0; background: none; color: var(--tblr-primary); font-size: 13.5px; font-weight: 600; cursor: pointer;">Se déconnecter</button>
    </form>
</x-auth-layout>
