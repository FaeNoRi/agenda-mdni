<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('fr');

        // Tout nouveau mot de passe (changement, réinitialisation) : 10 caractères au moins.
        // Les mots de passe existants ne sont pas touchés : la règle ne joue qu'à la saisie d'un nouveau.
        Password::defaults(fn () => Password::min(10));
    }
}
