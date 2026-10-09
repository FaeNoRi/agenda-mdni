<?php

namespace Tests\Feature;

use App\Models\Commentaire;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProjetsTachesDroitsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $membre;      // utilisateur normal, ni référent ni responsable
    private User $referent;    // référent du projet
    private User $civique;     // Service civique, non impliqué
    private User $civiqueImplique; // Service civique responsable d'une tâche
    private Projet $projet;
    private Tache $tache;      // tâche du projet, confiée à civiqueImplique

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->membre = User::factory()->create();
        $this->referent = User::factory()->create();
        $this->civique = User::factory()->create(['is_civique' => true]);
        $this->civiqueImplique = User::factory()->create(['is_civique' => true]);

        $this->projet = Projet::factory()->create();
        $this->projet->membres()->attach($this->referent->id, ['role' => 'referent']);

        $this->tache = Tache::factory()->create(['projet_id' => $this->projet->id]);
        $this->tache->responsables()->attach($this->civiqueImplique->id);
    }

    // ---- Consultation ----------------------------------------------------

    public function test_tout_le_monde_peut_consulter(): void
    {
        foreach ([$this->admin, $this->membre, $this->referent, $this->civique, $this->civiqueImplique] as $u) {
            $this->assertTrue($u->can('viewAny', Projet::class));
            $this->assertTrue($u->can('view', $this->projet));
            $this->assertTrue($u->can('viewAny', Tache::class));
            $this->assertTrue($u->can('view', $this->tache));
        }
    }

    // ---- Création / modification -----------------------------------------

    public function test_creer_et_modifier_sauf_service_civique(): void
    {
        foreach ([$this->admin, $this->membre, $this->referent] as $u) {
            $this->assertTrue($u->can('create', Projet::class));
            $this->assertTrue($u->can('update', $this->projet));
            $this->assertTrue($u->can('create', Tache::class));
            $this->assertTrue($u->can('update', $this->tache));
        }

        foreach ([$this->civique, $this->civiqueImplique] as $u) {
            $this->assertFalse($u->can('create', Projet::class));
            $this->assertFalse($u->can('update', $this->projet));
            $this->assertFalse($u->can('create', Tache::class));
            $this->assertFalse($u->can('update', $this->tache), 'même impliqué, le Service civique ne modifie pas');
        }
    }

    // ---- Suppression -----------------------------------------------------

    public function test_supprimer_reserve_aux_admins_et_referents(): void
    {
        $this->assertTrue($this->admin->can('delete', $this->projet));
        $this->assertTrue($this->referent->can('delete', $this->projet));
        $this->assertFalse($this->membre->can('delete', $this->projet));
        $this->assertFalse($this->civique->can('delete', $this->projet));

        $this->assertTrue($this->admin->can('delete', $this->tache));
        $this->assertTrue($this->referent->can('delete', $this->tache), 'référent du projet = référent de ses tâches');
        $this->assertFalse($this->membre->can('delete', $this->tache));
        $this->assertFalse($this->civiqueImplique->can('delete', $this->tache));
    }

    public function test_tache_simple_supprimable_par_son_createur(): void
    {
        $simple = Tache::factory()->create(['created_by' => $this->membre->id]);

        $this->assertTrue($this->membre->can('delete', $simple));
        $this->assertTrue($this->admin->can('delete', $simple));
        $this->assertFalse($this->referent->can('delete', $simple));
    }

    public function test_un_service_civique_ne_supprime_jamais_meme_admin_ou_referent(): void
    {
        $this->projet->membres()->attach($this->civique->id, ['role' => 'referent']);
        $this->civique->forceFill(['is_admin' => true]);

        $this->assertFalse($this->civique->can('delete', $this->projet));
        $this->assertFalse($this->civique->can('delete', $this->tache));
    }

    // ---- Statut et commentaires ------------------------------------------

    public function test_tout_utilisateur_normal_change_le_statut_et_commente(): void
    {
        foreach ([$this->admin, $this->membre, $this->referent] as $u) {
            $this->assertTrue($u->can('changeStatus', $this->tache));
            $this->assertTrue($u->can('comment', $this->tache));
            $this->assertTrue($u->can('comment', $this->projet));
        }
    }

    public function test_le_service_civique_n_agit_que_sur_les_taches_ou_il_est_implique(): void
    {
        $this->assertTrue($this->civiqueImplique->can('changeStatus', $this->tache));
        $this->assertTrue($this->civiqueImplique->can('comment', $this->tache));

        $this->assertFalse($this->civique->can('changeStatus', $this->tache));
        $this->assertFalse($this->civique->can('comment', $this->tache));
    }

    public function test_le_service_civique_commente_un_projet_ou_il_est_implique(): void
    {
        // responsable d'une tâche du projet => impliqué dans le projet
        $this->assertTrue($this->civiqueImplique->can('comment', $this->projet));
        $this->assertFalse($this->civique->can('comment', $this->projet));

        // référent du projet
        $this->projet->membres()->attach($this->civique->id, ['role' => 'referent']);
        $this->assertTrue($this->civique->can('comment', $this->projet));
    }

    public function test_une_tache_annulee_ne_rend_pas_implique(): void
    {
        $annulee = Tache::factory()->create(['projet_id' => $this->projet->id, 'statut' => 'annule']);
        $annulee->responsables()->attach($this->civique->id);

        $this->assertFalse($this->civique->can('comment', $this->projet));
    }

    public function test_un_referent_de_projet_est_implique_dans_ses_taches(): void
    {
        $this->referent->forceFill(['is_civique' => true]);

        $this->assertTrue($this->referent->can('changeStatus', $this->tache));
    }

    // ---- Validation ------------------------------------------------------

    public function test_valider_est_reserve_aux_admins_et_referents(): void
    {
        $this->assertTrue($this->admin->can('valider', $this->tache));
        $this->assertTrue($this->referent->can('valider', $this->tache));
        $this->assertTrue($this->referent->can('valider', $this->projet));
        $this->assertFalse($this->membre->can('valider', $this->tache));
        $this->assertFalse($this->civiqueImplique->can('valider', $this->tache));
    }

    // ---- Commentaires existants ------------------------------------------

    public function test_modifier_ou_supprimer_un_commentaire(): void
    {
        $c = Commentaire::create([
            'commentable_type' => $this->tache->getMorphClass(), 'commentable_id' => $this->tache->id,
            'user_id' => $this->membre->id, 'contenu' => 'Bonjour',
        ]);

        $this->assertTrue($this->membre->can('update', $c));
        $this->assertTrue($this->membre->can('delete', $c));
        $this->assertTrue($this->admin->can('delete', $c));
        $this->assertFalse($this->admin->can('update', $c), "l'admin ne réécrit pas les mots des autres");
        $this->assertFalse($this->referent->can('delete', $c));
        $this->assertFalse($this->civiqueImplique->can('delete', $c));
    }

    public function test_un_service_civique_ne_modifie_pas_ses_propres_commentaires(): void
    {
        $c = Commentaire::create([
            'commentable_type' => $this->tache->getMorphClass(), 'commentable_id' => $this->tache->id,
            'user_id' => $this->civiqueImplique->id, 'contenu' => 'Fait',
        ]);

        $this->assertFalse($this->civiqueImplique->can('update', $c));
        $this->assertFalse($this->civiqueImplique->can('delete', $c));
    }

    // ---- Garde-fou HTTP du Service civique -------------------------------

    public function test_le_garde_fou_http_laisse_passer_statut_et_commentaires_du_service_civique(): void
    {
        foreach (['taches.statut', 'taches.commentaires.store', 'projets.commentaires.store'] as $nom) {
            Route::middleware('web')->post('/_test/'.$nom, fn () => 'ok')->name($nom);
        }
        Route::middleware('web')->post('/_test/ailleurs', fn () => 'ok')->name('test.ailleurs');

        foreach (['taches.statut', 'taches.commentaires.store', 'projets.commentaires.store'] as $nom) {
            $this->actingAs($this->civique)->post('/_test/'.$nom)->assertOk();
        }

        // la politique, elle, reste l'arbitre : elle refuse le Service civique non impliqué
        $this->assertFalse($this->civique->can('changeStatus', $this->tache));

        // toute autre écriture reste bloquée
        $this->actingAs($this->civique)->post('/_test/ailleurs')->assertForbidden();
    }
}
