<?php

namespace Tests\Feature;

use App\Enums\TacheStatut;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TachesEcritureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $membre;
    private User $civique;
    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->membre = User::factory()->create();
        $this->civique = User::factory()->create(['is_civique' => true]);
        $this->projet = Projet::factory()->create(['nom' => 'Fête de la science']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'titre' => 'Imprimer les Ozobot',
            'projet_id' => $this->projet->id,
            'date_limite' => '2026-10-20',
            'details' => '20 pièces',
            'responsables' => [$this->membre->id],
            'liens' => [['libelle' => 'Fichiers STL', 'url' => 'https://exemple.test/stl']],
        ], $surcharge);
    }

    private function tache(array $attrs = []): Tache
    {
        return Tache::factory()->create(array_merge(['projet_id' => $this->projet->id, 'created_by' => $this->admin->id], $attrs));
    }

    // ---- Création --------------------------------------------------------

    public function test_creer_une_tache(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->donnees())
            ->assertOk()->assertJsonPath('ok', true);

        $t = Tache::firstOrFail();
        $this->assertSame('Imprimer les Ozobot', $t->titre);
        $this->assertSame($this->projet->id, $t->projet_id);
        $this->assertSame(TacheStatut::AFaire, $t->statut);
        $this->assertSame($this->membre->id, $t->created_by);
        $this->assertSame('2026-10-20', $t->date_limite->toDateString());
        $this->assertSame([$this->membre->id], $t->responsables->pluck('id')->all());
        $this->assertSame('Fichiers STL', $t->liens->first()->libelle);
        $this->assertCount(1, $t->historiques, "l'historique démarre à la création");
    }

    public function test_creer_une_tache_simple_sans_projet(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->donnees(['projet_id' => null, 'details' => null, 'liens' => []]))->assertOk();

        $this->assertNull(Tache::first()->projet_id);
    }

    public function test_titre_date_et_responsable_sont_obligatoires(): void
    {
        $this->actingAs($this->membre)
            ->postJson('/taches', ['titre' => '', 'date_limite' => '', 'responsables' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titre', 'date_limite', 'responsables'])
            ->assertJsonFragment(['responsables' => ['Choisissez au moins une personne responsable.']]);

        $this->assertSame(0, Tache::count());
    }

    public function test_projet_et_responsables_doivent_exister_et_l_equipe_n_est_pas_assignable(): void
    {
        User::unguarded(fn () => User::create([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'team@x.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0,
        ]));

        $this->actingAs($this->membre)->postJson('/taches', $this->donnees(['projet_id' => 999]))->assertJsonValidationErrors('projet_id');
        $this->actingAs($this->membre)->postJson('/taches', $this->donnees(['responsables' => [999]]))->assertJsonValidationErrors('responsables.0');
        $this->actingAs($this->membre)->postJson('/taches', $this->donnees(['responsables' => [0]]))->assertJsonValidationErrors('responsables.0');
        $this->assertSame(0, Tache::count());
    }

    public function test_les_liens_vides_sont_ignores_et_les_adresses_invalides_refusees(): void
    {
        $this->actingAs($this->membre)->postJson('/taches', $this->donnees(['liens' => [
            ['libelle' => 'Vide', 'url' => ''],
            ['libelle' => '', 'url' => 'https://exemple.test/a'],
        ]]))->assertOk();

        $liens = Tache::first()->liens;
        $this->assertCount(1, $liens);
        $this->assertNull($liens->first()->libelle);

        $this->actingAs($this->membre)->postJson('/taches', $this->donnees(['liens' => [['libelle' => 'x', 'url' => 'pas une adresse']]]))
            ->assertJsonValidationErrors('liens.0.url');
    }

    public function test_formulaire_de_creation_avec_projet_preselectionne(): void
    {
        $this->actingAs($this->membre)->get('/taches/create?projet='.$this->projet->id)
            ->assertOk()
            ->assertSee('Nouvelle tâche')
            ->assertSee('<option value="'.$this->projet->id.'" selected>', false);
    }

    public function test_le_formulaire_ne_propose_que_de_vraies_personnes(): void
    {
        User::factory()->create(['name' => 'Non']);
        User::unguarded(fn () => User::create([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'team@x.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0,
        ]));
        $marie = User::factory()->create(['name' => 'Marie Dupont']);

        $this->actingAs($this->admin)->get('/taches/create')
            ->assertSee('Marie Dupont')
            ->assertDontSee('Toute l\'équipe')
            ->assertDontSee('>Non<', false);

        $this->assertNotNull($marie);
    }

    // ---- Modification ----------------------------------------------------

    public function test_modifier_une_tache(): void
    {
        $t = $this->tache(['titre' => 'Ancien titre']);
        $t->responsables()->attach($this->admin->id);
        $t->liens()->create(['libelle' => 'Ancien', 'url' => 'https://exemple.test/ancien']);

        $this->actingAs($this->membre)->putJson("/taches/{$t->id}", $this->donnees(['titre' => 'Nouveau titre', 'projet_id' => null]))
            ->assertOk();

        $t->refresh();
        $this->assertSame('Nouveau titre', $t->titre);
        $this->assertNull($t->projet_id);
        $this->assertSame([$this->membre->id], $t->responsables->pluck('id')->all());
        $this->assertSame(['https://exemple.test/stl'], $t->liens->pluck('url')->all());
        $this->assertSame(TacheStatut::AFaire, $t->statut, 'le statut ne change pas par la modification');
        $this->assertCount(1, $t->historiques);
    }

    public function test_formulaire_de_modification_prerempli(): void
    {
        $t = $this->tache(['titre' => 'Titre existant', 'date_limite' => '2026-11-03']);
        $t->responsables()->attach($this->membre->id);

        $this->actingAs($this->membre)->get("/taches/{$t->id}/edit")
            ->assertOk()
            ->assertSee('Modifier la tâche')
            ->assertSee('value="Titre existant"', false)
            ->assertSee('value="2026-11-03"', false)
            ->assertSee('value="'.$this->membre->id.'" class="form-selectgroup-input" checked', false);
    }

    // ---- Service civique : aucune écriture, sauf le statut ----------------

    public function test_le_service_civique_ne_peut_ni_creer_ni_modifier_ni_supprimer(): void
    {
        $t = $this->tache();
        $t->responsables()->attach($this->civique->id);

        $this->actingAs($this->civique)->get('/taches/create')->assertForbidden();
        $this->actingAs($this->civique)->get("/taches/{$t->id}/edit")->assertForbidden();
        $this->actingAs($this->civique)->postJson('/taches', $this->donnees())->assertForbidden();
        $this->actingAs($this->civique)->putJson("/taches/{$t->id}", $this->donnees())->assertForbidden();
        $this->actingAs($this->civique)->deleteJson("/taches/{$t->id}")->assertForbidden();

        $this->assertSame(1, Tache::count());
        $this->assertSame($t->titre, $t->fresh()->titre);
    }

    // ---- Suppression -----------------------------------------------------

    public function test_supprimer_reserve_aux_admins_et_referents(): void
    {
        $referent = User::factory()->create();
        $this->projet->membres()->attach($referent->id, ['role' => 'referent']);
        $t = $this->tache();
        $t->responsables()->attach($this->membre->id);
        $t->commentaires()->create(['user_id' => $this->membre->id, 'contenu' => 'x']);

        $this->actingAs($this->membre)->deleteJson("/taches/{$t->id}")->assertForbidden();
        $this->assertSame(1, Tache::count());

        $this->actingAs($referent)->deleteJson("/taches/{$t->id}")->assertOk();
        $this->assertSame(0, Tache::count());
        $this->assertDatabaseCount('tache_user', 0);
        $this->assertDatabaseCount('tache_historiques', 0);
    }

    public function test_un_admin_supprime(): void
    {
        $t = $this->tache();

        $this->actingAs($this->admin)->deleteJson("/taches/{$t->id}")->assertOk();
        $this->assertSame(0, Tache::count());
    }

    public function test_le_createur_d_une_tache_simple_peut_la_supprimer(): void
    {
        $t = Tache::factory()->create(['created_by' => $this->membre->id]);

        $this->actingAs($this->membre)->deleteJson("/taches/{$t->id}")->assertOk();
    }

    // ---- Statut ----------------------------------------------------------

    public function test_changer_le_statut_ecrit_l_historique(): void
    {
        $t = $this->tache();

        $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", ['statut' => 'en_cours'])
            ->assertOk()->assertJsonPath('change', true);

        $this->assertSame(TacheStatut::EnCours, $t->fresh()->statut);
        $dernier = $t->historiques()->get()->last();
        $this->assertSame(TacheStatut::EnCours, $dernier->statut);
        $this->assertSame($this->membre->id, $dernier->user_id);
    }

    public function test_bloque_et_en_attente_exigent_une_raison(): void
    {
        $t = $this->tache();

        foreach (['bloque', 'en_attente'] as $statut) {
            $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", ['statut' => $statut, 'raison' => '  '])
                ->assertStatus(422)->assertJsonValidationErrors('raison');
        }
        $this->assertSame(TacheStatut::AFaire, $t->fresh()->statut);

        $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", ['statut' => 'bloque', 'raison' => 'Attente mairie'])->assertOk();
        $this->assertSame('Attente mairie', $t->fresh()->raison);
    }

    public function test_statut_inconnu_refuse(): void
    {
        $t = $this->tache();

        $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", ['statut' => 'nimporte'])->assertStatus(422);
        $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", [])->assertStatus(422);
    }

    public function test_le_service_civique_change_le_statut_seulement_s_il_est_implique(): void
    {
        $impliquee = $this->tache();
        $impliquee->responsables()->attach($this->civique->id);
        $autre = $this->tache();

        $this->actingAs($this->civique)->postJson("/taches/{$impliquee->id}/statut", ['statut' => 'termine'])->assertOk();
        $this->assertSame(TacheStatut::Termine, $impliquee->fresh()->statut);

        $this->actingAs($this->civique)->postJson("/taches/{$autre->id}/statut", ['statut' => 'termine'])->assertForbidden();
        $this->assertSame(TacheStatut::AFaire, $autre->fresh()->statut);
    }

    // ---- Boutons de la fiche ---------------------------------------------

    public function test_les_boutons_de_la_fiche_suivent_les_droits(): void
    {
        $t = $this->tache();
        $t->responsables()->attach($this->civique->id);

        // administrateur : tout
        $this->actingAs($this->admin)->get("/taches/{$t->id}")
            ->assertSee('data-modifier-tache', false)->assertSee('data-supprimer-demande', false)->assertSee('Changer le statut');

        // utilisateur normal : modifie et change le statut, ne supprime pas
        $this->actingAs($this->membre)->get("/taches/{$t->id}")
            ->assertSee('data-modifier-tache', false)->assertDontSee('data-supprimer-demande', false)->assertSee('Changer le statut');

        // Service civique impliqué : change le statut seulement
        $this->actingAs($this->civique)->get("/taches/{$t->id}")
            ->assertDontSee('data-modifier-tache', false)->assertDontSee('data-supprimer-demande', false)->assertSee('Changer le statut');

        // Service civique non impliqué : lecture seule
        $autre = User::factory()->create(['is_civique' => true]);
        $this->actingAs($autre)->get("/taches/{$t->id}")->assertOk()
            ->assertDontSee('Changer le statut')->assertDontSee('data-modifier-tache', false);
    }

    public function test_boutons_nouvelle_tache(): void
    {
        $this->actingAs($this->membre)->get('/taches')->assertSee('Nouvelle tâche');
        $this->actingAs($this->membre)->get("/projets/{$this->projet->id}")
            ->assertSee('+ Ajouter une tâche')
            ->assertSee('data-projet="'.$this->projet->id.'"', false);

        $this->actingAs($this->civique)->get('/taches')->assertDontSee('Nouvelle tâche');
        $this->actingAs($this->civique)->get("/projets/{$this->projet->id}")->assertDontSee('+ Ajouter une tâche');
    }

    public function test_module_ferme_aucune_ecriture_pour_les_non_admins(): void
    {
        config(['features.projets_taches' => false]);
        $t = $this->tache();

        $this->actingAs($this->membre)->postJson('/taches', $this->donnees())->assertNotFound();
        $this->actingAs($this->membre)->postJson("/taches/{$t->id}/statut", ['statut' => 'termine'])->assertNotFound();
        $this->actingAs($this->membre)->deleteJson("/taches/{$t->id}")->assertNotFound();
        $this->assertSame(TacheStatut::AFaire, $t->fresh()->statut);
    }
}
