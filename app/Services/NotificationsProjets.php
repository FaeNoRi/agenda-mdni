<?php

namespace App\Services;

use App\Enums\ProjetEtat;
use App\Enums\TacheStatut;
use App\Models\Commentaire;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Notifications internes du module Projets & tâches (déclencheurs décidés le 09/10/2026).
 * Appelé par les contrôleurs ; ne lève jamais d'exception : une notification manquée ne doit
 * pas faire échouer l'action de la personne.
 */
class NotificationsProjets
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    // ---- Tâches ----------------------------------------------------------

    /** Des personnes deviennent responsables d'une tâche. */
    public function tacheAssignee(Tache $tache, iterable $userIds, User $par): void
    {
        $this->sans(function () use ($tache, $userIds, $par) {
            $this->notifier->envoyer($userIds, 'tache.assignee', [
                'titre' => 'Une tâche vous a été confiée',
                'contexte' => $par->name.' vous a assigné **'.$tache->titre.'**',
            ] + $this->tacheInfos($tache), $par);
        });
    }

    /** Des personnes ne sont plus responsables d'une tâche. */
    public function tacheRetiree(Tache $tache, iterable $userIds, User $par): void
    {
        $this->sans(function () use ($tache, $userIds, $par) {
            $this->notifier->envoyer($userIds, 'tache.retiree', [
                'titre' => "Vous n'êtes plus responsable d'une tâche",
                'contexte' => $par->name.' vous a retiré de **'.$tache->titre.'**',
                'projet_nom' => $tache->projet?->nom,
                'url' => $tache->projet ? route('projets.show', $tache->projet) : null,
            ], $par);
        });
    }

    /** Changement de statut : prévient les référents (« À valider » et « Terminé » ont leur type). */
    public function tacheStatut(Tache $tache, TacheStatut $avant, User $par): void
    {
        $this->sans(function () use ($tache, $avant, $par) {
            $apres = $tache->statut;
            $infos = $this->tacheInfos($tache);

            [$type, $titre, $contexte] = match ($apres) {
                TacheStatut::AValider => ['tache.a_valider', 'Une tâche attend votre validation',
                    '**'.$tache->titre.'** est passée à « À valider » ('.$par->name.')'],
                TacheStatut::Termine => ['tache.terminee', 'Une tâche est terminée',
                    '**'.$tache->titre.'** est terminée ('.$par->name.')'],
                default => ['tache.statut', "Le statut d'une tâche a changé",
                    '**'.$tache->titre.'** : '.$avant->label().' → '.$apres->label().' ('.$par->name.')'
                    .($tache->raison && $apres->exigeRaison() ? ' : '.$tache->raison : '')],
            };

            $this->notifier->envoyer($tache->referents(), $type, ['titre' => $titre, 'contexte' => $contexte] + $infos, $par);

            $this->tachesTerminees($tache, $par);
        });
    }

    /** Date, titre ou projet d'une tâche modifiés : les responsables sont prévenus. */
    public function tacheModifiee(Tache $tache, array $changements, iterable $sauf, User $par): void
    {
        $this->sans(function () use ($tache, $changements, $sauf, $par) {
            if (!$changements) {
                return;
            }

            $destinataires = $tache->responsables->pluck('id')->diff(collect($sauf)->map(fn ($u) => $u instanceof User ? $u->id : (int) $u));

            $this->notifier->envoyer($destinataires, 'tache.modifiee', [
                'titre' => 'Une de vos tâches a été modifiée',
                'contexte' => $par->name.' a modifié **'.$tache->titre.'** : '.implode(', ', $changements),
            ] + $this->tacheInfos($tache), $par);
        });
    }

    /** Appelé avant la suppression : on a besoin des responsables et du projet. */
    public function tacheSupprimee(Tache $tache, User $par): void
    {
        $this->sans(function () use ($tache, $par) {
            $this->notifier->envoyer($tache->responsables, 'tache.supprimee', [
                'titre' => 'Une de vos tâches a été supprimée',
                'contexte' => $par->name.' a supprimé **'.$tache->titre.'**',
                'projet_nom' => $tache->projet?->nom,
                'url' => $tache->projet ? route('projets.show', $tache->projet) : null,
            ], $par);
        });
    }

    /** Commentaire sur une tâche ou un projet. */
    public function commentaire(Model $cible, Commentaire $commentaire, User $par): void
    {
        $this->sans(function () use ($cible, $commentaire, $par) {
            $extrait = '« '.mb_strimwidth(preg_replace('/\s+/', ' ', trim($commentaire->contenu)), 0, 110, '…').' »';

            if ($cible instanceof Tache) {
                $cible->loadMissing(['responsables', 'projet.referents', 'createur']);
                $destinataires = $cible->responsables->pluck('id')
                    ->merge($cible->referents()->pluck('id'))
                    ->merge($cible->commentaires()->pluck('user_id'));

                $this->notifier->envoyer($destinataires, 'tache.commentaire', [
                    'titre' => 'Nouveau commentaire sur une tâche',
                    'contexte' => '**'.$par->name.'** sur **'.$cible->titre.'** : '.$extrait,
                ] + $this->tacheInfos($cible), $par);

                return;
            }

            /** @var Projet $cible */
            $this->notifier->envoyer($cible->personnesImpliquees(), 'projet.commentaire', [
                'titre' => 'Nouveau commentaire sur un projet',
                'contexte' => '**'.$par->name.'** sur **'.$cible->nom.'** : '.$extrait,
                'projet_nom' => $cible->nom,
                'url' => route('projets.show', $cible),
            ], $par);
        });
    }

    // ---- Projets ---------------------------------------------------------

    /** Changement d'état : « À valider » prévient les référents ; les autres changements, tous les impliqués. */
    public function projetEtat(Projet $projet, ProjetEtat $avant, User $par): void
    {
        $this->sans(function () use ($projet, $avant, $par) {
            $apres = $projet->etat;
            $infos = ['projet_nom' => $projet->nom, 'url' => route('projets.show', $projet)]
                + $this->jalon($projet->date_limite);

            $referents = $projet->referents()->get();
            $autres = $projet->personnesImpliquees()->reject(fn ($u) => $referents->contains('id', $u->id));

            if ($apres === ProjetEtat::AValider) {
                $this->notifier->envoyer($referents, 'projet.a_valider', [
                    'titre' => 'Un projet attend votre validation',
                    'contexte' => '**'.$projet->nom.'** est passé à « À valider » ('.$par->name.')',
                ] + $infos, $par);
                $destinataires = $autres;
            } else {
                $destinataires = $referents->merge($autres);
            }

            $this->notifier->envoyer($destinataires, 'projet.etat', [
                'titre' => "L'état d'un projet a changé",
                'contexte' => '**'.$projet->nom.'** : '.$avant->label().' → '.$apres->label().' ('.$par->name.')'
                    .($projet->raison && $apres->exigeRaison() ? ' : '.$projet->raison : ''),
            ] + $infos, $par);
        });
    }

    /** Des personnes deviennent référentes, ou ne le sont plus. */
    public function projetReferents(Projet $projet, iterable $ajoutes, iterable $retires, User $par): void
    {
        $this->sans(function () use ($projet, $ajoutes, $retires, $par) {
            $this->notifier->envoyer($ajoutes, 'projet.referent', [
                'titre' => 'Vous êtes référent d\'un projet',
                'contexte' => $par->name.' vous a ajouté comme référent de **'.$projet->nom.'**',
                'projet_nom' => $projet->nom,
                'url' => route('projets.show', $projet),
            ], $par);

            $this->notifier->envoyer($retires, 'projet.referent', [
                'titre' => 'Vous n\'êtes plus référent d\'un projet',
                'contexte' => $par->name.' vous a retiré comme référent de **'.$projet->nom.'**',
                'projet_nom' => $projet->nom,
                'url' => route('projets.show', $projet),
            ], $par);
        });
    }

    /** Appelé avant la suppression. */
    public function projetSupprime(Projet $projet, User $par): void
    {
        $this->sans(function () use ($projet, $par) {
            $this->notifier->envoyer($projet->personnesImpliquees(), 'projet.supprime', [
                'titre' => 'Un projet a été supprimé',
                'contexte' => $par->name.' a supprimé **'.$projet->nom.'**',
            ], $par);
        });
    }

    /** La dernière tâche ouverte d'un projet vient d'être terminée ou annulée. */
    private function tachesTerminees(Tache $tache, User $par): void
    {
        $projet = $tache->projet;

        if (!$projet || !in_array($tache->statut, [TacheStatut::Termine, TacheStatut::Annule], true)) {
            return;
        }

        $projet->unsetRelation('taches');

        if ($projet->tachesOuvertes() > 0 || $projet->resume()['terminees'] === 0 || !$projet->etat->estOuvert()) {
            return;
        }

        $this->notifier->envoyer($projet->referents()->get(), 'projet.taches_terminees', [
            'titre' => 'Toutes les tâches du projet sont terminées',
            'contexte' => 'Toutes les tâches de **'.$projet->nom.'** sont terminées : vous pouvez clore le projet.',
            'projet_nom' => $projet->nom,
            'url' => route('projets.show', $projet),
        ], $par);
    }

    // ---- Outils ----------------------------------------------------------

    /** Projet, échéance et lien communs aux notifications d'une tâche. */
    private function tacheInfos(Tache $tache): array
    {
        return [
            'projet_nom' => $tache->projet?->nom,
            'url' => route('taches.index').'?tache='.$tache->id,
        ] + $this->jalon($tache->date_limite);
    }

    /** Échéance et pastille associée : « J-3 », « Aujourd'hui », « 4 j de retard ». */
    private function jalon(?Carbon $date): array
    {
        if (!$date) {
            return [];
        }

        $jours = (int) today()->diffInDays($date->copy()->startOfDay(), false);

        return [
            'echeance' => $date->toDateString(),
            'jalon' => $jours < 0 ? (-$jours).' j de retard' : ($jours === 0 ? "Aujourd'hui" : 'J-'.$jours),
        ];
    }

    private function sans(callable $envoi): void
    {
        rescue($envoi, report: true);
    }
}
