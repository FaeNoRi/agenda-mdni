<?php

namespace Tests\Feature;

use App\Models\Adherents;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdherent(array $overrides = []): Adherents
    {
        return Adherents::create(array_merge([
            'nom_adh' => 'DUPONT Jean',
            'situation_adh' => 'Étudiant',
            'dom_adh' => 'Calais',
            'type_adh' => '',
            'date_adh' => now(),
            'isCGU' => true,
            'isPresent' => false,
        ], $overrides));
    }

    public function test_marking_present_logs_a_presence_day_once_per_day(): void
    {
        $user = User::factory()->create();
        $adherent = $this->createAdherent();

        $response = $this->actingAs($user)->postJson("/adherents/{$adherent->id}/presence", [
            'isPresent' => true,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($adherent->fresh()->isPresent);
        $this->assertDatabaseCount('presence_days', 1);
        $this->assertDatabaseHas('presence_days', ['adherent_id' => $adherent->id]);

        // Un second passage à "présent" le même jour ne doit pas dupliquer le log.
        $this->actingAs($user)->postJson("/adherents/{$adherent->id}/presence", [
            'isPresent' => true,
        ]);

        $this->assertDatabaseCount('presence_days', 1);
    }

    public function test_marking_absent_does_not_log_a_presence_day(): void
    {
        $user = User::factory()->create();
        $adherent = $this->createAdherent(['isPresent' => true]);

        $response = $this->actingAs($user)->postJson("/adherents/{$adherent->id}/presence", [
            'isPresent' => false,
        ]);

        $response->assertOk();
        $this->assertFalse($adherent->fresh()->isPresent);
        $this->assertDatabaseCount('presence_days', 0);
    }

    public function test_reset_all_requires_correct_admin_password(): void
    {
        $user = User::factory()->create();
        $this->createAdherent(['isPresent' => true]);

        $response = $this->actingAs($user)->postJson('/presence/reset', [
            'admin_password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseHas('adherents', ['isPresent' => true]);
    }
}
