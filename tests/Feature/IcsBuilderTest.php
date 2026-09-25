<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\Salles;
use App\Services\IcsBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcsBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(array $overrides = []): Evenements
    {
        return Evenements::create(array_merge([
            'nom_event' => 'Point projet', 'type_event' => 'Réunion', 'type_public' => 'Autres',
            'desc_event' => 'Ordre du jour', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'Test',
            'date_heure_debut' => '2026-09-17 10:30:00', 'date_heure_fin' => '2026-09-17 11:30:00',
        ], $overrides));
    }

    private function ics(Evenements $event, string $method = IcsBuilder::METHOD_PUBLISH): string
    {
        return app(IcsBuilder::class)->build($event->load('salles'), $method);
    }

    private function unfold(string $ics): string
    {
        return str_replace("\r\n ", '', $ics);
    }

    public function test_uid_is_stable_across_updates(): void
    {
        $event = $this->event();
        $first = $this->ics($event);
        $event->update(['nom_event' => 'Point projet (modifié)']);

        $this->assertStringContainsString('UID:event-'.$event->id.'@floppybord', $first);
        $this->assertStringContainsString('UID:event-'.$event->id.'@floppybord', $this->ics($event));
    }

    public function test_sequence_and_dtstamp_increase_between_versions(): void
    {
        $event = $this->event();

        Carbon::setTestNow(Carbon::parse('2026-09-20 10:00:00', 'UTC'));
        $v1 = $this->ics($event);
        Carbon::setTestNow(Carbon::parse('2026-09-20 10:05:00', 'UTC'));
        $v2 = $this->ics($event);

        preg_match('/SEQUENCE:(\d+)/', $v1, $s1);
        preg_match('/SEQUENCE:(\d+)/', $v2, $s2);
        $this->assertGreaterThan((int) $s1[1], (int) $s2[1]);

        $this->assertStringContainsString('DTSTAMP:20260920T100000Z', $v1);
        $this->assertStringContainsString('DTSTAMP:20260920T100500Z', $v2);
        $this->assertStringNotContainsString('DTSTAMP:20260917', $v1);
    }

    public function test_has_method_and_crlf_line_endings_only(): void
    {
        $ics = $this->ics($this->event());

        $this->assertStringContainsString("METHOD:PUBLISH\r\n", $ics);
        $this->assertStringContainsString("STATUS:CONFIRMED\r\n", $ics);
        $this->assertSame(0, preg_match('/(?<!\r)\n/', $ics), 'Retour à la ligne sans CR détecté');
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }

    public function test_times_are_converted_to_utc(): void
    {
        // 10:30 heure de Paris le 17 septembre (heure d'été, UTC+2) = 08:30 UTC
        $ics = $this->ics($this->event());

        $this->assertStringContainsString('DTSTART:20260917T083000Z', $ics);
        $this->assertStringContainsString('DTEND:20260917T093000Z', $ics);
    }

    public function test_all_day_event_uses_date_values_with_exclusive_end(): void
    {
        $ics = $this->ics($this->event([
            'date_heure_debut' => '2026-09-17 00:00:00', 'date_heure_fin' => '2026-09-17 00:00:00',
        ]));

        $this->assertStringContainsString('DTSTART;VALUE=DATE:20260917', $ics);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20260918', $ics);
    }

    public function test_text_is_escaped_and_cannot_inject_properties(): void
    {
        $ics = $this->ics($this->event([
            'nom_event' => 'Atelier, café; suite',
            'desc_event' => "Ligne 1\nLigne 2\\fin\r\nEND:VEVENT",
        ]));

        $flat = $this->unfold($ics);
        $this->assertStringContainsString('SUMMARY:Atelier\, café\; suite', $flat);
        $this->assertStringContainsString('DESCRIPTION:Ligne 1\nLigne 2\\\\fin\nEND:VEVENT', $flat);
        $this->assertSame(1, substr_count($flat, "\r\nEND:VEVENT\r\n"));
    }

    public function test_long_lines_are_folded_without_breaking_utf8(): void
    {
        $description = str_repeat('Événement très important à préparer. ', 12);
        $ics = $this->ics($this->event(['desc_event' => $description]));

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'));
        }
        $this->assertStringContainsString('DESCRIPTION:'.trim($description), $this->unfold($ics));
    }

    public function test_location_comes_from_rooms_and_is_omitted_when_none(): void
    {
        $event = $this->event();
        $this->assertStringNotContainsString('LOCATION:', $this->ics($event));

        $event->salles()->attach(Salles::create(['nom_salle' => 'Innovation', 'type_salle' => 'Salle'])->id);
        $this->assertStringContainsString('LOCATION:Innovation', $this->ics($event->fresh()));
    }

    public function test_cancel_method_marks_event_cancelled(): void
    {
        $ics = $this->ics($this->event(), IcsBuilder::METHOD_CANCEL);

        $this->assertStringContainsString("METHOD:CANCEL\r\n", $ics);
        $this->assertStringContainsString("STATUS:CANCELLED\r\n", $ics);
    }
}
