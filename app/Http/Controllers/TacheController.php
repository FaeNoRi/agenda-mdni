<?php

namespace App\Http\Controllers;

use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\ProjetHistorique;
use App\Models\Recurrence;
use App\Models\Tache;
use App\Models\User;
use App\Services\NotificationsProjets;
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
    /** Statuts proposés à la création (on ne crée pas une tâche déjà terminée ou annulée). */
    public const STATUTS_DE_DEPART = ['a_faire', 'en_cours', 'a_valider', 'en_attente', 'bloque'];

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
                'statut' => TacheStatut::from($data['statut'] ?? TacheStatut::AFaire->value),
                'raison' => $data['raison'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $tache->responsables()->sync($data['responsables']);
            $this->remplacerLiens($tache, $data['liens']);

            return $tache;
        });

        app(NotificationsProjets::class)->tacheAssignee($tache, $data['responsables'], $request->user());

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

        $avant = DB::transaction(function () use ($data, $tache, $request) {
            $avant = [
                'titre' => $tache->titre,
                'date' => $tache->date_limite->format('d/m/Y'),
                'projet' => $tache->projet_id,
                'resp' => $tache->responsables()->pluck('users.id')->sort()->values()->all(),
            ];

            $tache->update([
                'projet_id' => $data['projet_id'] ?? null,
                'titre' => $data['titre'],
                'details' => $data['details'] ?? null,
                'date_limite' => $data['date_limite'],
            ]);

            $tache->responsables()->sync($data['responsables']);
            $this->remplacerLiens($tache, $data['liens']);

            $this->noterModifications($tache->refresh(), $avant, $request->user());

            return $avant;
        });

        $this->notifierModifications($tache->refresh(), $avant, $request->user());

        return response()->json(['ok' => true, 'id' => $tache->id]);
    }

    public function destroy(Tache $tache): JsonResponse
    {
        Gate::authorize('delete', $tache);

        ProjetHistorique::noter($tache->projet_id, ProjetHistorique::TACHE, 'Tâche « '.$tache->titre.' » supprimée', request()->user());
        app(NotificationsProjets::class)->tacheSupprimee($tache->load(['responsables', 'projet']), request()->user());
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

        $avant = $tache->statut;
        $change = $tache->changerStatut(TacheStatut::from($data['statut']), $request->user(), $data['raison'] ?? null, $data['portee'] ?? null);

        if ($change) {
            app(NotificationsProjets::class)->tacheStatut($tache->refresh(), $avant, $request->user());
        }

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
            'statutsDepart' => array_map(fn ($v) => TacheStatut::from($v), self::STATUTS_DE_DEPART),
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

        $depart = $creation ? [
            'statut' => ['nullable', Rule::in(self::STATUTS_DE_DEPART)],
            'raison' => ['nullable', 'string', 'max:1000'],
        ] : [];

        $data = $request->validate($recurrence + $depart + [
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

        // « En attente » et « Bloqué » exigent une raison, comme lors d'un changement de statut.
        if ($creation && ($statut = TacheStatut::tryFrom($data['statut'] ?? '')) && $statut->exigeRaison() && !filled($data['raison'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'raison' => 'Précisez la raison pour le statut « '.$statut->label().' ».',
            ]);
        }
        if ($creation && !(TacheStatut::tryFrom($data['statut'] ?? '')?->exigeRaison())) {
            $data['raison'] = null;
        }

        $data['liens'] = collect($data['liens'] ?? [])
            ->filter(fn ($l) => filled($l['url'] ?? null))
            ->map(fn ($l) => ['libelle' => filled($l['libelle'] ?? null) ? $l['libelle'] : null, 'url' => $l['url']])
            ->values()
            ->all();

        return $data;
    }

    /** Notifications après modification : nouveaux responsables, responsables retirés, puis les autres responsables. */
    private function notifierModifications(Tache $tache, array $avant, User $par): void
    {
        $notifs = app(NotificationsProjets::class);
        $apres = $tache->responsables()->pluck('users.id')->all();
        $ajoutes = array_values(array_diff($apres, $avant['resp']));
        $retires = array_values(array_diff($avant['resp'], $apres));

        $changements = [];
        if ($avant['titre'] !== $tache->titre) {
            $changements[] = 'renommée en « '.$tache->titre.' »';
        }
        if ($avant['date'] !== $tache->date_limite->format('d/m/Y')) {
            $changements[] = 'date limite '.$avant['date'].' → '.$tache->date_limite->format('d/m/Y');
        }
        if ($avant['projet'] !== $tache->projet_id) {
            $changements[] = 'projet : '.($tache->projet?->nom ?? 'sans projet');
        }

        $notifs->tacheAssignee($tache, $ajoutes, $par);
        $notifs->tacheRetiree($tache, $retires, $par);
        $notifs->tacheModifiee($tache, $changements, $ajoutes, $par);
    }

    /** Journal du projet : ce qui a changé sur la tâche (intitulé, date, projet, responsables). */
    private function noterModifications(Tache $tache, array $avant, User $par): void
    {
        $projetAvant = $avant['projet'];
        $t = '« '.$tache->titre.' »';

        if ($projetAvant !== $tache->projet_id) {
            ProjetHistorique::noter($projetAvant, ProjetHistorique::TACHE, 'Tâche '.$t.' retirée du projet', $par);
            ProjetHistorique::noter($tache->projet_id, ProjetHistorique::TACHE, 'Tâche '.$t.' ajoutée au projet', $par, $tache->id);
        }
        if ($avant['titre'] !== $tache->titre) {
            ProjetHistorique::noter($tache->projet_id, ProjetHistorique::TACHE, 'Tâche renommée : « '.$avant['titre'].' » → '.$t, $par, $tache->id);
        }
        if ($avant['date'] !== $tache->date_limite->format('d/m/Y')) {
            ProjetHistorique::noter($tache->projet_id, ProjetHistorique::TACHE, $t.' : date limite '.$avant['date'].' → '.$tache->date_limite->format('d/m/Y'), $par, $tache->id);
        }
        if ($avant['resp'] !== $tache->responsables()->pluck('users.id')->sort()->values()->all()) {
            ProjetHistorique::noter($tache->projet_id, ProjetHistorique::TACHE, $t.' : responsables '.$tache->responsables()->orderBy('name')->pluck('users.name')->join(', '), $par, $tache->id);
        }
    }

    private function remplacerLiens(Tache $tache, array $liens): void
    {
        $tache->liens()->delete();

        foreach ($liens as $lien) {
            $tache->liens()->create($lien);
        }
    }
}
