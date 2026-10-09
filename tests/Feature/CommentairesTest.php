<?php

namespace Tests\Feature;

use App\Models\Commentaire;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentairesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $membre;
    private User $autre;
    private User $civique;
    private User $civiqueImplique;
    private Projet $projet;
    private Tache $tache;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->membre = User::factory()->create();
        $this->autre = User::factory()->create();
        $this->civique = User::factory()->create(['is_civique' => true]);
        $this->civiqueImplique = User::factory()->create(['is_civique' => true]);

        $this->projet = Projet::factory()->create();
        $this->tache = Tache::factory()->create(['projet_id' => $this->projet->id]);
        $this->tache->responsables()->attach($this->civiqueImplique->id);
    }

    private function commentaire(User $auteur): Commentaire
    {
        return $this->tache->commentaires()->create(['user_id' => $auteur->id, 'contenu' => 'Un mot']);
    }

    public function test_commenter_une_tache(): void
    {
        $this->actingAs($this->membre)
            ->postJson(route('taches.commentaires.store', $this->tache), ['contenu' => '  Bien reçu  '])
            ->assertOk()->assertJson(['ok' => true, 'id' => $this->tache->id]);

        $this->assertDatabaseHas('commentaires', [
            'commentable_type' => $this->tache->getMorphClass(), 'commentable_id' => $this->tache->id,
            'user_id' => $this->membre->id, 'contenu' => 'Bien reçu',
        ]);
    }

    public function test_commenter_un_projet(): void
    {
        $this->actingAs($this->membre)
            ->postJson(route('projets.commentaires.store', $this->projet), ['contenu' => 'À suivre'])
            ->assertOk();

        $this->assertSame(1, $this->projet->commentaires()->count());
    }

    public function test_le_commentaire_est_obligatoire_et_borne(): void
    {
        $this->actingAs($this->membre);

        $this->postJson(route('taches.commentaires.store', $this->tache), ['contenu' => '   '])
            ->assertStatus(422)->assertJsonValidationErrors('contenu');
        $this->postJson(route('taches.commentaires.store', $this->tache), ['contenu' => str_repeat('a', 2001)])
            ->assertStatus(422);
        $this->assertSame(0, Commentaire::count());
    }

    public function test_le_service_civique_ne_commente_que_ou_il_est_implique(): void
    {
        $this->actingAs($this->civique)
            ->postJson(route('taches.commentaires.store', $this->tache), ['contenu' => 'Salut'])
            ->assertForbidden();
        $this->actingAs($this->civique)
            ->postJson(route('projets.commentaires.store', $this->projet), ['contenu' => 'Salut'])
            ->assertForbidden();

        $this->actingAs($this->civiqueImplique)
            ->postJson(route('taches.commentaires.store', $this->tache), ['contenu' => 'Fait'])
            ->assertOk();
        $this->actingAs($this->civiqueImplique)
            ->postJson(route('projets.commentaires.store', $this->projet), ['contenu' => 'Fait aussi'])
            ->assertOk();
    }

    public function test_modifier_son_commentaire_seulement(): void
    {
        $c = $this->commentaire($this->membre);

        $this->actingAs($this->autre)->putJson(route('commentaires.update', $c), ['contenu' => 'Piraté'])->assertForbidden();
        $this->actingAs($this->admin)->putJson(route('commentaires.update', $c), ['contenu' => 'Piraté'])->assertForbidden();

        $this->actingAs($this->membre)->putJson(route('commentaires.update', $c), ['contenu' => 'Corrigé'])
            ->assertOk()->assertJson(['id' => $this->tache->id]);
        $this->assertSame('Corrigé', $c->fresh()->contenu);
    }

    public function test_supprimer_son_commentaire_ou_comme_admin(): void
    {
        $c = $this->commentaire($this->membre);

        $this->actingAs($this->autre)->deleteJson(route('commentaires.destroy', $c))->assertForbidden();
        $this->actingAs($this->membre)->deleteJson(route('commentaires.destroy', $c))->assertOk();
        $this->assertSame(0, Commentaire::count());

        $c2 = $this->commentaire($this->membre);
        $this->actingAs($this->admin)->deleteJson(route('commentaires.destroy', $c2))->assertOk();
        $this->assertSame(0, Commentaire::count());
    }

    public function test_le_service_civique_ne_modifie_ni_ne_supprime(): void
    {
        $c = $this->commentaire($this->civiqueImplique);

        $this->actingAs($this->civiqueImplique)->putJson(route('commentaires.update', $c), ['contenu' => 'X'])->assertForbidden();
        $this->actingAs($this->civiqueImplique)->deleteJson(route('commentaires.destroy', $c))->assertForbidden();
    }

    public function test_les_commentaires_sont_dans_l_ordre_chronologique_sur_les_fiches(): void
    {
        $this->tache->commentaires()->create(['user_id' => $this->autre->id, 'contenu' => 'Second mot']);
        $this->tache->commentaires()->create(['user_id' => $this->membre->id, 'contenu' => 'Premier mot'])
            ->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($this->membre)->get(route('taches.show', $this->tache))
            ->assertOk()
            ->assertSeeInOrder(['Premier mot', 'Second mot'])
            ->assertSee('Commenter');
    }

    public function test_le_formulaire_et_les_actions_dependent_des_droits(): void
    {
        $this->commentaire($this->membre);

        // l'auteur voit Modifier / Supprimer, un autre utilisateur non
        $this->actingAs($this->membre)->get(route('taches.show', $this->tache))
            ->assertSee('data-commentaire-modifier', false)->assertSee('data-commentaire-suppr', false);
        $this->actingAs($this->autre)->get(route('taches.show', $this->tache))
            ->assertDontSee('data-commentaire-modifier', false)->assertDontSee('data-commentaire-suppr', false);
        // l'admin peut supprimer mais pas réécrire
        $this->actingAs($this->admin)->get(route('taches.show', $this->tache))
            ->assertDontSee('data-commentaire-modifier', false)->assertSee('data-commentaire-suppr', false);

        // le Service civique non impliqué voit les commentaires mais pas le formulaire
        $this->actingAs($this->civique)->get(route('taches.show', $this->tache))
            ->assertSee('Un mot')->assertDontSee('Commenter');
        $this->actingAs($this->civiqueImplique)->get(route('taches.show', $this->tache))
            ->assertSee('Commenter');
    }

    public function test_la_fiche_projet_affiche_les_commentaires_et_le_formulaire(): void
    {
        $this->projet->commentaires()->create(['user_id' => $this->autre->id, 'contenu' => 'Note du projet']);

        $this->actingAs($this->membre)->get(route('projets.show', $this->projet))
            ->assertOk()->assertSee('Note du projet')
            ->assertSee(route('projets.commentaires.store', $this->projet), false);
    }
}
