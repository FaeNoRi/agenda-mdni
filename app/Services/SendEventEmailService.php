<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendEventEmailService
{
    /** Événement nouvellement créé. */
    public const CREATED = 'created';
    /** Événement modifié (personne déjà concernée avant la modification). */
    public const UPDATED = 'updated';
    /** Événement supprimé ou passé au type "Annulé". */
    public const CANCELLED = 'cancelled';
    /** Personne ajoutée à un événement existant. */
    public const ADDED = 'added';
    /** Personne retirée d'un événement qui, lui, continue d'exister. */
    public const REMOVED = 'removed';
    /** Événement annulé puis rétabli. */
    public const REINSTATED = 'reinstated';

    public function __construct(private IcsBuilder $ics)
    {
    }

    private function getColor($type)
    {
        return [
            'RDV' => '#0d6efd',
            'Location' => '#6c757d',
            'Permanence' => '#6c757d',
            'Atelier' => '#198754',
            'Interne' => '#0dcaf0',
            'Information' => '#0dcaf0',
            'Réunion' => '#ffc107',
            'Exceptionnel' => '#dc3545',
            'FN-RDV' => '#dc3545',
            'FN-Atelier' => '#dc3545',
            'Annulé' => '#212529',
        ][$type] ?? '#888888';
    }

    private function isCancelledType($type): bool
    {
        return in_array($type, ['Annule', 'Annulé'], true);
    }

    /** État d'un événement à conserver avant modification, pour détecter ce qui change et qui est concerné. */
    public function snapshot($event): array
    {
        $event->load(['users', 'salles']);

        return [
            'start' => Carbon::parse($event->date_heure_debut),
            'end' => Carbon::parse($event->date_heure_fin),
            'salles' => $event->salles->pluck('nom_salle')->sort()->values()->all(),
            'users' => $event->users->pluck('name')->sort()->values()->all(),
            'user_ids' => $event->users->pluck('id')->all(),
            'cancelled' => $this->isCancelledType($event->type_event),
        ];
    }

    /**
     * Notifie les personnes concernées par un événement, avec un fichier .ics.
     *
     * @param  string      $mode    CREATED, UPDATED ou CANCELLED (suppression de l'événement).
     * @param  array|null  $before  snapshot() pris avant une modification : permet de distinguer les personnes
     *                              déjà concernées, ajoutées ou retirées, et de lister ce qui a changé.
     *
     * N'échoue jamais : un problème d'envoi ne doit pas faire échouer la sauvegarde de l'événement.
     */
    public function send($event, string $mode = self::CREATED, ?array $before = null): void
    {
        try {
            $event->load(['users', 'salles']);

            $current = $this->recipients($event->users->pluck('id')->all());
            $cancelledNow = $mode === self::CANCELLED || $this->isCancelledType($event->type_event);
            $plan = [];

            if ($mode === self::UPDATED && $before) {
                $previous = $this->recipients($before['user_ids']);
                $removed = $previous->diffKeys($current);

                if ($cancelledNow) {
                    // Déjà annulé avant cette modification : les agendas ont été prévenus, rien à renvoyer.
                    if ($before['cancelled']) {
                        return;
                    }
                    $plan[] = [$current, self::CANCELLED];
                    $plan[] = [$removed, self::REMOVED];
                } elseif ($before['cancelled']) {
                    $plan[] = [$current, self::REINSTATED];
                } else {
                    $plan[] = [$current->intersectByKeys($previous), self::UPDATED];
                    $plan[] = [$current->diffKeys($previous), self::ADDED];
                    $plan[] = [$removed, self::REMOVED];
                }
            } elseif ($cancelledNow) {
                $plan[] = [$current, self::CANCELLED];
            } else {
                $plan[] = [$current, self::CREATED];
            }

            $changes = ($mode === self::UPDATED && $before) ? $this->changes($before, $this->snapshot($event)) : [];

            foreach ($plan as [$users, $situation]) {
                $this->sendTo($users, $event, $situation, $changes);
            }
        } catch (\Throwable $e) {
            Log::error('Erreur notification événement : ' . $e->getMessage());
        }
    }

    /** Destinataires (indexés par id) : animateurs cités + équipe entière si "Toute l'équipe" (id 0). */
    private function recipients(array $userIds): Collection
    {
        $users = User::query()->whereIn('id', $userIds)->where('is_email', 1)->get();

        if (in_array(0, $userIds, true)) {
            $team = User::query()
                ->where('is_equipe', 1)
                ->where('is_email', 1)
                ->where('id', '!=', 0)
                ->get();

            $users = $users->merge($team);
        }

        return $users->filter(fn ($u) => !empty($u->email))->keyBy('id');
    }

    /** Liste des champs modifiés : [['label' => 'Horaire', 'old' => '10:30 – 11:30', 'new' => '14:00 – 15:00'], ...]. */
    private function changes(array $before, array $after): array
    {
        $rows = [];
        $add = function (string $label, string $old, string $new) use (&$rows) {
            if ($old !== $new) {
                $rows[] = ['label' => $label, 'old' => $old, 'new' => $new];
            }
        };

        $add('Date', $this->dateLabel($before['start'], $before['end']), $this->dateLabel($after['start'], $after['end']));
        $add('Horaire', $this->timeLabel($before['start'], $before['end']), $this->timeLabel($after['start'], $after['end']));
        $add('Salle(s)', implode(', ', $before['salles']) ?: 'Aucune', implode(', ', $after['salles']) ?: 'Aucune');
        $add('Animateur(s)', implode(', ', $before['users']) ?: 'Non précisé', implode(', ', $after['users']) ?: 'Non précisé');

        return $rows;
    }

    private function dateLabel(Carbon $start, Carbon $end): string
    {
        $day = fn (Carbon $d) => mb_convert_case($d->copy()->locale('fr')->translatedFormat('l d F Y'), MB_CASE_TITLE, 'UTF-8');

        return $start->isSameDay($end) ? $day($start) : $day($start) . ' → ' . $day($end);
    }

    private function timeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->format('H:i') === '00:00' && $end->format('H:i') === '00:00') {
            return 'Journée entière';
        }

        return $start->format('H:i') . ' – ' . $end->format('H:i');
    }

    private function sendTo(Collection $users, $event, string $situation, array $changes): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $method = in_array($situation, [self::CANCELLED, self::REMOVED], true)
            ? IcsBuilder::METHOD_CANCEL
            : IcsBuilder::METHOD_PUBLISH;

        $icsContent = $this->ics->build($event, $method);

        foreach ($users as $user) {
            try {
                $this->sendEmail($user->email, $event, $icsContent, $situation, $changes);
            } catch (\Throwable $e) {
                Log::error("Erreur envoi mail événement à {$user->email} : " . $e->getMessage());
            }
        }
    }

    private function subject($event, string $situation): string
    {
        return match ($situation) {
            self::UPDATED => "Événement modifié : {$event->nom_event}",
            self::CANCELLED => "Événement annulé : {$event->nom_event}",
            self::REMOVED => "Vous n'êtes plus affecté(e) : {$event->nom_event}",
            self::REINSTATED => "Événement rétabli : {$event->nom_event}",
            default => "Nouvel événement : {$event->nom_event}",
        };
    }

    private function sendEmail($to, $event, $icsContent, string $situation, array $changes): void
    {
        $htmlContent = view('emails.event-notification', [
            'event' => $event,
            'color' => $this->getColor($event->type_event),
            'mode' => $situation,
            'changes' => $changes,
        ])->render();

        $response = Http::timeout(10)->withHeaders([
            'api-key' => config('services.brevo.api_key'),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => config('services.brevo.sender_name'),
                'email' => config('services.brevo.sender_email'),
            ],
            'to' => [['email' => $to]],
            'subject' => $this->subject($event, $situation),
            'htmlContent' => $htmlContent,
            'attachment' => [[
                'name' => 'event.ics',
                'content' => base64_encode($icsContent),
            ]],
        ]);

        if (!$response->successful()) {
            Log::error('Erreur envoi Brevo : ' . $response->body());
        }
    }
}
