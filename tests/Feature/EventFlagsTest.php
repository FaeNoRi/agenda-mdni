<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\Objets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFlagsTest extends TestCase
{
    use RefreshDatabase;

    private const PHOTOS = 'Des photos doivent être prises';
    private const OBJETS = 'Un ou plusieurs objets sont à remettre';

    private function event(array $overrides = [], array $objets = []): Evenements
    {
        $event = Evenements::create(array_merge([
            'nom_event' => 'Fête de la science', 'type_event' => 'Atelier', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'T',
            'date_heure_debut' => '2026-10-07 10:00:00', 'date_heure_fin' => '2026-10-07 11:00:00',
        ], $overrides));

        foreach ($objets as $nom => $etat) {
            $event->objets()->attach(Objets::create(['nom_obj' => $nom])->id, ['etat' => $etat]);
        }

        return $event;
    }

    private function cards(): string
    {
        $viewer = User::factory()->create();

        return $this->actingAs($viewer)->get('/dashboard/cards?from=2026-10-07&to=2026-10-07')->assertOk()->getContent();
    }

    private function modal(Evenements $event): string
    {
        $viewer = User::factory()->create();

        return $this->actingAs($viewer)->get("/evenements/{$event->id}/details")->assertOk()->getContent();
    }

    public function test_photos_flag_follows_the_checkbox(): void
    {
        $this->event(['prendre_photos' => true]);
        $this->assertStringContainsString('data-tip="'.self::PHOTOS.'"', $this->cards());
    }

    public function test_no_photos_flag_when_unchecked(): void
    {
        $this->event(['prendre_photos' => false]);
        $this->assertStringNotContainsString(self::PHOTOS, $this->cards());
    }

    public function test_objects_flag_requires_the_yes_flag_and_a_non_empty_list(): void
    {
        $this->event(['objet' => 'Non'], ['Carnet' => 'A faire']);
        $this->assertStringNotContainsString(self::OBJETS, $this->cards());
    }

    public function test_objects_flag_hidden_when_list_is_empty(): void
    {
        $this->event(['objet' => 'Oui']);
        $this->assertStringNotContainsString(self::OBJETS, $this->cards());
    }

    public function test_objects_flag_is_red_while_something_is_left_to_do(): void
    {
        $this->event(['objet' => 'Oui'], ['Carnet' => 'Fait', 'Impression 3D' => 'A faire']);
        $html = $this->cards();

        $this->assertMatchesRegularExpression('/event-flag bg-danger"[^>]*data-tip="'.preg_quote(self::OBJETS, '/').'/u', $html);
    }

    public function test_objects_flag_keeps_the_card_color_when_everything_is_done(): void
    {
        $this->event(['objet' => 'Oui'], ['Carnet' => 'Fait']);
        $html = $this->cards();

        $this->assertMatchesRegularExpression('/event-flag bg-success"[^>]*data-tip="'.preg_quote(self::OBJETS, '/').'"/u', $html);
        $this->assertStringNotContainsString('event-flag bg-danger', $html);
    }

    public function test_cancelled_event_shows_no_photos_or_objects_flag(): void
    {
        $this->event(['type_event' => 'Annule', 'prendre_photos' => true, 'objet' => 'Oui'], ['Carnet' => 'A faire']);
        $html = $this->cards();

        $this->assertStringNotContainsString(self::PHOTOS, $html);
        $this->assertStringNotContainsString(self::OBJETS, $html);
    }

    public function test_modal_shows_flags_in_the_banner_and_next_to_the_objects_section(): void
    {
        $event = $this->event(['prendre_photos' => true, 'objet' => 'Oui'], ['Carnet' => 'A faire']);
        $html = $this->modal($event);

        $this->assertStringContainsString('data-tip="'.self::PHOTOS.'"', $html);
        $this->assertSame(2, substr_count($html, 'data-tip="'.self::OBJETS));
        $this->assertStringContainsString('Objet à remettre', $html);
    }

    public function test_modal_still_lists_the_objects_when_cancelled(): void
    {
        $event = $this->event(['type_event' => 'Annule', 'objet' => 'Oui'], ['Carnet' => 'A faire']);
        $html = $this->modal($event);

        $this->assertStringNotContainsString(self::OBJETS, $html);
        $this->assertStringContainsString('Carnet - A faire', $html);
    }

    public function test_photos_flag_is_saved_and_loaded_by_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/evenements', [
            'nom_event' => 'Atelier', 'commanditaire_event' => 'MDNI', 'type_event' => 'Atelier', 'nbpart' => 3,
            'date_heure_debut' => '2026-10-07T10:00', 'date_heure_fin' => '2026-10-07T11:00',
            'objet' => 'Non', 'devis' => 'Non', 'facture' => 'Non', 'reglement' => 'Non', 'prendre_photos' => '1',
        ])->assertRedirect();

        $event = Evenements::firstOrFail();
        $this->assertTrue($event->prendre_photos);

        $this->actingAs($user)->get("/evenements/{$event->id}/edit")
            ->assertSee('"prendre_photos":true', false);

        $this->actingAs($user)->put("/evenements/{$event->id}", [
            'nom_event' => 'Atelier', 'commanditaire_event' => 'MDNI', 'type_event' => 'Atelier',
            'date_heure_debut' => '2026-10-07T10:00', 'date_heure_fin' => '2026-10-07T11:00',
            'objet' => 'Non', 'devis' => 'Non', 'facture' => 'Non', 'reglement' => 'Non', 'prendre_photos' => '0',
        ])->assertRedirect();

        $this->assertFalse($event->fresh()->prendre_photos);
    }

    public function test_form_labels_the_objects_question_as_a_handover(): void
    {
        $this->actingAs(User::factory()->create())->get('/evenements/create')
            ->assertSee('Objets à remettre ?')
            ->assertDontSee('Objets à produire')
            ->assertSee('Prendre des photos');
    }
}
