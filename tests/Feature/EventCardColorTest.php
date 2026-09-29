<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCardColorTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $type): Evenements
    {
        return Evenements::create([
            'nom_event' => 'Test', 'type_event' => $type, 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 1,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'T',
            'date_heure_debut' => '2026-09-29 09:00:00', 'date_heure_fin' => '2026-09-29 10:00:00',
        ]);
    }

    private function cards(): string
    {
        $admin = User::factory()->create();

        return $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-29&to=2026-09-29')->assertOk()->getContent();
    }

    public function test_rdv_uses_the_same_color_as_reunion_and_not_primary(): void
    {
        $this->event('RDV');
        $html = $this->cards();

        $this->assertStringContainsString('border-warning', $html);
        $this->assertStringContainsString('bg-warning-lt text-warning', $html);
        $this->assertStringNotContainsString('bg-primary-lt', $html);
    }

    public function test_cancelled_event_card_uses_solid_dark_not_light(): void
    {
        $this->event('Annule');
        $html = $this->cards();

        $this->assertStringContainsString('bg-dark text-white', $html);
        $this->assertStringNotContainsString('bg-dark-lt', $html);
    }

    public function test_other_types_keep_the_light_badge_treatment(): void
    {
        $this->event('Atelier');
        $html = $this->cards();

        $this->assertStringContainsString('bg-success-lt text-success', $html);
    }
}
