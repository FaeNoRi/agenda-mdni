<?php

namespace App\Services;

use App\Enums\TacheStatut;
use App\Http\Controllers\NotificationController;
use App\Models\AppNotification;
use App\Models\Evenements;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rappels quotidiens (lancés une fois par jour par la commande « notifications:rappels »).
 *
 *  - tâches ouvertes : J-15, J-7, J-3 et jour J (responsables et référents), puis une relance
 *    hebdomadaire une fois en retard ;
 *  - projets ouverts : J-15, J-7, J-3, jour J (référents) et le lendemain de l'échéance ;
 *  - événements : objets à remettre encore « A faire » à J-7 et J-3 (participants), photos à prendre
 *    la veille, conflits de salle ou de personne à J-7 (administrateurs).
 *
 * Chaque rappel porte une clé : relancer la commande le même jour ne crée aucun doublon.
 */
class RappelsPlanifies
{
    /** Jalons (jours avant l'échéance) des tâches et des projets. */
    public const JALONS = [15, 7, 3, 0];

    public function __construct(private readonly Notifier $notifier, private readonly NotificationsEvenements $evenements)
    {
    }

    /** @return array<string,int> nombre de notifications créées, par famille */
    public function executer(?Carbon $jour = null): array
    {
        $jour = ($jour ?? today())->copy()->startOfDay();

        return [
            'taches' => $this->taches($jour),
            'projets' => $this->projets($jour),
            'evenements' => $this->evenements($jour),
            'supprimees' => $this->nettoyer($jour),
        ];
    }

    private function taches(Carbon $jour): int
    {
        $cree = 0;

        $taches = Tache::with(['responsables', 'projet.referents', 'createur'])
            ->whereNotIn('statut', [TacheStatut::Termine->value, TacheStatut::Annule->value])
            ->get();

        foreach ($taches as $tache) {
            $jours = $this->jours($jour, $tache->date_limite);
            $destinataires = $tache->responsables->pluck('id')->merge($tache->referents()->pluck('id'));
            $base = [
                'contexte' => '**'.$tache->titre.'** n\'est pas terminée ('.$tache->statut->label().')',
                'projet_nom' => $tache->projet?->nom,
                'echeance' => $tache->date_limite->toDateString(),
                'url' => route('taches.index').'?tache='.$tache->id,
            ];

            if (in_array($jours, self::JALONS, true)) {
                $cree += $this->notifier->envoyer($destinataires, 'tache.rappel', [
                    'titre' => $jours === 0 ? 'Échéance aujourd\'hui' : 'Échéance dans '.$jours.' jours',
                    'jalon' => $this->jalon($jours),
                    'cle' => 'tache:'.$tache->id.':J'.$jours,
                ] + $base);
            } elseif ($jours < 0 && ((-$jours - 1) % 7) === 0) {
                $retard = -$jours;
                $cree += $this->notifier->envoyer($destinataires, 'tache.retard', [
                    'titre' => 'Une tâche est en retard',
                    'contexte' => '**'.$tache->titre.'** a '.$retard.' jour'.($retard > 1 ? 's' : '').' de retard ('.$tache->statut->label().')',
                    'jalon' => $retard.' j de retard',
                    'cle' => 'tache:'.$tache->id.':retard:'.$retard,
                ] + array_diff_key($base, ['contexte' => 1]));
            }
        }

        return $cree;
    }

    private function projets(Carbon $jour): int
    {
        $cree = 0;

        $projets = Projet::with('membres')->whereNotNull('date_limite')->get()
            ->filter(fn (Projet $p) => $p->etat->estOuvert());

        foreach ($projets as $projet) {
            $jours = $this->jours($jour, $projet->date_limite);
            $referents = $projet->referents()->get();
            $base = [
                'projet_nom' => $projet->nom,
                'echeance' => $projet->date_limite->toDateString(),
                'url' => route('projets.show', $projet),
            ];

            if (in_array($jours, self::JALONS, true)) {
                $cree += $this->notifier->envoyer($referents, 'projet.rappel', [
                    'titre' => $jours === 0 ? 'Échéance du projet aujourd\'hui' : 'Échéance du projet dans '.$jours.' jours',
                    'contexte' => 'Le projet **'.$projet->nom.'** n\'est pas terminé ('.$projet->etat->label().')',
                    'jalon' => $this->jalon($jours),
                    'cle' => 'projet:'.$projet->id.':J'.$jours,
                ] + $base);
            } elseif ($jours === -1) {
                $cree += $this->notifier->envoyer($referents, 'projet.rappel', [
                    'titre' => 'Date limite du projet dépassée',
                    'contexte' => 'Le projet **'.$projet->nom.'** avait pour échéance le '.$projet->date_limite->format('d/m/Y'),
                    'jalon' => '1 j de retard',
                    'cle' => 'projet:'.$projet->id.':retard',
                ] + $base);
            }
        }

        return $cree;
    }

