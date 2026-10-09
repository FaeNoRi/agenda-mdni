<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Module « Projets & tâches » en cours de construction : réservé aux administrateurs tant que
 * config('features.projets_taches') est faux. Les autres reçoivent une 404 (le module n'existe pas pour eux).
 */
class EnsureProjetsTachesAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('features.projets_taches') || $request->user()?->is_admin, 404);

        return $next($request);
    }
}
