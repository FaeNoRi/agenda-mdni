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

    public function test_conflict_popover_names_the_overlapping_event(): void
    {
        $admin = User::factory()->create();
        $quentin = User::factory()->create(['name' => 'Duplaquet Quentin']);

        // Congé 08:00-11:00, réunion 10:00-12:00 : une heure de chevauchement réel.
        Conge::create(['user_id' => $quentin->id, 'start' => '2026-11-10 08:00:00', 'end' => '2026-11-10 11:00:00']);

        $reunion = $this->createEvent([
            'nom_event' => 'Réunion SAS Coluche',
            'date_heure_debut' => '2026-11-10 10:00:00',
            'date_heure_fin' => '2026-11-10 12:00:00',
        ]);
        $reunion->users()->sync([$quentin->id]);

        // L'après-midi ne chevauche pas le congé : il ne doit pas être cité.
        $boost = $this->createEvent([
            'nom_event' => 'Boost ton avenir',
            'type_event' => 'RDV',
            'date_heure_debut' => '2026-11-10 14:00:00',
            'date_heure_fin' => '2026-11-10 15:00:00',
        ]);
        $boost->users()->sync([$quentin->id]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-11-10&to=2026-11-10');

        $response->assertOk();
        $response->assertSee('Congé · Conflit');
        $response->assertSee('Réunion SAS Coluche&lt;/strong&gt; (10:00–12:00)', false);
        $response->assertDontSee('Boost ton avenir&lt;/strong&gt;', false);
    }

    public function test_conflict_is_still_detected_when_the_overlapping_event_is_filtered_out(): void
    {
        $admin = User::factory()->create();
        $antoine = User::factory()->create(['name' => 'Cadet Antoine']);

        Conge::create(['user_id' => $antoine->id, 'start' => '2026-09-17 08:00:00', 'end' => '2026-09-17 12:00:00']);

        // Réunion en conflit, masquée par le filtre de type…
        $reunion = $this->createEvent();
        $reunion->users()->sync([$antoine->id]);

        // …et un atelier l'après-midi, seul affiché.
        $atelier = $this->createEvent([
            'nom_event' => 'Atelier',
            'type_event' => 'Atelier',
            'date_heure_debut' => '2026-09-17 14:00:00',
            'date_heure_fin' => '2026-09-17 16:00:00',
        ]);
        $atelier->users()->sync([$antoine->id]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17&type[]=Atelier');

        $response->assertOk();
        $response->assertSee('Congé · Conflit');
        $response->assertSee('Point projet VEOLIA');
    }

    public function test_full_day_conge_entered_as_midnight_to_midnight_same_day_triggers_conflict(): void
    {
        $admin = User::factory()->create();
        $antoine = User::factory()->create(['name' => 'Cadet Antoine']);

        // Journée entière saisie « 17/09 00:00 → 17/09 00:00 » (durée nulle sans traitement).
        Conge::create(['user_id' => $antoine->id, 'start' => '2026-09-17 00:00:00', 'end' => '2026-09-17 00:00:00']);

        $event = $this->createEvent();
        $event->users()->sync([$antoine->id]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17');

        $response->assertOk();
        $response->assertSee('Congé · Conflit');
    }

    public function test_team_event_does_not_trigger_conflict(): void
    {
        $admin = User::factory()->create();
        $antoine = User::factory()->create(['name' => 'Cadet Antoine', 'is_equipe' => true]);
        $equipe = User::factory()->create(['name' => "Toute l'équipe"]);
        $equipe->forceFill(['id' => 0])->save();

        Conge::create(['user_id' => $antoine->id, 'start' => '2026-09-17 00:00:00', 'end' => '2026-09-18 00:00:00']);

        $event = $this->createEvent();
        $event->users()->sync([0]);

        $response = $this->actingAs($admin)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17');

        $response->assertOk();
        $response->assertSee('Cadet Antoine');
        $response->assertDontSee('Congé · Conflit');
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
