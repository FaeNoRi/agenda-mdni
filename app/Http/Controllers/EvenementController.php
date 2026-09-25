<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Evenements;
use App\Models\Salles;
use App\Models\User;
use App\Models\Materiels;
use App\Models\Objets;
use App\Models\Conge;
use App\Models\ChangementHoraire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Services\SendEventEmailService;

class EvenementController extends Controller
{

    private const EVENT_TYPE_COLORS = [
        'RDV'          => 'primary',
        'Location'     => 'danger',
        'Permanence'   => 'danger',
        'Atelier'      => 'success',
        'Interne'      => 'warning',
        'Information'  => 'secondary',
        'Réunion'      => 'warning',
        'Exceptionnel' => 'danger',
        'FN-RDV'       => 'info',
        'FN-Atelier'   => 'info',
        'Annulé'       => 'dark',
        'Fonctionnement' => 'purple',
    ];

    private function normalizeEventType(?string $type): string
    {
        $type = trim((string) $type);

        return match ($type) {
            'Annule' => 'Annulé',
            default => $type,
        };
    }

    private function typeColorName(?string $type): string
    {
        $type = $this->normalizeEventType($type);

        return self::EVENT_TYPE_COLORS[$type] ?? 'secondary';
    }

    private function typeColorHex(?string $type): string
    {
        return match ($this->typeColorName($type)) {
            'primary'   => '#206bc4',
            'secondary' => '#667382',
            'success'   => '#2fb344',
            'info'      => '#4299e1',
            'warning'   => '#f76707',
            'danger'    => '#d63939',
            'dark'      => '#182433',
            'purple'    => '#ae3ec9',
            default     => '#667382',
        };
    }

    private function typeColorLightHex(?string $type): string
    {
        return match ($this->typeColorName($type)) {
            'primary'   => '#dbeafe',
            'secondary' => '#e5e7eb',
            'success'   => '#dcfce7',
            'info'      => '#dff2ff',
            'warning'   => '#ffedd5',
            'danger'    => '#fee2e2',
            'dark'      => '#d1d5db',
            'purple'    => '#f3e1f9',
            default     => '#e5e7eb',
        };
    }

    private function applyEventColors(Evenements $event): Evenements
    {
        $event->setAttribute('type_color', $this->typeColorName($event->type_event));
        $event->setAttribute('type_color_hex', $this->typeColorHex($event->type_event));

        return $event;
    }

    private function applyEventColorsToCollection($events)
    {
        return $events->each(function (Evenements $event) {
            $this->applyEventColors($event);
        });
    }

