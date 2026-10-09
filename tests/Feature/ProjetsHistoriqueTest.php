<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\ProjetHistorique;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProjetsHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $membre;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Alice Admin']);
        $this->membre = User::factory()->create(['name' => 'Bob Membre']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function libelles(Projet $p): array
    {
        return $p->historiques()->get()->pluck('libelle')->all();
    }

    private function projetDonnees(array $s = []): array
    {
        return array_merge([
            'nom' => 'Fête de la science', 'couleur' => '#2fb344', 'icone' => 'flask', 'etat' => 'en_cours',
            'date_limite' => '2026-11-15', 'referents' => [$this->admin->id],
        ], $s);
    }

    public function test_la_creation_du_projet_est_journalisee(): void
    {
        $this->actingAs($this->admin)->postJson('/projets', $this->projetDonnees())->assertOk();

        $h = ProjetHistorique::firstOrFail();
        $this->assertSame('Projet créé', $h->libelle);
        $this->assertSame('projet', $h->type);
        $this->assertSame($this->admin->id, $h->user_id);
    }

    public function test_les_modifications_du_projet_sont_journalisees(): void
    {
        $p = Projet::factory()->create(['nom' => 'Ancien nom', 'date_limite' => '2026-11-15']);
        $p->membres()->attach($this->admin->id, ['role' => 'referent']);

        $this->actingAs($this->admin)->putJson("/projets/{$p->id}", $this->projetDonnees([
            'nom' => 'Nouveau nom', 'date_limite' => '2026-12-01', 'referents' => [$this->admin->id, $this->membre->id],
        ]))->assertOk();

        $l = $this->libelles($p);
        $this->assertContains('Projet renommé : « Ancien nom » → « Nouveau nom »', $l);
        $this->assertContains('Date limite du projet : 15/11/2026 → 01/12/2026', $l);
        $this->assertContains('Référents : Alice Admin, Bob Membre', $l);
    }

    public function test_une_modification_sans_changement_n_ecrit_rien(): void
    {
        $p = Projet::factory()->create(['nom' => 'Stable', 'date_limite' => '2026-11-15']);
        $p->membres()->attach($this->admin->id, ['role' => 'referent']);

        $this->actingAs($this->admin)->putJson("/projets/{$p->id}", $this->projetDonnees(['nom' => 'Stable']))->assertOk();

        $this->assertSame([], $this->libelles($p));
    }

    public function test_le_changement_d_etat_est_journalise_avec_sa_raison(): void
    {
        $p = Projet::factory()->create(['etat' => 'en_cours']);

        $this->actingAs($this->admin)->postJson("/projets/{$p->id}/etat", ['etat' => 'bloque', 'raison' => 'Attente mairie'])->assertOk();

        $this->assertSame(['État du projet : En cours → Bloqué (Attente mairie)'], $this->libelles($p));
    }

    public function test_les_changements_de_taches_apparaissent_dans_le_projet(): void
    {
        $p = Projet::factory()->create();

        $this->actingAs($this->membre)->postJson('/taches', [
            'titre' => 'Imprimer', 'projet_id' => $p->id, 'date_limite' => '2026-10-20', 'responsables' => [$this->membre->id],
        ])->assertOk();
        $t = Tache::firstOrFail();

        $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", ['statut' => 'en_cours'])->assertOk();
        $this->actingAs($this->membre)->putJson("/taches/{$t->id}", [
            'titre' => 'Imprimer les Ozobot', 'projet_id' => $p->id, 'date_limite' => '2026-10-25', 'responsables' => [$this->admin->id],
        ])->assertOk();
        $this->actingAs($this->admin)->deleteJson("/taches/{$t->id}")->assertOk();

        $l = $this->libelles($p);
        $this->assertContains('Tâche « Imprimer » créée', $l);
        $this->assertContains('« Imprimer » : À faire → En cours', $l);
        $this->assertContains('Tâche renommée : « Imprimer » → « Imprimer les Ozobot »', $l);
        $this->assertContains('« Imprimer les Ozobot » : date limite 20/10/2026 → 25/10/2026', $l);
        $this->assertContains('« Imprimer les Ozobot » : responsables Alice Admin', $l);
        $this->assertContains('Tâche « Imprimer les Ozobot » supprimée', $l);
        $this->assertSame('Tâche « Imprimer les Ozobot » supprimée', $p->historiques()->first()->libelle, 'le plus récent d\'abord');
    }

    public function test_deplacer_une_tache_ecrit_dans_les_deux_projets(): void
    {
        $a = Projet::factory()->create();
        $b = Projet::factory()->create();
        $t = Tache::factory()->create(['projet_id' => $a->id, 'titre' => 'Mobile']);
        $t->responsables()->attach($this->membre->id);

        $this->actingAs($this->membre)->putJson("/taches/{$t->id}", [
            'titre' => 'Mobile', 'projet_id' => $b->id, 'date_limite' => $t->date_limite->toDateString(), 'responsables' => [$this->membre->id],
        ])->assertOk();

        $this->assertContains('Tâche « Mobile » retirée du projet', $this->libelles($a));
        $this->assertContains('Tâche « Mobile » ajoutée au projet', $this->libelles($b));
    }

    public function test_une_tache_simple_n_ecrit_pas_de_journal(): void
    {
        Tache::factory()->create(['projet_id' => null]);

        $this->assertSame(0, ProjetHistorique::count());
    }

    public function test_l_historique_est_affiche_sur_la_fiche_et_survit_a_la_tache(): void
    {
        $p = Projet::factory()->create();
        $t = Tache::factory()->create(['projet_id' => $p->id, 'titre' => 'Éphémère', 'created_by' => $this->admin->id]);
        $t->delete();

        $this->actingAs($this->membre)->get(route('projets.show', $p))
            ->assertOk()->assertSee('Historique du projet')->assertSee('Éphémère')->assertSee('pt-histo', false);
    }

    // ---- Statut de départ d'une tâche ------------------------------------

    private function tache(array $s = []): array
    {
        return array_merge(['titre' => 'Nouvelle', 'date_limite' => '2026-10-20', 'responsables' => [$this->membre->id]], $s);
    }

    public function test_choisir_le_statut_de_depart(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->tache(['statut' => 'en_cours']))->assertOk();

        $t = Tache::firstOrFail();
        $this->assertSame('en_cours', $t->statut->value);
        $this->assertSame('en_cours', $t->historiques()->first()->statut->value);
    }

    public function test_sans_choix_la_tache_demarre_a_faire(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->tache())->assertOk();

        $this->assertSame('a_faire', Tache::firstOrFail()->statut->value);
    }

    public function test_un_statut_de_depart_bloque_exige_une_raison(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->tache(['statut' => 'bloque']))
            ->assertStatus(422)->assertJsonValidationErrors('raison');
        $this->assertSame(0, Tache::count());

        $this->actingAs($this->membre)->postJson('/taches', $this->tache(['statut' => 'en_attente', 'raison' => 'Devis']))->assertOk();
        $t = Tache::firstOrFail();
        $this->assertSame('Devis', $t->raison);
    }

    public function test_on_ne_cree_pas_une_tache_deja_terminee_ou_annulee(): void
    {
        foreach (['termine', 'annule', 'nimporte'] as $s) {
            $this->actingAs($this->membre)->postJson('/taches', $this->tache(['statut' => $s]))
                ->assertStatus(422)->assertJsonValidationErrors('statut');
        }
    }

    public function test_une_raison_inutile_est_ignoree(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->tache(['statut' => 'en_cours', 'raison' => 'bruit']))->assertOk();

        $this->assertNull(Tache::firstOrFail()->raison);
    }

    public function test_le_formulaire_propose_les_statuts_de_depart_et_la_pastille_est_renommee(): void
    {
        $html = $this->actingAs($this->membre)->get(route('taches.create'))->assertOk()->getContent();
        $this->assertStringContainsString('Statut de départ', $html);
        $this->assertStringContainsString('name="statut" value="bloque"', $html);
        $this->assertStringNotContainsString('name="statut" value="termine"', $html);

        $t = Tache::factory()->create();
        $this->actingAs($this->membre)->get(route('taches.edit', $t))->assertDontSee('Statut de départ');

        $this->actingAs($this->membre)->get('/projets')->assertSee('Tâches en cours')->assertDontSee('Tâches ouvertes');
    }
}
