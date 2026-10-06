<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\Materiels;
use App\Models\Objets;
use App\Models\Salles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvenementFormListsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nom_event' => 'Atelier',
            'commanditaire_event' => 'MDNI',
            'nbpart' => 8,
            'type_event' => 'Atelier',
            'type_public' => 'Autres',
            'devis' => 'Non',
            'facture' => 'Non',
            'reglement' => 'Non',
            'desc_event' => 'x',
            'date_heure_debut' => '2026-02-01T14:00',
            'date_heure_fin' => '2026-02-01T16:00',
            'objet' => 'Non',
        ], $overrides);
    }

    private function event(array $overrides = []): Evenements
    {
        return Evenements::create(array_merge([
            'nom_event' => 'Atelier', 'type_event' => 'Atelier', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 1,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'T',
            'date_heure_debut' => '2026-02-01 14:00:00', 'date_heure_fin' => '2026-02-01 16:00:00',
        ], $overrides));
    }

    public function test_store_tolerates_an_empty_object_row(): void
    {
        $user = User::factory()->create();
        $objet = Objets::create(['nom_obj' => 'Carnet']);

        $this->actingAs($user)->post('/evenements', $this->payload([
            'objet' => 'Oui',
            'objets' => ['', (string) $objet->id],
            'etat' => ['A faire', 'A faire'],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $event = Evenements::firstOrFail();
        $this->assertSame([$objet->id], $event->objets->pluck('id')->all());
    }

    public function test_store_ignores_objects_when_flag_is_non(): void
    {
        $user = User::factory()->create();
        $objet = Objets::create(['nom_obj' => 'Carnet']);

        $this->actingAs($user)->post('/evenements', $this->payload([
            'objet' => 'Non',
            'objets' => [(string) $objet->id],
            'etat' => ['A faire'],
        ]))->assertRedirect();

        $this->assertCount(0, Evenements::firstOrFail()->objets);
    }

    public function test_store_skips_blank_materials_and_defaults_quantity_to_one(): void
    {
        $user = User::factory()->create();
        $mat = Materiels::create(['nom_mat' => 'Ordinateur', 'nb_stock' => 3]);

        $this->actingAs($user)->post('/evenements', $this->payload([
            'materiels' => ['', (string) $mat->id],
            'quantites' => ['4', null],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $event = Evenements::firstOrFail();
        $this->assertCount(1, $event->materiels);
        $this->assertSame(1, (int) $event->materiels->first()->pivot->quantite);
    }

    public function test_duplicate_materials_are_merged_by_summing_quantities(): void
    {
        $user = User::factory()->create();
        $mat = Materiels::create(['nom_mat' => 'Ordinateur', 'nb_stock' => 9]);

        $this->actingAs($user)->post('/evenements', $this->payload([
            'materiels' => [(string) $mat->id, (string) $mat->id],
            'quantites' => ['2', '3'],
        ]))->assertRedirect();

        $this->assertSame(5, (int) Evenements::firstOrFail()->materiels->first()->pivot->quantite);
    }

    public function test_duplicate_objects_keep_the_pending_state(): void
    {
        $user = User::factory()->create();
        $objet = Objets::create(['nom_obj' => 'Carnet']);

        $this->actingAs($user)->post('/evenements', $this->payload([
            'objet' => 'Oui',
            'objets' => [(string) $objet->id, (string) $objet->id],
            'etat' => ['Fait', 'A faire'],
        ]))->assertRedirect();

        $this->assertSame('A faire', Evenements::firstOrFail()->objets->first()->pivot->etat);
    }

    public function test_update_behaves_like_store_for_blank_and_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $mat = Materiels::create(['nom_mat' => 'Ordinateur', 'nb_stock' => 9]);
        $event = $this->event();

        $this->actingAs($user)->put("/evenements/{$event->id}", $this->payload([
            'materiels' => ['', (string) $mat->id, (string) $mat->id],
            'quantites' => ['7', '1', '2'],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertCount(1, $event->materiels);
        $this->assertSame(3, (int) $event->materiels->first()->pivot->quantite);
    }

    public function test_empty_participant_count_and_description_do_not_break_saving(): void
    {
        $user = User::factory()->create();
        $event = $this->event();

        $payload = $this->payload(['nbpart' => '', 'desc_event' => '']);

        $this->actingAs($user)->post('/evenements', $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, Evenements::count());

        $this->actingAs($user)->put("/evenements/{$event->id}", $payload)->assertSessionHasNoErrors();
        $this->assertSame(0, (int) $event->fresh()->nbpart);
    }

    public function test_invalid_quantity_is_rejected(): void
    {
        $user = User::factory()->create();
        $mat = Materiels::create(['nom_mat' => 'Ordinateur', 'nb_stock' => 9]);

        $this->actingAs($user)->post('/evenements', $this->payload([
            'materiels' => [(string) $mat->id],
            'quantites' => ['0'],
        ]))->assertSessionHasErrors('quantites.0');

        $this->assertSame(0, Evenements::count());
    }

    public function test_disponibilites_can_exclude_the_event_being_edited(): void
    {
        $user = User::factory()->create();
        $animateur = User::factory()->create();
        $salle = Salles::create(['nom_salle' => 'Salle A', 'type_salle' => 'Atelier']);

        $event = $this->event();
        $event->users()->attach($animateur->id);
        $event->salles()->attach($salle->id);

        $query = ['debut' => '2026-02-01T14:00', 'fin' => '2026-02-01T16:00'];

        $busy = $this->actingAs($user)->getJson('/evenements/disponibilites?' . http_build_query($query))->json();
        $this->assertArrayHasKey((string) $animateur->id, $busy['users']);

        $free = $this->actingAs($user)->getJson('/evenements/disponibilites?' . http_build_query($query + ['exclude' => $event->id]))->json();
        $this->assertSame([], (array) $free['users']);
        $this->assertSame([], (array) $free['salles']);
    }

    public function test_form_adds_new_rooms_as_objects_like_the_existing_ones(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get('/evenements/create')->assertOk()->getContent();

        $this->assertStringNotContainsString("salles.push('')", $html);
        $this->assertStringContainsString("form.salles.push({ id: '' })", $html);
    }

    public function test_duplicating_an_event_resets_objects_to_pending(): void
    {
        $user = User::factory()->create();
        $objet = Objets::create(['nom_obj' => 'Carnet']);
        $event = $this->event(['objet' => 'Oui']);
        $event->objets()->attach($objet->id, ['etat' => 'Fait']);

        $html = $this->actingAs($user)->get("/evenements/{$event->id}/duplicate")->assertOk()->getContent();

        $this->assertStringContainsString('"etat":"A faire"', $html);
        $this->assertStringNotContainsString('"etat":"Fait"', $html);
    }

    public function test_modal_lists_materials_even_without_objects(): void
    {
        $user = User::factory()->create();
        $mat = Materiels::create(['nom_mat' => 'Ordinateur portable', 'nb_stock' => 9]);
        $event = $this->event();
        $event->materiels()->attach($mat->id, ['quantite' => 2]);

        $html = $this->actingAs($user)->get("/evenements/{$event->id}/details")->assertOk()->getContent();

        $this->assertStringContainsString('Ordinateur portable', $html);
    }
}