    private function formatHoraireNotification(ChangementHoraire $c): array
    {
        try {
            $newStart = Carbon::parse($c->new_start);
            $newEnd   = Carbon::parse($c->new_end);

            if ($c->type_chgmt === 'add') {
                $newSameDay = $newStart->isSameDay($newEnd);

                $summary = $newSameDay
                    ? 'Le <strong>' . $newStart->translatedFormat('d F Y') . '</strong>, de <strong>'
                        . $newStart->format('H:i') . '</strong> à <strong>' . $newEnd->format('H:i') . '</strong>'
                    : 'Du <strong>' . $newStart->translatedFormat('d F Y \à H:i') . '</strong> au <strong>'
                        . $newEnd->translatedFormat('d F Y \à H:i') . '</strong>';

                $badge = 'Ajout';
                $dates = $this->dateRangeArray($newStart, $newEnd);

            } elseif ($c->type_chgmt === 'change') {
                $oldStart = Carbon::parse($c->old_start);
                $oldEnd   = Carbon::parse($c->old_end);

                $oldSameDay = $oldStart->isSameDay($oldEnd);
                $newSameDay = $newStart->isSameDay($newEnd);

                if ($oldSameDay && $newSameDay) {
                    // Cas le plus fréquent : on ne met en gras que ce qui change réellement
                    $dateChanged = !$oldStart->isSameDay($newStart);
                    $timeChanged = $oldStart->format('H:i') !== $newStart->format('H:i')
                        || $oldEnd->format('H:i') !== $newEnd->format('H:i');

                    $wrap = fn (string $text, bool $bold) => $bold ? "<strong>{$text}</strong>" : $text;

                    $avantDate = $wrap($oldStart->translatedFormat('d F Y'), $dateChanged);
                    $apresDate = $wrap($newStart->translatedFormat('d F Y'), $dateChanged);

                    $avantHeures = $wrap($oldStart->format('H:i'), $timeChanged) . ' à ' . $wrap($oldEnd->format('H:i'), $timeChanged);
                    $apresHeures = $wrap($newStart->format('H:i'), $timeChanged) . ' à ' . $wrap($newEnd->format('H:i'), $timeChanged);

                    $summary = '<em>Avant</em> : Le ' . $avantDate . ', de ' . $avantHeures
                        . '<br><em>Après</em> : Le ' . $apresDate . ', de ' . $apresHeures;

                } else {
                    // Changement chevauchant plusieurs jours : bloc entier en gras,
                    // pas de distinction fine (voir note dans le message).
                    $formatPeriod = function (Carbon $s, Carbon $e): string {
                        return $s->isSameDay($e)
                            ? $s->translatedFormat('d F Y') . ', de ' . $s->format('H:i') . ' à ' . $e->format('H:i')
                            : 'du ' . $s->translatedFormat('d F Y \à H:i') . ' au ' . $e->translatedFormat('d F Y \à H:i');
                    };

                    $summary = '<em>Avant</em> : <strong>' . $formatPeriod($oldStart, $oldEnd) . '</strong>'
                        . '<br><em>Après</em> : <strong>' . $formatPeriod($newStart, $newEnd) . '</strong>';
                }

                $badge = 'Changement';
                $dates = array_values(array_unique(array_merge(
                    $this->dateRangeArray($oldStart, $oldEnd),
                    $this->dateRangeArray($newStart, $newEnd)
                )));

            } else {
                $summary = 'Le <strong>' . $newStart->translatedFormat('d F Y \à H:i') . '</strong>';
                $badge   = 'Inconnu';
                $dates   = [$newStart->toDateString()];
            }
        } catch (\Exception $e) {
            $summary = 'Dates invalides';
            $badge   = 'Erreur';
            $dates   = [];
        }

        return [
            'type'        => 'horaire',
            'user_id'     => $c->user_id ?? $c->user?->id,
            'user_name'   => $c->user->name ?? 'Utilisateur inconnu',
            'badge_label' => $badge,
            'summary'     => $summary,
            'dates'       => $dates,
        ];
    }

    private function formatCongeNotification(Conge $c): array
    {
        try {
            $start = Carbon::parse($c->start);
            $end   = Carbon::parse($c->end);

            $sameDay = $start->isSameDay($end);
            $fullDay = $start->format('H:i') === '00:00' && $end->format('H:i') === '00:00';

            if ($sameDay && $fullDay) {
                // Le 21 juillet 2026 — Journée entière
                $summary = 'Le <strong>' . $start->translatedFormat('d F Y') . '</strong> — <em>Journée entière</em>';

            } elseif (!$sameDay && $fullDay) {
                // Du 21 juillet au 23 juillet 2026 — Journée entière
                $summary = 'Du <strong>' . $start->translatedFormat('d F') . '</strong> au <strong>'
                    . $end->translatedFormat('d F Y') . '</strong> — <em>Journée entière</em>';

            } elseif ($sameDay && !$fullDay) {
                // Le 21 juillet 2026, de 09:00 à 12:00 — Demi-journée
                $summary = 'Le <strong>' . $start->translatedFormat('d F Y') . '</strong>, de <strong>'
                    . $start->format('H:i') . '</strong> à <strong>' . $end->format('H:i')
                    . '</strong> — <em>Demi-journée</em>';

            } else {
                // Cas non couvert par vos 3 règles : plusieurs jours avec horaires
                // précis (ex: du 21/07 14:00 au 23/07 10:00). Voir remarque à ce sujet.
                $summary = 'Du <strong>' . $start->translatedFormat('d F Y \à H:i') . '</strong> au <strong>'
                    . $end->translatedFormat('d F Y \à H:i') . '</strong>';
            }

            $dates = $this->dateRangeArray($start, $end);
        } catch (\Exception $e) {
            $summary = 'Dates inconnues';
            $dates   = [];
        }

        return [
            'type'        => 'conge',
            'user_id'     => $c->user_id ?? $c->user?->id,
            'user_name'   => $c->user->name ?? 'Utilisateur inconnu',
            'badge_label' => 'Congé',
            'summary'     => $summary,
            'dates'       => $dates,
            // Bornes réelles du congé, utilisées pour détecter un conflit d'horaire
            // (et pas seulement un conflit de date) avec un événement.
            'start'       => $c->start,
            'end'         => $c->end,
        ];
    }

