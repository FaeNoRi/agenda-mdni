<?php

namespace Tests\Feature;

use App\Models\Conge;
use App\Models\Evenements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardConflictBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(array $overrides = []): Evenements
    {
        return Evenements::create(array_merge([
            'nom_event' => 'Point projet VEOLIA',
            'type_event' => 'Réunion',
            'type_public' => 'Autres',
            'desc_event' => 'x',
            'commanditaire_event' => 'MDNI',
            'nbpart' => 4,
            'facture' => 'Non',
            'devis' => 'Non',
            'objet' => 'Non',
            'auteur' => 'Test',
            'date_heure_debut' => '2026-09-17 10:30:00',
            'date_heure_fin' => '2026-09-17 11:30:00',
        ], $overrides));
    }

    public function test_half_day_conge_not_overlapping_event_does_not_trigger_conflict(): void
    {
        $admin = User::factory()->create();
        $antoine = User::factory()->create(['name' => 'Cadet Antoine']);

        // Congé du matin uniquement (00:00-09:00), l'événement est à 10:30-11:30 : pas de chevauchement réel.
        Conge::create(['user_id' => $antoine->id, 'start' => '2026-09-17 00:00:00', 'end' => '2026-09-17 09:00:00']);

        $event = $this->createEvent();
        $event->users()->sync([$antoine->id]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17');

        $response->assertOk();
        $response->assertSee('Cadet Antoine');
        $response->assertDontSee('Congé · Conflit');
    }

    public function test_full_day_conge_overlapping_event_triggers_conflict(): void
    {
        $admin = User::factory()->create();
        $antoine = User::factory()->create(['name' => 'Cadet Antoine']);

        Conge::create(['user_id' => $antoine->id, 'start' => '2026-09-17 00:00:00', 'end' => '2026-09-18 00:00:00']);

        $event = $this->createEvent();
        $event->users()->sync([$antoine->id]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17');

        $response->assertOk();
        $response->assertSee('Congé · Conflit');
    }

    public function test_conge_overlapping_only_a_cancelled_event_does_not_trigger_conflict(): void
    {
        $admin = User::factory()->create();
        $antoine = User::factory()->create(['name' => 'Cadet Antoine']);

        Conge::create(['user_id' => $antoine->id, 'start' => '2026-09-17 00:00:00', 'end' => '2026-09-18 00:00:00']);

        // Seul événement du jour pour Antoine : un événement annulé.
        $event = $this->createEvent(['type_event' => 'Annule']);
        $event->users()->sync([$antoine->id]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17');

        $response->assertOk();
        $response->assertDontSee('Congé · Conflit');
    }
}
