<?php

namespace App\Http\Controllers;

use App\Enums\ProjetEtat;
use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Support\Icones;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Projets : liste, fiche, et écriture (création, modification, suppression, changement d'état)
 * depuis une fenêtre modale / la fiche, en Ajax.
 */
class ProjetController extends Controller
{
    /** Statuts comptés sur les cartes (« Terminé » est déjà dans l'anneau), dans l'ordre d'affichage. */
    public const STATUTS_CARTE = [
        TacheStatut::AValider, TacheStatut::EnCours, TacheStatut::AFaire,
        TacheStatut::EnAttente, TacheStatut::Bloque, TacheStatut::Annule,
    ];

    /** Couleurs proposées pour un projet. */
    public const PALETTE = [
        '#2fb344', '#4263eb', '#ae3ec9', '#f76707', '#d6336c',
        '#17a2b8', '#f59f00', '#0ca678', '#667382', '#d63939',
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

        // « Mes projets » : référent, ou responsable d'une tâche du projet (relations déjà chargées).
        $mienne = fn (Projet $p) => $p->personnesImpliquees()->contains('id', $moi->id);

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
            'impliques' => $projet->impliquesHorsReferents(),
            'statutsCarte' => self::STATUTS_CARTE,
            'avertissements' => $projet->avertissements(),
            'etats' => ProjetEtat::cases(),
        ]);
    }

    // ---- Écriture ---------------------------------------------------------

    public function create(): View
    {
        Gate::authorize('create', Projet::class);

        return $this->formulaire(new Projet(['etat' => ProjetEtat::EnCours]));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Projet::class);

        $data = $this->valider($request, true);

        $projet = DB::transaction(function () use ($data, $request) {
            $projet = Projet::create([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'couleur' => $data['couleur'],
                'icone' => $data['icone'],
                'date_limite' => $data['date_limite'] ?? null,
                'etat' => $data['etat'],
                'created_by' => $request->user()->id,
            ]);

            $this->syncReferents($projet, $data['referents']);

            return $projet;
        });

        return response()->json(['ok' => true, 'id' => $projet->id]);
    }

    public function edit(Projet $projet): View
    {
        Gate::authorize('update', $projet);

        return $this->formulaire($projet->load('membres'));
    }

    public function update(Request $request, Projet $projet): JsonResponse
    {
        Gate::authorize('update', $projet);

        $data = $this->valider($request, false);

        DB::transaction(function () use ($data, $projet) {
            $projet->update([
                'nom' => $data['nom'],
                'description' => $data['description'] ?? null,
                'couleur' => $data['couleur'],
                'icone' => $data['icone'],
                'date_limite' => $data['date_limite'] ?? null,
            ]);

            $this->syncReferents($projet, $data['referents']);
        });

        return response()->json(['ok' => true, 'id' => $projet->id]);
    }

    public function destroy(Projet $projet): JsonResponse
    {
        Gate::authorize('delete', $projet);

        $projet->delete();

        return response()->json(['ok' => true]);
    }

    /** Change l'état du projet (règle de l'option 2 : voir Projet::changerEtat). */
    public function etat(Request $request, Projet $projet): JsonResponse
    {
        Gate::authorize('update', $projet);

        $data = $request->validate([
            'etat' => ['required', Rule::enum(ProjetEtat::class)],
            'raison' => ['nullable', 'string', 'max:1000'],
        ], [
            'etat.required' => 'Choisissez un état.',
        ]);

        $change = $projet->changerEtat(ProjetEtat::from($data['etat']), $data['raison'] ?? null);

        return response()->json([
            'ok' => true,
            'change' => $change,
            'avertissements' => $projet->fresh()->avertissements(),
        ]);
    }

    // ---- Outils -----------------------------------------------------------

    private function formulaire(Projet $projet): View
    {
        $existe = $projet->exists;

        return view('projets._form', [
            'projet' => $projet,
            'personnes' => User::personnes()->orderBy('name')->get(),
            'referents' => $existe ? $projet->membres->filter(fn ($u) => $u->pivot->role === 'referent')->pluck('id')->all() : [],
            'palette' => self::PALETTE,
            'icones' => Icones::PROJET,
            'etatsCreation' => [ProjetEtat::EnAttente, ProjetEtat::EnCours],
        ]);
    }

    private function valider(Request $request, bool $creation): array
    {
        $personne = Rule::exists('users', 'id')->where(fn ($q) => $q->where('id', '!=', 0));

        $rules = [
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'couleur' => ['required', Rule::in(self::PALETTE)],
            'icone' => ['required', Rule::in(Icones::PROJET)],
            'date_limite' => ['nullable', 'date'],
            'referents' => ['required', 'array', 'min:1'],
            'referents.*' => ['integer', $personne],
        ];

        if ($creation) {
            $rules['etat'] = ['required', Rule::in([ProjetEtat::EnAttente->value, ProjetEtat::EnCours->value])];
        }

        $data = $request->validate($rules, [
            'nom.required' => 'Donnez un nom au projet.',
            'couleur.in' => 'Choisissez une couleur de la liste.',
            'icone.in' => 'Choisissez une icône de la liste.',
            'date_limite.date' => 'La date limite n\'est pas valide.',
            'referents.required' => 'Choisissez au moins un référent.',
            'referents.min' => 'Choisissez au moins un référent.',
        ]);

        $data['referents'] = array_values(array_unique(array_map('intval', $data['referents'])));

        return $data;
    }

    /** Seuls les référents sont saisis ; les impliqués se déduisent des tâches (Projet::personnesImpliquees). */
    private function syncReferents(Projet $projet, array $referents): void
    {
        $projet->membres()->sync(array_fill_keys($referents, ['role' => 'referent']));
    }
}