    private function buildNotifications($changementsHoraires, $conges)
    {
        $notifications = collect();

        foreach ($changementsHoraires as $c) {
            $notifications->push($this->formatHoraireNotification($c));
        }

        foreach ($conges as $c) {
            $notifications->push($this->formatCongeNotification($c));
        }

        return $notifications
            ->sortBy('sort_date')
            ->values()
            // sort_date ne sert qu'au tri, inutile côté vue / JSON
            ->map(fn ($n) => collect($n)->except('sort_date')->toArray());
    }

    private function notificationsForDateRange(string $from, string $to)
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate   = Carbon::parse($to)->endOfDay();

        $changements = ChangementHoraire::with('user')->get()->filter(function ($c) use ($fromDate, $toDate) {
            $newOverlap = Carbon::parse($c->new_start)->lte($toDate) && Carbon::parse($c->new_end)->gte($fromDate);

            $oldOverlap = $c->old_start && $c->old_end
                && Carbon::parse($c->old_start)->lte($toDate)
                && Carbon::parse($c->old_end)->gte($fromDate);

            return $newOverlap || $oldOverlap;
        });

        $conges = Conge::with('user')->get()->filter(function ($c) use ($fromDate, $toDate) {
            return Carbon::parse($c->start)->lte($toDate) && Carbon::parse($c->end)->gte($fromDate);
        });

        $notifications = collect();

        foreach ($changements as $c) {
            $notifications->push($this->formatHoraireNotification($c));
        }

        foreach ($conges as $c) {
            $notifications->push($this->formatCongeNotification($c));
        }

