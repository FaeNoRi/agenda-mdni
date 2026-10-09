<?php

namespace App\Http\Controllers;

use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\Recurrence;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Tâches : page de consultation groupée par projet, fiche en fenêtre modale, et écriture
 * (création, modification, suppression, changement de statut) en Ajax depuis cette modale.
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

        $tache->load(['projet.referents', 'responsables', 'createur', 'liens', 'historiques.user', 'commentaires.user', 'recurrence']);

        return view('taches._modal', [
            'tache' => $tache,
            'referents' => $tache->referents(),
            'statuts' => TacheStatut::cases(),
        ]);
    }

    // ---- Écriture ---------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('create', Tache::class);

        $tache = new Tache(['projet_id' => $request->integer('projet') ?: null]);

        return $this->formulaire($tache);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Tache::class);

        $data = $this->valider($request, true);

        $tache = DB::transaction(function () use ($data, $request) {
            $recurrence = !empty($data['recurrent'])
                ? Recurrence::create(['frequence' => $data['frequence'], 'date_fin' => $data['date_fin'], 'created_by' => $request->user()->id])
                : null;

            $tache = Tache::create([
                'recurrence_id' => $recurrence?->id,
                'projet_id' => $data['projet_id'] ?? null,
                'titre' => $data['titre'],
                'details' => $data['details'] ?? null,
                'date_limite' => $data['date_limite'],
                'statut' => TacheStatut::AFaire,
                'created_by' => $request->user()->id,
            ]);

            $tache->responsables()->sync($data['responsables']);
            $this->remplacerLiens($tache, $data['liens']);

            return $tache;
        });

        return response()->json(['ok' => true, 'id' => $tache->id]);
    }

    public function edit(Tache $tache): View
    {
        Gate::authorize('update', $tache);

        return $this->formulaire($tache->load(['responsables', 'liens']));
    }

    public function update(Request $request, Tache $tache): JsonResponse
    {
        Gate::authorize('update', $tache);

        $data = $this->valider($request);

        DB::transaction(function () use ($data, $tache) {
            $tache->update([
                'projet_id' => $data['projet_id'] ?? null,
                'titre' => $data['titre'],
                'details' => $data['details'] ?? null,
                'date_limite' => $data['date_limite'],
            ]);

            $tache->responsables()->sync($data['responsables']);
            $this->remplacerLiens($tache, $data['liens']);
        });

        return response()->json(['ok' => true, 'id' => $tache->id]);
    }

    public function destroy(Tache $tache): JsonResponse
    {
        Gate::authorize('delete', $tache);

        $tache->delete();

        return response()->json(['ok' => true]);
    }

    /** Change le statut (raison obligatoire pour « Bloqué » et « En attente »). */
    public function statut(Request $request, Tache $tache): JsonResponse
    {
        Gate::authorize('changeStatus', $tache);

        $data = $request->validate([
            'statut' => ['required', Rule::enum(TacheStatut::class)],
            'raison' => ['nullable', 'string', 'max:1000'],
            'portee' => ['nullable', Rule::in([Tache::PORTEE_OCCURRENCE, Tache::PORTEE_SERIE])],
        ], [
            'statut.required' => 'Choisissez un statut.',
        ]);

        $change = $tache->changerStatut(TacheStatut::from($data['statut']), $request->user(), $data['raison'] ?? null, $data['portee'] ?? null);

        return response()->json(['ok' => true, 'change' => $change, 'id' => $tache->id]);
    }

    // ---- Outils -----------------------------------------------------------

    private function formulaire(Tache $tache): View
    {
        return view('taches._form', [
            'tache' => $tache,
            'projets' => Projet::orderBy('nom')->get(),
            'personnes' => User::personnes()->orderBy('name')->get(),
            'choisis' => $tache->exists ? $tache->responsables->pluck('id')->all() : [],
            'liens' => $tache->exists
                ? $tache->liens->map(fn ($l) => ['libelle' => $l->libelle, 'url' => $l->url])->values()->all()
                : [],
        ]);
    }

    private function valider(Request $request, bool $creation = false): array
    {
        $recurrence = $creation ? [
            'recurrent' => ['nullable', 'boolean'],
            'frequence' => ['required_if:recurrent,1', 'nullable', Rule::in([Recurrence::HEBDOMADAIRE, Recurrence::MENSUELLE])],
            'date_fin' => ['required_if:recurrent,1', 'nullable', 'date', 'after:date_limite'],
        ] : [];

        $data = $request->validate($recurrence + [
            'titre' => ['required', 'string', 'max:190'],
            'projet_id' => ['nullable', 'integer', 'exists:projets,id'],
            'details' => ['nullable', 'string', 'max:5000'],
            'date_limite' => ['required', 'date'],
            'responsables' => ['required', 'array', 'min:1'],
            'responsables.*' => ['integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('id', '!=', 0))],
            'liens' => ['nullable', 'array', 'max:20'],
            'liens.*.libelle' => ['nullable', 'string', 'max:150'],
            'liens.*.url' => ['nullable', 'url', 'max:500'],
        ], [
            'titre.required' => 'Donnez un intitulé à la tâche.',
            'date_limite.required' => 'La date limite est obligatoire.',
            'date_limite.date' => 'La date limite n\'est pas valide.',
            'responsables.required' => 'Choisissez au moins une personne responsable.',
            'responsables.min' => 'Choisissez au moins une personne responsable.',
            'liens.*.url.url' => 'Un des liens n\'est pas une adresse valide (https://…).',
        ]);

        $data['liens'] = collect($data['liens'] ?? [])
            ->filter(fn ($l) => filled($l['url'] ?? null))
            ->map(fn ($l) => ['libelle' => filled($l['libelle'] ?? null) ? $l['libelle'] : null, 'url' => $l['url']])
            ->values()
            ->all();

        return $data;
    }

    private function remplacerLiens(Tache $tache, array $liens): void
    {
        $tache->liens()->delete();

        foreach ($liens as $lien) {
            $tache->liens()->create($lien);
        }
    }
}
