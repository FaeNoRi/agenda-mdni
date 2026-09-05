<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendEventEmailService
{
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

    public function send($event)
    {

        $concernedUsers = $event->users()
            ->where('is_email', 1)
            ->get();

        $hasWholeTeam = $event->users()->whereKey(0)->exists();

        if ($hasWholeTeam) {
            $teamUsers = User::query()
                ->where('is_equipe', 1)
                ->where('is_email', 1)
                ->where('id', '!=', 0)
                ->get();

            $concernedUsers = $concernedUsers
                ->merge($teamUsers)
                ->unique('id')
                ->values();
        }

        $concernedUsers = $concernedUsers->filter(fn ($u) => !empty($u->email));

        if ($concernedUsers->isEmpty()) {
            return;
        }

        $icsContent = $this->generateICS($event);

        foreach ($concernedUsers as $user) {
            $this->sendEmail($user->email, $event, $icsContent);
        }
    }

    private function generateICS($event)
    {
        $start = Carbon::parse($event->date_heure_debut)->format('Ymd\THis');
        $end = Carbon::parse($event->date_heure_fin)->format('Ymd\THis');

        // 🔁 Récupération des noms de salles
        $location = $event->salles->pluck('nom_salle')->implode(', ');

        return <<<ICS
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Floppy Bord//EN
        BEGIN:VEVENT
        UID:event-{$event->id}@floppybord
        DTSTAMP:$start
        DTSTART:$start
        DTEND:$end
        SUMMARY:{$event->nom_event}
        DESCRIPTION:{$event->desc_event}
        LOCATION:$location
        END:VEVENT
        END:VCALENDAR
        ICS;
    }


    private function sendEmail($to, $event, $icsContent)
    {
        $apiKey = config('services.brevo.api_key');

        $htmlContent = view('emails.event-notification', [
            'event' => $event,
            'color' => $this->getColor($event->type_event),
        ])->render();

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => config('services.brevo.sender_name'),
                'email' => config('services.brevo.sender_email'),
            ],
            'to' => [[ 'email' => $to ]],
            'subject' => "Nouvel événement : {$event->nom_event}",
            'htmlContent' => $htmlContent,
            'attachment' => [[
                'name' => 'event.ics',
                'content' => base64_encode($icsContent),
            ]],
        ]);

        if (!$response->successful()) {
            Log::error("Erreur envoi Brevo : " . $response->body());
        }
    }
}
