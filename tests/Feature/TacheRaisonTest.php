<?php

namespace Tests\Feature;

use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TacheRaisonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->projet = Projet::factory()->create(['nom' => 'Fête de la science']);
    }

    private function tache(string $statut, ?string $raison): Tache
    {
        return Tache::factory()->create(['projet_id' => $this->projet->id, 'statut' => $statut, 'raison' => $raison]);
    }

    public function test_modifier_le_texte_d_une_tache_en_attente_sans_changer_le_statut(): void
    {
        $t = $this->tache('en_attente', 'Attente du devis');

        $this->actingAs($this->admin)->postJson("/taches/{$t->id}/raison", ['raison' => '  Devis reçu, attente de la signature  '])
            ->assertOk()->assertJson(['ok' => true, 'change' => true, 'id' => $t->id]);

        $t->refresh();
        $this->assertSame(TacheStatut::EnAttente, $t->statut);
        $this->assertSame('Devis reçu, attente de la signature', $t->raison);

        $this->assertDatabaseHas('tache_historiques', ['tache_id' => $t->id, 'raison' => 'Devis reçu, attente de la signature']);
        $this->assertDatabaseHas('projet_historiques', ['projet_id' => $this->projet->id, 'tache_id' => $t->id]);
    }

    public function test_modifier_le_texte_d_une_tache_bloquee(): void
    {
        $t = $this->tache('bloque', 'Machine en panne');

        $this->actingAs($this->admin)->postJson("/taches/{$t->id}/raison", ['raison' => 'Pièce commandée'])->assertOk();

        $this->assertSame('Pièce commandée', $t->fresh()->raison);
        $this->assertSame(TacheStatut::Bloque, $t->fresh()->statut);
    }

    public function test_un_texte_vide_est_refuse(): void
    {
        $t = $this->tache('en_attente', 'Attente du devis');

        $this->actingAs($this->admin)->postJson("/taches/{$t->id}/raison", ['raison' => '   '])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson("/taches/{$t->id}/raison", [])->assertUnprocessable();

        $this->assertSame('Attente du devis', $t->fresh()->raison);
    }

    public function test_un_texte_identique_n_ecrit_rien(): void
    {
        $t = $this->tache('en_attente', 'Attente du devis');
        $avant = $t->historiques()->count();

        $this->actingAs($this->admin)->postJson("/taches/{$t->id}/raison", ['raison' => 'Attente du devis'])
            ->assertOk()->assertJson(['change' => false]);

        $this->assertSame($avant, $t->historiques()->count());
    }

    public function test_un_statut_sans_texte_obligatoire_refuse(): void
    {
        $t = $this->tache('en_cours', null);

        $this->actingAs($this->admin)->postJson("/taches/{$t->id}/raison", ['raison' => 'Autre chose'])->assertUnprocessable();

        $this->assertNull($t->fresh()->raison);
    }

    public function test_un_civique_non_implique_ne_peut_pas_modifier_le_texte(): void
    {
        $t = $this->tache('en_attente', 'Attente du devis');
        $etranger = User::factory()->create(['is_civique' => true]);

        $this->actingAs($etranger)->postJson("/taches/{$t->id}/raison", ['raison' => 'Piraté'])->assertForbidden();

        $this->assertSame('Attente du devis', $t->fresh()->raison);
    }

    public function test_la_fiche_propose_de_modifier_le_texte(): void
    {
        $t = $this->tache('bloque', 'Machine en panne');

        $this->actingAs($this->admin)->get("/taches/{$t->id}")->assertOk()
            ->assertSee('data-raison-modifier', false)
            ->assertSee('data-raison-edition', false)
            ->assertSee(route('taches.raison', $t), false);

        // Statut sans texte obligatoire : pas de bloc.
        $autre = $this->tache('en_cours', null);
        $this->actingAs($this->admin)->get("/taches/{$autre->id}")->assertOk()->assertDontSee('data-raison-modifier', false);
    }

    public function test_le_service_civique_peut_corriger_le_texte_d_une_tache_ou_il_est_implique(): void
    {
        $civique = User::factory()->create(['is_civique' => true]);
        $t = $this->tache('en_attente', 'Attente du devis');
        $t->responsables()->attach($civique->id);
        $autre = $this->tache('en_attente', 'Autre texte');

        $this->actingAs($civique)->postJson("/taches/{$t->id}/raison", ['raison' => 'Nouveau texte'])->assertOk();
        $this->assertSame('Nouveau texte', $t->fresh()->raison);

        $this->actingAs($civique)->postJson("/taches/{$autre->id}/raison", ['raison' => 'Interdit'])->assertForbidden();
        $this->assertSame('Autre texte', $autre->fresh()->raison);
    }
}