        return $notifications->values();
    }

    private function dateRangeArray(Carbon $start, Carbon $end): array
    {
        $days = [];
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $days;
    }

    public function index()
    {
        $evenements = Evenements::with(['users', 'salles', 'materiels', 'objets'])->orderByDesc('id')->get();
        return view('evenements.index', compact('evenements'));
    }

    public function show($id)
    {
        abort(404); // temporairement, on empêche tout accès direct
    }

    public function create()
    {
        $debut = now(); // ou une date par défaut
        $fin = now()->addHours(2);

        $animateurs = collect();
        $salles = collect();
        $materiels = collect();
        $objets = collect();

        $formData = [
            'nom_event' => '',
            'commanditaire_event' => '',
            'nbpart' => 0,
            'type_event' => '',
            'type_public' => 'Autres',
            'desc_event' => '',
            'devis' => 'Non',
            'numdevis' => '',
            'facture' => 'Non',
            'numfact' => '',
            'reglement' => 'Non',
            'type_reglement' => '',
            'num_reglement' => '',
            'objet' => 'Non',
            'date_heure_debut' => $debut->format('Y-m-d\TH:i'),
            'date_heure_fin'   => $fin->format('Y-m-d\TH:i'),

            'animateurs' => $animateurs->pluck('id')->toArray() ?: [],

            'salles' => $salles->map(fn($s) => [
                'id' => $s->id
            ])->toArray(),

            'materiels' => $materiels->map(fn($m) => [
                'id' => $m->id,
                'quantite' => $m->pivot->quantite
            ])->toArray(),

            'objets' => $objets->map(fn($o) => [
                'id' => $o->id,
                'etat' => $o->pivot->etat
            ])->toArray(),
        ];

        return view('evenements._form', [
            'route' => route('evenements.store'),
            'evenement' => null,
            'users' => User::orderByRaw('id = 16 desc')->orderBy('is_equipe')->orderBy('name')->get()
            ->groupBy(function ($user) {
                return $user->is_equipe ? 'Équipe' : 'Autres';
            }),
            'salles' => Salles::orderByRaw('id = 0 desc')->orderBy('type_salle')->orderBy('nom_salle')->get()->groupBy('type_salle'),
            'materiels' => Materiels::orderBy('nom_mat')->get(),
            'objets' => Objets::orderBy('nom_obj')->get(),
            'formData' => $formData,
        ]);

    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom_event' => 'required|string|max:255',
            'commanditaire_event' => 'nullable|string',
            'nbpart' => 'nullable|integer',
            'type_event' => 'required|string',
            'type_public' => 'nullable|string',
            'devis' => 'nullable|in:Oui,Non,A faire',
            'numdevis' => 'nullable|string',
            'facture' => 'nullable|in:Oui,Non,A faire',
            'numfact' => 'nullable|string',
            'reglement' => 'nullable|in:Oui,Non,A faire',
            'type_reglement' => 'nullable|string',
            'num_reglement' => 'nullable|string',
            'desc_event' => 'nullable|string',
            'date_heure_debut' => 'required|date_format:Y-m-d\TH:i',
            'date_heure_fin'   => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_heure_debut',
            'objet' => 'nullable|in:Oui,Non,A faire',
            'users' => 'nullable|array',
            'salles' => 'nullable|array',
            'materiels' => 'nullable|array',
            'quantites' => 'nullable|array',
            'objets' => 'nullable|array',
            'etat' => 'nullable|array'
        ]);

        $data['auteur'] = Auth::user()?->name;

        try {
            $evenement = null; // ➜ à rendre accessible hors de la transaction

            DB::transaction(function () use ($data, &$evenement) {
                $evenement = Evenements::create($data);
                Log::debug('Événement créé', ['evenement_id' => $evenement->id ?? null]);

                $evenement->users()->sync($data['users'] ?? []);
                Log::debug('Users liés :', $data['users'] ?? []);

                $evenement->salles()->sync($data['salles'] ?? []);
                Log::debug('Objets liés :', $data['objets'] ?? []);

                $materiels = [];
                if (!empty($data['materiels'])) {
                    foreach ($data['materiels'] as $i => $id) {
                        $quantite = $data['quantites'][$i] ?? 0;
                        $materiels[$id] = ['quantite' => $quantite];
                    }
                }
                $evenement->materiels()->sync($materiels);

                $objects = [];
                if (!empty($data['objets'])) {
                    foreach ($data['objets'] as $i => $id) {
                        $etat = $data['etat'][$i] ?? 'A faire';
                        $objects[$id] = ['etat' => $etat];
                    }
                }
                $evenement->objets()->sync($objects);
            });

            // ✅ Envoi d’email une fois la transaction réussie
            app(SendEventEmailService::class)->send($evenement, SendEventEmailService::CREATED);

        } catch (\Throwable $e) {
            Log::error("Erreur store() Evenement: " . $e->getMessage());
            return back()->withErrors('Impossible de sauvegarder l\'événement.');
        }

        if ($request->input('source') === 'dashboard') {
            $dateFiltre = Carbon::parse($data['date_heure_debut'])->toDateString();

            return redirect()
                ->route('dashboard', [
                    'from' => $dateFiltre,
                    'to' => $dateFiltre,
                ])
                ->with('success', 'Événement créé avec succès.');
        }

        return redirect()->back()->with('success', 'Événement créé avec succès.');
    }

    public function edit(Evenements $evenement)
    {
        $animateurs = $evenement?->users ?? collect();
        $salles = $evenement?->salles ?? collect();
        $materiels = $evenement?->materiels ?? collect();
        $objets = $evenement?->objets ?? collect();

        $formData = [
            'nom_event' => $evenement->nom_event ?? '',
            'commanditaire_event' => $evenement->commanditaire_event ?? '',
            'nbpart' => $evenement->nbpart ?? '',
            'type_event' => $evenement->type_event ?? '',
            'type_public' => $evenement->type_public ?? 'Autres',
            'desc_event' => $evenement->desc_event ?? '',
            'devis' => $evenement->devis ?? 'Non',
            'numdevis' => $evenement->numdevis ?? '',
            'facture' => $evenement->facture ?? 'Non',
            'numfact' => $evenement->numfact ?? '',
            'reglement' => $evenement->reglement ?? 'Non',
            'type_reglement' => $evenement->type_reglement ?? '',
            'num_reglement' => $evenement->num_reglement ?? '',
            'objet' => $evenement->objet ?? 'Non',
            'date_heure_debut' => optional($evenement->date_heure_debut)
                                    ->format('Y-m-d\TH:i') ?? '',
            'date_heure_fin'   => optional($evenement->date_heure_fin)
                                    ->format('Y-m-d\TH:i') ?? '',

            'animateurs' => $animateurs->pluck('id')->toArray() ?: [],

            'salles' => $salles->map(fn($s) => [
                'id' => $s->id
            ])->toArray(),

            'materiels' => $materiels->map(fn($m) => [
                'id' => $m->id,
                'quantite' => $m->pivot->quantite
            ])->toArray(),

            'objets' => $objets->map(fn($o) => [
                'id' => $o->id,
                'etat' => $o->pivot->etat
            ])->toArray(),
        ];

        return view('evenements._form', [
            'route' => route('evenements.update', $evenement),
            'evenement' => $evenement->load('users', 'salles', 'materiels', 'objets'),
            'users' => User::orderByRaw('id = 16 desc')->orderBy('is_equipe')->orderBy('name')->get()
            ->groupBy(function ($user) {
                return $user->is_equipe ? 'Équipe' : 'Autres';
            }),
            'salles' => Salles::orderByRaw('id = 0 desc')->orderBy('type_salle')->orderBy('nom_salle')->get()->groupBy('type_salle'),
            'materiels' => Materiels::orderBy('nom_mat')->get(),
            'objets' => Objets::orderBy('nom_obj')->get(),
            'formData' => $formData,
        ]);
    }

    public function update(Request $request, Evenements $evenement)
    {
        $data = $request->validate([
            'nom_event' => 'required|string|max:255',
            'commanditaire_event' => 'nullable|string',
            'nbpart' => 'nullable|integer',
            'type_event' => 'required|string',
            'type_public' => 'nullable|string',
            'devis' => 'nullable|in:Oui,Non,A faire',
            'numdevis' => 'nullable|string',
            'facture' => 'nullable|in:Oui,Non,A faire',
            'numfact' => 'nullable|string',
            'reglement' => 'nullable|in:Oui,Non,A faire',
            'type_reglement' => 'nullable|string',
            'num_reglement' => 'nullable|string',
            'desc_event' => 'nullable|string',
            'date_heure_debut' => 'required|date_format:Y-m-d\TH:i',
            'date_heure_fin'   => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_heure_debut',
            'objet' => 'nullable|in:Oui,Non,A faire',
            'users' => 'nullable|array',
            'salles' => 'nullable|array',
            'materiels' => 'nullable|array',
            'quantites' => 'nullable|array',
            'objets' => 'nullable|array',
            'etat' => 'nullable|array'
        ]);

        $data['auteur'] = Auth::user()?->name;

        // Animateurs avant modification : les personnes retirées reçoivent une annulation.
        $previousUserIds = $evenement->users()->pluck('users.id')->all();

        try {
            DB::transaction(function () use ($request, $evenement, $data) {
                $evenement->update($data);

                // Users
                $evenement->users()->sync($request->input('users', []));

                // Salles
                $evenement->salles()->sync($request->input('salles', []));

                // Matériels
                $materielData = [];
                $materiels = $request->input('materiels', []);
                $quantites = $request->input('quantites', []);
                foreach ($materiels as $i => $materielId) {
                    if ($materielId) {
                        $materielData[$materielId] = ['quantite' => $quantites[$i] ?? 1];
                    }
                }
                $evenement->materiels()->sync($materielData);

                // Objets
                if ($request->input('objet') === 'Oui') {
                    $evenement->update(['objet' => 'Oui']);
                    $objetData = [];
                    $objets = $request->input('objets', []);
                    $etats = $request->input('etat', []);
                    foreach ($objets as $i => $objetId) {
                        if ($objetId) {
                            $objetData[$objetId] = ['etat' => $etats[$i] ?? 'A faire'];
                        }
                    }
                    $evenement->objets()->sync($objetData);
                } else {
                    $evenement->update(['objet' => 'Non']);
                    $evenement->objets()->detach();
                }
            });

            app(SendEventEmailService::class)->send($evenement, SendEventEmailService::UPDATED, $previousUserIds);

        } catch (\Throwable $e) {
            Log::error("Erreur update() Evenement: " . $e->getMessage());
            return back()->withErrors('Impossible de modifier l\'événement.');
        }

        if ($request->input('source') === 'dashboard') {
            $dateFiltre = Carbon::parse($data['date_heure_debut'])->toDateString();

            return redirect()
                ->route('dashboard', [
                    'from' => $dateFiltre,
                    'to' => $dateFiltre,
                ])
                ->with('success', 'Événement modifié avec succès.');
        }

        return redirect()->back()->with('success', 'Événement modifié avec succès.');
    }

    public function destroy(Request $request, Evenements $evenement)
    {

        $dateFiltre = Carbon::parse($evenement->date_heure_debut)->toDateString();

        // Avant suppression (les animateurs sont encore rattachés) : retire l'événement des agendas.
        // (inutile si l'événement était déjà passé au type "Annulé" : l'annulation a déjà été envoyée)
        if (!in_array($evenement->type_event, ['Annule', 'Annulé'], true)) {
            app(SendEventEmailService::class)->send($evenement, SendEventEmailService::CANCELLED);
        }

        DB::transaction(function () use ($evenement) {
            $evenement->users()->detach();
            $evenement->salles()->detach();
            $evenement->materiels()->detach();
            $evenement->objets()->detach();

            $evenement->delete();
        });

        if ($request->input('source') === 'dashboard') {
            return redirect()
                ->route('dashboard', [
                    'from' => $dateFiltre,
                    'to' => $dateFiltre,
                ])
                ->with('success', 'Événement supprimé avec succès.');
        }

        return redirect()->back()->with('success', 'Événement supprimé avec succès.');
    }

    public function data()
    {
        return DataTables::of(\App\Models\Evenements::query())
            // Formatage de date déjà en place…
            ->editColumn('date_heure_debut', fn($e) =>
                Carbon::parse($e->date_heure_debut)->translatedFormat('d F Y H:i'))
            ->editColumn('date_heure_fin', fn($e) =>
                Carbon::parse($e->date_heure_fin)->translatedFormat('d F Y H:i'))
            ->editColumn('commanditaire_event', function($evenement) {
                return e($evenement->commanditaire_event);
            })
            ->editColumn('type_event', function($evenement) {
                $type  = $evenement->type_event;
                $color = $this->typeColorName($type);

                return '<span class="badge bg-'.e($color).' text-white">'.e($type).'</span>';
            })
            ->addColumn('actions', function(\App\Models\Evenements $evenement) {
                return view('evenements.partials.actions', compact('evenement'))->render();
            })
            ->rawColumns(['type_event', 'actions'])
            ->make(true);
    }

    public function disponibilites(Request $request)
    {
        $debutInput = $request->input('debut');
        $finInput = $request->input('fin');

        if (!$debutInput || !$finInput) {
            return response()->json([
                'users' => (object) [],
                'salles' => (object) [],
            ]);
        }

        try {
            // Normalisation indispensable : le champ datetime-local du formulaire envoie
            // un format "Y-m-dTH:i", incompatible en comparaison directe (chaîne) avec le
            // format "Y-m-d H:i:s" stocké en base (particulièrement sur SQLite).
            $debut = Carbon::parse($debutInput)->format('Y-m-d H:i:s');
            $fin = Carbon::parse($finInput)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return response()->json([
                'users' => (object) [],
                'salles' => (object) [],
            ]);
        }

        $userReasons = [];
        $salleReasons = [];

        // Autres événements chevauchant le créneau (occupation ferme)
        $evenements = Evenements::where('type_event', '!=', 'Annule')
            ->where(function ($q) use ($debut, $fin) {
                $q->where('date_heure_debut', '<', $fin)
                  ->where('date_heure_fin', '>', $debut);
            })
            ->with(['users', 'salles'])
            ->get();

        foreach ($evenements as $evenement) {
            $periode = Carbon::parse($evenement->date_heure_debut)->format('d/m H:i')
                . '–' . Carbon::parse($evenement->date_heure_fin)->format('H:i');
            $reason = 'Occupé : ' . ($evenement->nom_event ?: 'Événement') . " ({$periode})";

            foreach ($evenement->users as $user) {
                $userReasons[$user->id][] = $reason;
            }
            foreach ($evenement->salles as $salle) {
                $salleReasons[$salle->id][] = $reason;
            }
        }

        // Congés chevauchant le créneau (alerte indicative)
        $conges = Conge::where('start', '<', $fin)->where('end', '>', $debut)->get();

        foreach ($conges as $conge) {
            $sameDay = Carbon::parse($conge->start)->isSameDay(Carbon::parse($conge->end));
            $periode = $sameDay
                ? Carbon::parse($conge->start)->format('d/m')
                : Carbon::parse($conge->start)->format('d/m') . '–' . Carbon::parse($conge->end)->format('d/m');

            $userReasons[$conge->user_id][] = "En congé ({$periode})";
        }

        // Changements d'horaires chevauchant le créneau (alerte indicative)
        $changements = ChangementHoraire::where('new_start', '<', $fin)->where('new_end', '>', $debut)->get();

        foreach ($changements as $c) {
            $periode = Carbon::parse($c->new_start)->format('d/m H:i')
                . '–' . Carbon::parse($c->new_end)->format('H:i');

            $userReasons[$c->user_id][] = "Horaire modifié ({$periode})";
        }

        // Cast en objet pour garantir un objet JSON même si les clés (ids) sont numériques et séquentielles
        return response()->json([
            'users' => (object) $userReasons,
            'salles' => (object) $salleReasons,
        ]);
    }

    public function dashboard(Request $request)
    {

        $from = $request->query('from', today()->toDateString());
        $to = $request->query('to', $from);

        $events = Evenements::with(['users','salles','materiels','objets'])
            ->whereDate('date_heure_debut', '>=', $from)
            ->whereDate('date_heure_debut', '<=', $to)
            ->orderByRaw("CASE WHEN type_event IN ('Annulé', 'Annule') THEN 1 ELSE 0 END")
            ->orderBy('type_event')
            ->orderBy('date_heure_debut')
            ->get();

        $this->applyEventColorsToCollection($events);

        // listes pour l'Offcanvas
        $typesDisponibles       = Evenements::distinct()
                                           ->pluck('type_event')
                                           ->sort();
        $sallesDisponibles      = Salles::orderBy('nom_salle')
                                        ->get(['id','nom_salle']);
        $animateursDisponibles  = User::orderBy('name')
                                     ->get(['id','name']);

        $changementsHoraires = ChangementHoraire::with('user')
            ->whereDate('new_end', '>=', today())
            ->orderBy('new_start')
            ->take(10)
            ->get();

        $conges = Conge::with('user')
            ->whereDate('end', '>=', today())
            ->orderBy('start')
            ->take(10)
            ->get();
        $notifications = $this->notificationsForDateRange($from, $to);

        $eventsForCalendar = $events->map(function ($e) {
            return [
                'id'              => $e->id,
                'title'           => $e->nom_event,
                'start'           => $e->date_heure_debut,
                'end'             => $e->date_heure_fin,
                'backgroundColor' => $e->type_color_hex,
                'borderColor'     => $e->type_color_hex,
                'textColor'       => '#ffffff',
            ];
        })->values();

        return view('dashboard.dashboard', compact(
            'events',
            'typesDisponibles',
            'sallesDisponibles',
            'animateursDisponibles',
            'notifications',
            'eventsForCalendar'
        ));

    }

    public function filter(Request $request)
    {
        $query = Evenements::with(['users','salles','materiels','objets']);

        if (! $request->filled('from') && ! $request->filled('to')) {
            $query->whereDate('date_heure_debut', today());
        } else {
            if ($request->filled('from')) {
                $query->whereDate('date_heure_debut', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('date_heure_fin', '<=', $request->to);
            }
        }

        if ($request->has('type') && is_array($request->type)) {
            $query->whereIn('type_event', $request->type);
        }

        if ($request->has('salle') && is_array($request->salle)) {
            $query->whereHas('salles', fn($q) =>
                $q->whereIn('salles.id', $request->salle)
            );
        }

        if ($request->has('user') && is_array($request->user)) {
            $query->whereHas('users', fn($q) =>
                $q->whereIn('users.id', $request->user)
            );
        }

        $events = $query
            ->orderByRaw("CASE WHEN type_event IN ('Annulé', 'Annule') THEN 1 ELSE 0 END")
            ->orderBy('type_event')
            ->orderBy('date_heure_debut', 'asc')
            ->get();

        $this->applyEventColorsToCollection($events);

        $from = $request->filled('from') ? $request->from : today()->toDateString();
        $to   = $request->filled('to')   ? $request->to   : $from;

        $notifications = $this->notificationsForDateRange($from, $to);

        return view('dashboard.partials.cards', compact('events', 'notifications'))->render();
    }

    public function cards(Request $request)
    {
        $query = Evenements::with(['users','salles','materiels','objets']);

        if (! $request->filled('from') && ! $request->filled('to')) {
            $query->whereDate('date_heure_debut', today());
        } else {
            if ($request->filled('from')) {
                $query->whereDate('date_heure_debut', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('date_heure_fin', '<=', $request->to);
            }
        }

        $types = (array) $request->query('type', []);
        if (count($types)) {
            $query->whereIn('type_event', $types);
        }

        // multi‐salle
        $salles = (array) $request->query('salle', []);
        if (count($salles)) {
            // UN SEUL whereHas : fait un OR interne (whereIn)

            $query->whereHas('salles', function($q) use ($salles) {
                $q->whereIn('salles.id', $salles);
            });
        }

        // multi‐user
        $users = (array) $request->query('user', []);
        $teamId        = '0';
        if (count($users)) {
            $query->whereHas('users', function($q) use($users, $teamId) {
                $q->whereIn('users.id', $users)
                ->orWhere('users.id', $teamId);
            });
        }

            $events = $query
                ->orderByRaw("CASE WHEN type_event IN ('Annulé', 'Annule') THEN 1 ELSE 0 END")
                ->orderBy('type_event')
                ->orderBy('date_heure_debut')
                ->get();

            $this->applyEventColorsToCollection($events);

        $from = $request->filled('from') ? $request->from : today()->toDateString();
        $to   = $request->filled('to')   ? $request->to   : $from;

        $notifications = $this->notificationsForDateRange($from, $to);

        return view('dashboard.partials.cards', compact('events', 'notifications'))->render();

    }

    public function showDetails(Evenements $evenement)
    {
        $evenement->load(['users','salles','materiels','objets']);

        $this->applyEventColors($evenement);

        return view('dashboard.partials.modal', [
            'event' => $evenement,
        ]);
    }

    public function duplicate(Evenements $evenement)
    {
        $evenement->load(['users', 'salles', 'materiels', 'objets']);

        $formData = [
            'nom_event' => $evenement->nom_event ?? '',
            'commanditaire_event' => $evenement->commanditaire_event ?? '',
            'nbpart' => $evenement->nbpart ?? '',
            'type_event' => $evenement->type_event ?? '',
            'type_public' => $evenement->type_public ?? 'Autres',
            'desc_event' => $evenement->desc_event ?? '',

            'devis' => $evenement->devis ?? 'Non',
            'numdevis' => $evenement->numdevis ?? '',

            'facture' => $evenement->facture ?? 'Non',
            'numfact' => $evenement->numfact ?? '',

            'reglement' => $evenement->reglement ?? 'Non',
            'type_reglement' => $evenement->type_reglement ?? '',
            'num_reglement' => $evenement->num_reglement ?? '',

            'objet' => $evenement->objet ?? 'Non',

            'date_heure_debut' => optional($evenement->date_heure_debut)->format('Y-m-d\TH:i') ?? '',
            'date_heure_fin' => optional($evenement->date_heure_fin)->format('Y-m-d\TH:i') ?? '',

            'animateurs' => $evenement->users->pluck('id')->toArray(),

            'salles' => $evenement->salles->map(fn ($s) => [
                'id' => $s->id,
            ])->toArray(),

            'materiels' => $evenement->materiels->map(fn ($m) => [
                'id' => $m->id,
                'quantite' => $m->pivot->quantite,
            ])->toArray(),

            'objets' => $evenement->objets->map(fn ($o) => [
                'id' => $o->id,
                'etat' => $o->pivot->etat,
            ])->toArray(),
        ];

        return view('evenements._form', [
            'route' => route('evenements.store'),
            'evenement' => null,

            'users' => User::orderByRaw('id = 16 desc')
                ->orderBy('is_equipe')
                ->orderBy('name')
                ->get()
                ->groupBy(fn ($user) => $user->is_equipe ? 'Équipe' : 'Autres'),

            'salles' => Salles::orderByRaw('id = 0 desc')
                ->orderBy('type_salle')
                ->orderBy('nom_salle')
                ->get()
                ->groupBy('type_salle'),

            'materiels' => Materiels::orderBy('nom_mat')->get(),
            'objets' => Objets::orderBy('nom_obj')->get(),

            'formData' => $formData,
            'mode' => 'duplicate',
        ]);
    }

    public function calendarData(Request $request)
    {
        $query = Evenements::with(['users', 'salles']);

        // 🎯 Filtre type_event uniquement si sélection présente
        if ($request->has('type') && count($request->type)) {
            $query->whereIn('type_event', $request->type);
        }

        // 🎯 Filtre salle uniquement si sélection présente
        if ($request->has('salle') && count($request->salle)) {
            $query->whereHas('salles', fn($q) =>
                $q->whereIn('salles.id', $request->salle)
            );
        }

        // 🎯 Filtre user uniquement si sélection présente
        if ($request->has('user') && count($request->user)) {
            $query->whereHas('users', fn($q) =>
                $q->whereIn('users.id', array_merge($request->user, [0]))
            );
        }

        // 🎯 Filtre sur la période visible dans le calendrier
        if ($request->filled('start') && $request->filled('end')) {
            $query->where('date_heure_debut', '<', $request->end)
                ->where('date_heure_fin', '>', $request->start);
        }

        return $query->get()->map(function ($e) {
            $start = Carbon::parse($e->date_heure_debut);
            $end   = Carbon::parse($e->date_heure_fin);

            $isAllDay = $start->format('H:i') === '00:00'
                && $end->format('H:i') === '00:00';

            $color = $this->typeColorHex($e->type_event);
            $lightColor = $this->typeColorLightHex($e->type_event);

            return [
                'id'              => $e->id,
                'title'           => $e->nom_event,
                'start'           => $start->toIso8601String(),
                'end'             => ($isAllDay ? $end->copy()->addDay() : $end)->toIso8601String(),
                'allDay'          => $isAllDay,

                // Rendu outline / light
                'backgroundColor' => $lightColor,
                'borderColor'     => $color,
                'textColor'       => '#374151',

                // Classe CSS optionnelle pour affiner le rendu
                'classNames'      => ['fc-event-outline'],
            ];
        });
    }
}