    private function evenements(Carbon $jour): int
    {
        $cree = 0;

        $evenements = Evenements::whereBetween('date_heure_debut', [$jour->copy(), $jour->copy()->addDays(7)->endOfDay()])
            ->with(['users', 'salles', 'objets'])
            ->get()
            ->reject(fn (Evenements $e) => $e->isCancelled());

        foreach ($evenements as $evenement) {
            $debut = Carbon::parse($evenement->date_heure_debut);
            $jours = $this->jours($jour, $debut);
            $participants = $this->evenements->participants($evenement->users->pluck('id')->all());
            $base = [
                'echeance' => $debut->toDateString(),
                'url' => route('dashboard', ['from' => $debut->toDateString(), 'to' => $debut->toDateString()]),
            ];
            $quand = $debut->copy()->locale('fr')->translatedFormat('l j F').', '.$debut->format('H\hi');

            // Objets à remettre pas encore prêts : J-7 et J-3.
            if (in_array($jours, [7, 3], true) && $evenement->objetsARemettre() === 'pending') {
                $reste = $evenement->objets->filter(fn ($o) => $o->pivot->etat !== 'Fait');
                $cree += $this->notifier->envoyer($participants, 'evenement.objets', [
                    'titre' => 'Objets à remettre : prenez contact',
                    'contexte' => '**'.$evenement->nom_event.'** ('.$quand.') : il reste '.$reste->count().' objet'.($reste->count() > 1 ? 's' : '')
                        .' à préparer ('.$reste->pluck('nom_obj')->join(', ').'). Prenez contact au plus vite avec la personne en charge.',
                    'jalon' => 'J-'.$jours,
                    'cle' => 'evenement:'.$evenement->id.':objets:J'.$jours,
                ] + $base);
            }

            // Photos : la veille.
            if ($jours === 1 && $evenement->wantsPhotos()) {
                $cree += $this->notifier->envoyer($participants, 'evenement.photos', [
                    'titre' => 'Photos à prendre demain',
                    'contexte' => '**'.$evenement->nom_event.'** ('.$quand.') : des photos sont demandées.',
                    'jalon' => 'J-1',
                    'cle' => 'evenement:'.$evenement->id.':photos',
                ] + $base);
            }

            // Conflits de salle ou de personne : J-7, aux administrateurs.
            if ($jours === 7) {
                $cree += $this->conflits($evenement, $base);
            }
        }

        return $cree;
    }

    /** Autres événements non annulés qui chevauchent celui-ci avec la même salle ou la même personne. */
    private function conflits(Evenements $evenement, array $base): int
    {
        $autres = Evenements::where('id', '!=', $evenement->id)
            ->where('date_heure_debut', '<', $evenement->date_heure_fin)
            ->where('date_heure_fin', '>', $evenement->date_heure_debut)
            ->with(['users', 'salles'])
            ->get()
            ->reject(fn (Evenements $e) => $e->isCancelled());

        $administrateurs = User::where('is_admin', 1)->get();
        $cree = 0;

        foreach ($autres as $autre) {
            $salles = $evenement->salles->pluck('id')->intersect($autre->salles->pluck('id'))->reject(fn ($id) => $id === 0);
            $personnes = $evenement->users->pluck('id')->intersect($autre->users->pluck('id'))->reject(fn ($id) => $id === 0);

            if ($salles->isEmpty() && $personnes->isEmpty()) {
                continue;
            }

            $quoi = collect();
            if ($salles->isNotEmpty()) {
                $quoi->push('salle : '.$evenement->salles->whereIn('id', $salles->all())->pluck('nom_salle')->join(', '));
            }
            if ($personnes->isNotEmpty()) {
                $quoi->push('personne : '.$evenement->users->whereIn('id', $personnes->all())->pluck('name')->join(', '));
            }

            $paire = min($evenement->id, $autre->id).'-'.max($evenement->id, $autre->id);
            $cree += $this->notifier->envoyer($administrateurs, 'admin.conflit', [
                'titre' => 'Conflit à J-7',
                'contexte' => '**'.$evenement->nom_event.'** et **'.$autre->nom_event.'** se chevauchent ('.$quoi->join(' ; ').')',
                'jalon' => 'J-7',
                'cle' => 'conflit:'.$paire,
            ] + $base);
        }

        return $cree;
    }

    /** Les notifications lues depuis plus de 30 jours sont supprimées. */
    private function nettoyer(Carbon $jour): int
    {
        return AppNotification::whereNotNull('vue_at')
            ->where('vue_at', '<', $jour->copy()->subDays(NotificationController::CONSERVATION_JOURS))
            ->delete();
    }

    private function jours(Carbon $jour, Carbon $date): int
    {
        return (int) $jour->diffInDays($date->copy()->startOfDay(), false);
    }

    private function jalon(int $jours): string
    {
        return $jours === 0 ? "Aujourd'hui" : 'J-'.$jours;
    }
}
