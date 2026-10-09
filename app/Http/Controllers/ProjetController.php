<?php

namespace App\Http\Controllers;

use App\Enums\ProjetEtat;
use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\Tache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * Page « Projets » (lecture seule pour l'instant : la création et la modification arrivent plus tard).
 */
class ProjetController extends Controller
{
    /** Statuts comptés sur les cartes (« Terminé » est déjà dans l'anneau), dans l'ordre d'affichage. */
    public const STATUTS_CARTE = [
        TacheStatut::AValider, TacheStatut::EnCours, TacheStatut::AFaire,
        TacheStatut::EnAttente, TacheStatut::Bloque, TacheStatut::Annule,
    ];

    public function index(): View
    {
        Gate::authorize('viewAny', Projet::class);

        $moi = auth()->user();

        $projets = Projet::with(['taches.responsables', 'membres'])->get()
            ->sortBy(fn (Projet $p) => [
                $p->etat->estOuvert() ? 0 : 1,
                $p->date_limite?->toDateString() ?? '9999-12-31',
                mb_strtolower($p->nom),
            ])
            ->values();

        // « Mes projets » : membre du projet ou responsable d'une de ses tâches (sans requête par projet).
        $mienne = fn (Projet $p) => $p->membres->contains('id', $moi->id)
            || $p->taches->contains(fn (Tache $t) => $t->responsables->contains('id', $moi->id));

        $ouvertes = Tache::whereNotIn('statut', [TacheStatut::Termine->value, TacheStatut::Annule->value]);

        $kpis = [
            'projets' => $projets->filter(fn (Projet $p) => $p->etat->estOuvert())->count(),
            'ouvertes' => (clone $ouvertes)->count(),
            'retard' => (clone $ouvertes)->whereDate('date_limite', '<', today())->count(),
            'a_valider' => Tache::where('statut', TacheStatut::AValider->value)->count(),
        ];

        return view('projets.index', [
            'projets' => $projets,
            'mienne' => $mienne,
            'kpis' => $kpis,
            'etats' => ProjetEtat::cases(),
            'statutsCarte' => self::STATUTS_CARTE,
            'retardProjets' => $projets->filter(fn (Projet $p) => $p->estEnRetard())->count(),
        ]);
    }

    public function show(Projet $projet): View
    {
        Gate::authorize('view', $projet);

        $projet->load(['membres', 'createur', 'liens', 'commentaires.user', 'taches.responsables']);

        $taches = $projet->taches
            ->sortBy(fn (Tache $t) => [$t->statut->estOuvert() ? 0 : 1, $t->date_limite->toDateString(), $t->id])
            ->values();

        return view('projets.show', [
            'projet' => $projet,
            'taches' => $taches,
            'resume' => $projet->resume(),
            'referents' => $projet->membres->filter(fn ($u) => $u->pivot->role === 'referent')->values(),
            'impliques' => $projet->membres->filter(fn ($u) => $u->pivot->role !== 'referent')->values(),
            'statutsCarte' => self::STATUTS_CARTE,
        ]);
    }
}
