<?php

namespace App\Services;

use App\Models\Evenements;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Notifications internes des événements : création, modification, annulation ou rétablissement,
 * ajout ou retrait d'un participant. Mêmes situations (et mêmes personnes concernées) que l'e-mail
 * d'événement, mais sans condition sur l'adresse e-mail : « Toute l'équipe » (id 0) vise les membres
 * de l'équipe. Ne lève jamais d'exception.
 */
class NotificationsEvenements
{
    public function __construct(private readonly Notifier $notifier, private readonly SendEventEmailService $etats)
    {
    }

    /** État à conserver avant modification (même instantané que l'e-mail). */
    public function snapshot(Evenements $evenement): array
    {
        return $this->etats->snapshot($evenement);
    }

    /**
     * @param string     $mode   SendEventEmailService::CREATED, UPDATED ou CANCELLED (suppression)
     * @param array|null $before snapshot() pris avant une modification
     */
    public function envoyer(Evenements $evenement, string $mode, ?array $before, ?User $par): void
    {
        rescue(function () use ($evenement, $mode, $before, $par) {
            $evenement->load(['users', 'salles']);
            $actuels = $this->destinataires($evenement->users->pluck('id')->all());
            $annuleMaintenant = $mode === SendEventEmailService::CANCELLED || $evenement->isCancelled();

            if ($mode === SendEventEmailService::UPDATED && $before) {
                $avant = $this->destinataires($before['user_ids']);
                $retires = $avant->diff($actuels);

                if ($annuleMaintenant) {
                    if ($before['cancelled']) {
                        return;
                    }
                    $this->dire($actuels, 'evenement.annule', $evenement, 'Événement annulé', 'est annulé', $par);
                    $this->dire($retires, 'evenement.participation', $evenement, "Vous n'êtes plus affecté à un événement", 'ne vous concerne plus', $par);

                    return;
                }

                if ($before['cancelled']) {
                    $this->dire($actuels, 'evenement.annule', $evenement, 'Événement rétabli', 'est rétabli', $par);

                    return;
                }

                $changements = $this->changements($before, $this->etats->snapshot($evenement));
                $this->dire($actuels->intersect($avant), 'evenement.modifie', $evenement, 'Événement modifié', $changements ? implode(' ; ', $changements) : null, $par, (bool) $changements);
                $this->dire($actuels->diff($avant), 'evenement.participation', $evenement, 'Vous êtes ajouté à un événement', 'vous concerne maintenant', $par);
                $this->dire($retires, 'evenement.participation', $evenement, "Vous n'êtes plus affecté à un événement", 'ne vous concerne plus', $par);

                return;
            }

            if ($annuleMaintenant) {
                $this->dire($actuels, 'evenement.annule', $evenement, 'Événement annulé', $mode === SendEventEmailService::CANCELLED ? 'est supprimé' : 'est annulé', $par);

                return;
            }

            $this->dire($actuels, 'evenement.cree', $evenement, 'Nouvel événement', null, $par);
        }, report: true);
    }

    /** Personnes citées, plus l'équipe entière pour « Toute l'équipe » (id 0). */
    private function destinataires(array $userIds): Collection
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id);

        if ($ids->contains(0)) {
            $ids = $ids->merge(User::where('is_equipe', 1)->where('id', '!=', 0)->pluck('id'));
        }

        return $ids->reject(fn ($id) => $id === 0)->unique()->values();
    }

    private function dire(Collection $ids, string $type, Evenements $evenement, string $titre, ?string $detail, ?User $par, bool $envoyer = true): void
    {
        if (!$envoyer || $ids->isEmpty()) {
            return;
        }

        $debut = Carbon::parse($evenement->date_heure_debut);
        $fin = Carbon::parse($evenement->date_heure_fin);
        $jours = (int) today()->diffInDays($debut->copy()->startOfDay(), false);

        $quand = $this->jour($debut).', '.$this->horaire($debut, $fin);
        $salles = $evenement->salles->where('id', '!=', 0)->pluck('nom_salle')->join(', ');
        $contexte = '**'.$evenement->nom_event.'**'
            .($detail ? ' '.$detail : '')
            .' · '.$quand.($salles ? ' · '.$salles : '');

        $this->notifier->envoyer($ids, $type, [
            'titre' => $titre,
            'contexte' => $contexte,
            'echeance' => $debut->toDateString(),
            'jalon' => $jours < 0 ? 'Passé' : ($jours === 0 ? "Aujourd'hui" : 'J-'.$jours),
            'url' => route('dashboard', ['from' => $debut->toDateString(), 'to' => $debut->toDateString()]),
        ], $par);
    }

    /** Date, horaire ou salles modifiés (les animateurs ont leurs propres notifications ajout/retrait). */
    private function changements(array $avant, array $apres): array
    {
        $liste = [];

        if ($avant['start']->toDateString() !== $apres['start']->toDateString() || $avant['end']->toDateString() !== $apres['end']->toDateString()) {
            $liste[] = 'date : '.$this->jour($avant['start']).' → '.$this->jour($apres['start']);
        }
        if ($this->horaire($avant['start'], $avant['end']) !== $this->horaire($apres['start'], $apres['end'])) {
            $liste[] = 'horaire : '.$this->horaire($avant['start'], $avant['end']).' → '.$this->horaire($apres['start'], $apres['end']);
        }
        if ($avant['salles'] !== $apres['salles']) {
            $liste[] = 'salle : '.(implode(', ', $avant['salles']) ?: 'aucune').' → '.(implode(', ', $apres['salles']) ?: 'aucune');
        }

        return $liste;
    }

    private function jour(Carbon $date): string
    {
        return mb_convert_case($date->copy()->locale('fr')->translatedFormat('l j F'), MB_CASE_TITLE, 'UTF-8');
    }

    private function horaire(Carbon $debut, Carbon $fin): string
    {
        if ($debut->format('H:i') === '00:00' && $fin->format('H:i') === '00:00') {
            return 'journée entière';
        }

        return $debut->format('H\hi').' - '.$fin->format('H\hi');
    }
}
