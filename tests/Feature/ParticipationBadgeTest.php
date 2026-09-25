<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParticipationBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->insert([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'equipe@example.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function event(array $userIds, array $overrides = []): Evenements
    {
        $event = Evenements::create(array_merge([
            'nom_event' => 'Point projet VEOLIA', 'type_event' => 'Réunion', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'Test',
            'date_heure_debut' => '2026-09-17 10:30:00', 'date_heure_fin' => '2026-09-17 11:30:00',
        ], $overrides));
        $event->users()->sync($userIds);

        return $event;
    }

    private function cards(User $viewer): string
    {
        return $this->actingAs($viewer)->get('/dashboard/cards?from=2026-09-17&to=2026-09-17')->assertOk()->getContent();
    }

    private function pillFor(string $html, string $name): bool
    {
        return (bool) preg_match('/participant-me[^>]*>\s*<svg.*?<\/svg>\s*'.preg_quote(e($name), '/').'/su', $html);
    }

    public function test_named_person_gets_badge_and_highlighted_name(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $other = User::factory()->create(['name' => 'Cadet Antoine', 'is_equipe' => 1]);
        $this->event([$me->id, $other->id]);

        $html = $this->cards($me);

        $this->assertStringContainsString('Vous participez', $html);
        $this->assertTrue($this->pillFor($html, 'Deloison Clément'));
        $this->assertFalse($this->pillFor($html, 'Cadet Antoine'));
    }

    public function test_person_not_on_event_gets_no_badge(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $other = User::factory()->create(['name' => 'Cadet Antoine', 'is_equipe' => 1]);
        $this->event([$other->id]);

        $html = $this->cards($me);

        $this->assertStringNotContainsString('Vous participez', $html);
        $this->assertStringNotContainsString('participant-me', $html);
    }

    public function test_team_placeholder_is_highlighted_for_team_members(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $this->event([0]);

        $html = $this->cards($me);

        $this->assertStringContainsString('Vous participez', $html);
        $this->assertTrue($this->pillFor($html, "Toute l'équipe"));
    }

    public function test_team_placeholder_is_not_highlighted_for_non_team_users(): void
    {
        $me = User::factory()->create(['name' => 'Externe', 'is_equipe' => 0]);
        $this->event([0]);

        $this->assertStringNotContainsString('Vous participez', $this->cards($me));
    }

    public function test_named_person_takes_priority_over_team_placeholder(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $this->event([0, $me->id]);

        $html = $this->cards($me);

        $this->assertTrue($this->pillFor($html, 'Deloison Clément'));
        $this->assertFalse($this->pillFor($html, "Toute l'équipe"));
        $this->assertSame(1, substr_count($html, 'participant-me'));
    }

    public function test_cancelled_event_never_shows_participation(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $this->event([0, $me->id], ['type_event' => 'Annule']);

        $html = $this->cards($me);

        $this->assertStringNotContainsString('Vous participez', $html);
        $this->assertStringNotContainsString('participant-me', $html);
    }

    public function test_animateurs_are_listed_even_when_event_has_no_room(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $this->event([$me->id]);

        $this->assertStringContainsString('Deloison Clément', $this->cards($me));
    }

    public function test_modal_shows_participation_banner_and_highlight(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $event = $this->event([0, $me->id]);

        $html = $this->actingAs($me)->get("/evenements/{$event->id}/details")->assertOk()->getContent();

        $this->assertStringContainsString('Vous participez à cet événement', $html);
        $this->assertTrue($this->pillFor($html, 'Deloison Clément'));
        $this->assertFalse($this->pillFor($html, "Toute l'équipe"));
    }

    public function test_modal_has_no_banner_for_cancelled_event(): void
    {
        $me = User::factory()->create(['name' => 'Deloison Clément', 'is_equipe' => 1]);
        $event = $this->event([$me->id], ['type_event' => 'Annule']);

        $this->actingAs($me)->get("/evenements/{$event->id}/details")
            ->assertOk()->assertDontSee('Vous participez à cet événement');
    }
}
