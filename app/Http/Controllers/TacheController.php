<?php

namespace App\Http\Controllers;

use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\Tache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * Page « Tâches » : toutes les tâches de l'association, regroupées par projet (lecture seule pour
 * l'instant) et fiche d'une tâche affichée dans une fenêtre modale.
 */
class TacheController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Tache::class);

        $trier = fn ($taches) => $taches
            ->sortBy(fn (Tache $t) => [$t->statut->estOuvert() ? 0 : 1, $t->date_limite->toDateString(), $t->id])
            ->values();

        // Un groupe par projet qui a des tâches (projets ouverts d'abord), puis les tâches sans projet.
        $groupes = Projet::with('taches.responsables')->get()
            ->filter(fn (Projet $p) => $p->taches->isNotEmpty())
            ->sortBy(fn (Projet $p) => [
                $p->etat->estOuvert() ? 0 : 1,
                $p->date_limite?->toDateString() ?? '9999-12-31',
                mb_strtolower($p->nom),
            ])
            ->map(fn (Projet $p) => ['projet' => $p, 'taches' => $trier($p->taches)])
            ->values();

        $simples = Tache::whereNull('projet_id')->with('responsables')->get();
        if ($simples->isNotEmpty()) {
            $groupes->push(['projet' => null, 'taches' => $trier($simples)]);
        }

        $toutes = $groupes->flatMap(fn ($g) => $g['taches']);

        return view('taches.index', [
            'groupes' => $groupes,
            'projets' => $groupes->pluck('projet')->filter()->values(),
            'personnes' => $toutes->flatMap(fn (Tache $t) => $t->responsables)->unique('id')->sortBy('name')->values(),
            'statuts' => TacheStatut::cases(),
            'statutsCarte' => ProjetController::STATUTS_CARTE,
            'nbRetard' => $toutes->filter(fn (Tache $t) => $t->estEnRetard())->count(),
            'aujourdhui' => today(),
        ]);
    }

    /** Contenu de la fenêtre modale d'une tâche (chargé en Ajax, comme le détail d'un événement). */
    public function show(Tache $tache): View
    {
        Gate::authorize('view', $tache);

        $tache->load(['projet.referents', 'responsables', 'createur', 'liens', 'historiques.user', 'commentaires.user']);

        return view('taches._modal', [
            'tache' => $tache,
            'referents' => $tache->referents(),
        ]);
    }
}
