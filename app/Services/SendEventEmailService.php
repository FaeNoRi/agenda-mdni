<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendEventEmailService
{
    public const CREATED = 'created';
    public const UPDATED = 'updated';
    public const CANCELLED = 'cancelled';

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

    /**
     * Notifie les personnes concernées par un événement, avec un fichier .ics.
     *
     * @param  string  $mode             CREATED, UPDATED ou CANCELLED (suppression de l'événement).
     * @param  array   $previousUserIds  Animateurs avant modification : les personnes retirées reçoivent
     *                                   une annulation pour que l'événement disparaisse de leur agenda.
     *
     * N'échoue jamais : un problème d'envoi ne doit pas faire échouer la sauvegarde de l'événement.
     */
    public function send($event, string $mode = self::CREATED, array $previousUserIds = []): void
    {
        try {
            $event->load(['users', 'salles']);

            // Un événement passé au type "Annulé" doit disparaître des agendas.
            if (in_array($event->type_event, ['Annule', 'Annulé'], true)) {
                $mode = self::CANCELLED;
            }

            $recipients = $this->recipients($event->users->pluck('id')->all());
            $method = $mode === self::CANCELLED ? IcsBuilder::METHOD_CANCEL : IcsBuilder::METHOD_PUBLISH;

            $this->sendTo($recipients, $event, $mode, $method);

            if ($mode === self::UPDATED && $previousUserIds) {
                $removed = $this->recipients($previousUserIds)->diffKeys($recipients);
                $this->sendTo($removed, $event, self::CANCELLED, IcsBuilder::METHOD_CANCEL);
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

    private function sendTo(Collection $users, $event, string $mode, string $method): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $icsContent = $this->ics->build($event, $method);

        foreach ($users as $user) {
            try {
                $this->sendEmail($user->email, $event, $icsContent, $mode);
            } catch (\Throwable $e) {
                Log::error("Erreur envoi mail événement à {$user->email} : " . $e->getMessage());
            }
        }
    }

    private function subject($event, string $mode): string
    {
        return match ($mode) {
            self::UPDATED => "Événement modifié : {$event->nom_event}",
            self::CANCELLED => "Événement annulé : {$event->nom_event}",
            default => "Nouvel événement : {$event->nom_event}",
        };
    }

    private function sendEmail($to, $event, $icsContent, string $mode): void
    {
        $htmlContent = view('emails.event-notification', [
            'event' => $event,
            'color' => $this->getColor($event->type_event),
            'mode' => $mode,
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
            'subject' => $this->subject($event, $mode),
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
