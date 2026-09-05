<?php

namespace Tests\Feature;

use App\Models\ChangementHoraire;
use App\Models\Conge;
use App\Models\Evenements;
use App\Models\Salles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvenementDisponibiliteTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(array $overrides = []): Evenements
    {
        return Evenements::create(array_merge([
            'nom_event' => 'Atelier Photo',
            'type_event' => 'Atelier',
            'type_public' => 'Grand Public',
            'desc_event' => 'Un atelier photo.',
            'commanditaire_event' => 'MDNI',
            'nbpart' => 5,
            'facture' => 'Non',
            'devis' => 'Non',
            'objet' => 'Non',
            'auteur' => 'Test',
            'date_heure_debut' => '2026-01-10 14:00:00',
            'date_heure_fin' => '2026-01-10 16:00:00',
        ], $overrides));
    }

    public function test_returns_empty_when_no_dates_given(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson('/evenements/disponibilites');

        $response->assertOk()->assertExactJson(['users' => [], 'salles' => []]);
    }

    public function test_flags_user_and_salle_busy_on_overlapping_event_with_reason(): void
    {
        $busyUser = User::factory()->create();
        $freeUser = User::factory()->create();
        $salle = Salles::create(['nom_salle' => 'Salle A', 'type_salle' => 'Atelier']);

        $event = $this->createEvent();
        $event->users()->attach($busyUser->id);
        $event->salles()->attach($salle->id);

        $response = $this->actingAs(User::factory()->create())->getJson(
            '/evenements/disponibilites?debut='.urlencode('2026-01-10T15:00').'&fin='.urlencode('2026-01-10T17:00')
        );

        $response->assertOk();
        $response->assertJsonPath(
            "users.{$busyUser->id}.0",
            fn ($reason) => str_contains($reason, 'Atelier Photo')
        );
        $response->assertJsonPath(
            "salles.{$salle->id}.0",
            fn ($reason) => str_contains($reason, 'Atelier Photo')
        );
        $this->assertArrayNotHasKey((string) $freeUser->id, $response->json('users'));
    }

    public function test_does_not_flag_non_overlapping_event(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent([
            'date_heure_debut' => '2026-01-10 08:00:00',
            'date_heure_fin' => '2026-01-10 09:00:00',
        ]);
        $event->users()->attach($user->id);

        $response = $this->actingAs(User::factory()->create())->getJson(
            '/evenements/disponibilites?debut='.urlencode('2026-01-10T14:00').'&fin='.urlencode('2026-01-10T16:00')
        );

        $response->assertOk();
        $this->assertArrayNotHasKey((string) $user->id, $response->json('users'));
    }

    public function test_excludes_cancelled_events(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(['type_event' => 'Annule']);
        $event->users()->attach($user->id);

        $response = $this->actingAs(User::factory()->create())->getJson(
            '/evenements/disponibilites?debut='.urlencode('2026-01-10T14:30').'&fin='.urlencode('2026-01-10T15:30')
        );

        $response->assertOk();
        $this->assertArrayNotHasKey((string) $user->id, $response->json('users'));
    }

    public function test_flags_user_on_conge_with_reason(): void
    {
        $user = User::factory()->create();

        Conge::create([
            'user_id' => $user->id,
            'start' => '2026-01-10 00:00:00',
            'end' => '2026-01-12 00:00:00',
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson(
            '/evenements/disponibilites?debut='.urlencode('2026-01-11T09:00').'&fin='.urlencode('2026-01-11T10:00')
        );

        $response->assertOk();
        $response->assertJsonPath(
            "users.{$user->id}.0",
            fn ($reason) => str_contains($reason, 'congé')
        );
    }

    public function test_flags_user_with_schedule_change_with_reason(): void
    {
        $user = User::factory()->create();

        ChangementHoraire::create([
            'user_id' => $user->id,
            'type_chgmt' => 'change',
            'old_start' => '2026-01-10 09:00:00',
            'old_end' => '2026-01-10 12:00:00',
            'new_start' => '2026-01-10 13:00:00',
            'new_end' => '2026-01-10 18:00:00',
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson(
            '/evenements/disponibilites?debut='.urlencode('2026-01-10T14:00').'&fin='.urlencode('2026-01-10T15:00')
        );

        $response->assertOk();
        $response->assertJsonPath(
            "users.{$user->id}.0",
            fn ($reason) => str_contains($reason, 'Horaire modifié')
        );
    }
}
