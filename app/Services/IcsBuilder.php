<?php

namespace App\Services;

use App\Models\Evenements;
use Carbon\Carbon;

/**
 * Génère un fichier iCalendar (RFC 5545 / iTIP RFC 5546) pour un événement.
 *
 * Pour qu'un client (Outlook notamment) remplace un événement déjà présent au lieu d'en créer un
 * second, il faut : le même UID à chaque envoi, un SEQUENCE / DTSTAMP qui augmente, et un METHOD.
 */
class IcsBuilder
{
    public const METHOD_PUBLISH = 'PUBLISH';
    public const METHOD_CANCEL = 'CANCEL';

    private const SEQUENCE_BASE = 1735689600; // 2025-01-01 00:00 UTC

    public function build(Evenements $event, string $method = self::METHOD_PUBLISH): string
    {
        $cancelled = $method === self::METHOD_CANCEL;
        $now = Carbon::now('UTC');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MDNI Calaisis//Floppy Bord//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:' . $method,
            'BEGIN:VEVENT',
            'UID:' . self::uid($event),
            'DTSTAMP:' . $now->format('Ymd\THis\Z'),
            'SEQUENCE:' . max(0, $now->timestamp - self::SEQUENCE_BASE),
        ];

        if ($event->updated_at) {
            $lines[] = 'LAST-MODIFIED:' . $event->updated_at->copy()->utc()->format('Ymd\THis\Z');
        }

        array_push($lines, ...$this->dateLines($event));

        $lines[] = 'SUMMARY:' . $this->escape((string) $event->nom_event);

        $description = trim((string) $event->desc_event);
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . $this->escape($description);
        }

        $location = $event->salles->pluck('nom_salle')->implode(', ');
        if ($location !== '') {
            $lines[] = 'LOCATION:' . $this->escape($location);
        }

        $lines[] = 'STATUS:' . ($cancelled ? 'CANCELLED' : 'CONFIRMED');

        $organizerEmail = config('services.brevo.sender_email');
        if ($organizerEmail) {
            $name = str_replace(['"', ';', ':', ','], ' ', (string) config('services.brevo.sender_name', 'MDNI'));
            $lines[] = 'ORGANIZER;CN="' . trim($name) . '":mailto:' . $organizerEmail;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([$this, 'fold'], $lines)) . "\r\n";
    }

    public static function uid(Evenements $event): string
    {
        return 'event-' . $event->id . '@floppybord';
    }

    private function dateLines(Evenements $event): array
    {
        $start = Carbon::parse($event->date_heure_debut);
        $end = Carbon::parse($event->date_heure_fin);

        // Convention de l'application : 00:00 → 00:00 = journée entière.
        if ($start->format('H:i') === '00:00' && $end->format('H:i') === '00:00') {
            return [
                'DTSTART;VALUE=DATE:' . $start->format('Ymd'),
                'DTEND;VALUE=DATE:' . $end->copy()->addDay()->format('Ymd'),
            ];
        }

        return [
            'DTSTART:' . $start->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $end->copy()->utc()->format('Ymd\THis\Z'),
        ];
    }

    private function escape(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $text
        );
    }

    /** Repli des lignes à 75 octets maximum, sans couper un caractère UTF-8. */
    private function fold(string $line): string
    {
        $length = strlen($line);
        if ($length <= 75) {
            return $line;
        }

        $chunks = [];
        $offset = 0;
        $limit = 75;

        while ($offset < $length) {
            if ($offset + $limit >= $length) {
                $chunks[] = substr($line, $offset);
                break;
            }

            $take = $limit;
            while ($take > 0 && (ord($line[$offset + $take]) & 0xC0) === 0x80) {
                $take--;
            }

            $chunks[] = substr($line, $offset, $take);
            $offset += $take;
            $limit = 74;
        }

        return implode("\r\n ", $chunks);
    }
}
