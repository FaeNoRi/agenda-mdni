<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\Materiels;
use App\Models\Objets;
use App\Models\Salles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvenementCrudTest extends TestCase
{
    use RefreshDatabase;

    /** Données envoyées au contrôleur (format datetime-local du formulaire). */
    private function requestPayload(array $overrides = []): array
    {
        return array_merge([
            'nom_event' => 'Atelier Photo',
            'commanditaire_event' => 'MDNI',
            'nbpart' => 8,
            'type_event' => 'Atelier',
            'type_public' => 'Grand Public',
            'devis' => 'Non',
            'facture' => 'Non',
            'reglement' => 'Non',
            'desc_event' => 'Un atelier photo.',
            'date_heure_debut' => '2026-02-01T14:00',
            'date_heure_fin' => '2026-02-01T16:00',
            'objet' => 'Non',
        ], $overrides);
    }

    /** Création directe en base (format datetime standard), pour préparer un état initial. */
    private function createEvent(string $auteur, array $overrides = []): Evenements
    {
        return Evenements::create(array_merge([
            'nom_event' => 'Atelier Photo',
            'type_event' => 'Atelier',
            'type_public' => 'Grand Public',
            'desc_event' => 'Un atelier photo.',
            'commanditaire_event' => 'MDNI',
            'nbpart' => 8,
            'facture' => 'Non',
            'devis' => 'Non',
            'objet' => 'Non',
            'auteur' => $auteur,
            'date_heure_debut' => '2026-02-01 14:00:00',
            'date_heure_fin' => '2026-02-01 16:00:00',
        ], $overrides));
    }

    public function test_store_creates_event_and_syncs_relations(): void
    {
        $user = User::factory()->create();
        $animateur = User::factory()->create();
        $salle = Salles::create(['nom_salle' => 'Salle A', 'type_salle' => 'Atelier']);
        $materiel = Materiels::create(['nom_mat' => 'Vidéoprojecteur', 'nb_stock' => 3]);
        $objet = Objets::create(['nom_obj' => 'Carnet']);

        $response = $this->actingAs($user)->post('/evenements', $this->requestPayload([
            'users' => [$animateur->id],
            'salles' => [$salle->id],
            'materiels' => [$materiel->id],
            'quantites' => [2],
            'objet' => 'Oui',
            'objets' => [$objet->id],
            'etat' => ['A faire'],
        ]));

        $response->assertRedirect();

        $event = Evenements::firstOrFail();
        $this->assertSame('Atelier Photo', $event->nom_event);
        $this->assertSame($user->name, $event->auteur);
        $this->assertTrue($event->users->contains($animateur->id));
        $this->assertTrue($event->salles->contains($salle->id));
        $this->assertSame(2, (int) $event->materiels->firstWhere('id', $materiel->id)->pivot->quantite);
        $this->assertSame('A faire', $event->objets->firstWhere('id', $objet->id)->pivot->etat);
    }

    public function test_update_syncs_relations(): void
    {
        $user = User::factory()->create();
        $animateurInitial = User::factory()->create();
        $animateurNouveau = User::factory()->create();

        $event = $this->createEvent($user->name);
        $event->users()->attach($animateurInitial->id);

        $response = $this->actingAs($user)->put("/evenements/{$event->id}", $this->requestPayload([
            'nom_event' => 'Atelier Photo (modifié)',
            'users' => [$animateurNouveau->id],
        ]));

        $response->assertRedirect();

        $event->refresh();
        $this->assertSame('Atelier Photo (modifié)', $event->nom_event);
        $this->assertFalse($event->users->contains($animateurInitial->id));
        $this->assertTrue($event->users->contains($animateurNouveau->id));
    }

    public function test_destroy_detaches_relations_and_deletes(): void
    {
        $user = User::factory()->create();
        $animateur = User::factory()->create();

        $event = $this->createEvent($user->name);
        $event->users()->attach($animateur->id);

        $response = $this->actingAs($user)->delete("/evenements/{$event->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('evenements', ['id' => $event->id]);
        $this->assertDatabaseMissing('evenement_users', ['evenement_id' => $event->id]);
    }
}
