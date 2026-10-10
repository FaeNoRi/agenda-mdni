<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationsProjetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;      // référent du projet, fait les actions
    private User $marie;      // membre
    private User $paul;       // membre
    private User $civique;
    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Alice Admin']);
        $this->marie = User::factory()->create(['name' => 'Marie Durand']);
        $this->paul = User::factory()->create(['name' => 'Paul Martin']);
        $this->civique = User::factory()->create(['is_civique' => true]);

        $this->projet = Projet::factory()->create(['nom' => 'Fête de la science', 'etat' => 'en_cours']);
        $this->projet->membres()->attach($this->admin->id, ['role' => 'referent']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function de(User $u, ?string $type = null)
    {
        $q = AppNotification::where('user_id', $u->id);

        return $type ? $q->where('type', $type) : $q;
    }

    private function tache(array $attrs = [], array $responsables = []): Tache
    {
        $t = Tache::factory()->create($attrs + ['projet_id' => $this->projet->id, 'titre' => 'Imprimer les Ozobot', 'date_limite' => '2026-10-12']);
        $t->responsables()->attach($responsables ?: [$this->marie->id]);

        return $t;
    }

    // ---- Tâches ----------------------------------------------------------

    public function test_creer_une_tache_notifie_ses_responsables(): void
    {
        $this->actingAs($this->admin)->postJson('/taches', [
            'titre' => 'Réserver la salle', 'projet_id' => $this->projet->id, 'date_limite' => '2026-10-12',
            'responsables' => [$this->marie->id, $this->admin->id],
        ])->assertOk();

        $n = $this->de($this->marie, 'tache.assignee')->firstOrFail();
        $this->assertSame('Une tâche vous a été confiée', $n->titre);
        $this->assertSame('Alice Admin vous a assigné **Réserver la salle**', $n->contexte);
        $this->assertSame('Fête de la science', $n->projet_nom);
        $this->assertSame('J-3', $n->jalon);
        $this->assertSame('2026-10-12', $n->echeance->toDateString());
        $this->assertStringContainsString('/taches?tache=', $n->url);

        $this->assertSame(0, $this->de($this->admin)->count(), "l'auteur de l'action n'est pas notifié");
    }

    public function test_modifier_les_responsables_notifie_les_ajouts_et_les_retraits(): void
    {
        $t = $this->tache([], [$this->marie->id]);

        $this->actingAs($this->admin)->putJson("/taches/{$t->id}", [
            'titre' => 'Imprimer les Ozobot', 'projet_id' => $this->projet->id, 'date_limite' => '2026-10-12',
            'responsables' => [$this->paul->id],
        ])->assertOk();

        $this->assertSame(1, $this->de($this->paul, 'tache.assignee')->count());
        $this->assertSame(1, $this->de($this->marie, 'tache.retiree')->count());
        $this->assertSame(0, $this->de($this->marie, 'tache.modifiee')->count());
    }

    public function test_decaler_la_date_notifie_les_responsables_restants(): void
    {
        $t = $this->tache([], [$this->marie->id, $this->paul->id]);

        $this->actingAs($this->admin)->putJson("/taches/{$t->id}", [
            'titre' => 'Imprimer les Ozobot', 'projet_id' => $this->projet->id, 'date_limite' => '2026-10-20',
            'responsables' => [$this->marie->id, $this->paul->id],
        ])->assertOk();

        $n = $this->de($this->marie, 'tache.modifiee')->firstOrFail();
        $this->assertStringContainsString('date limite 12/10/2026 → 20/10/2026', $n->contexte);
        $this->assertSame(1, $this->de($this->paul, 'tache.modifiee')->count());
    }

    public function test_une_modification_sans_changement_ne_notifie_personne(): void
    {
        $t = $this->tache();

        $this->actingAs($this->admin)->putJson("/taches/{$t->id}", [
            'titre' => 'Imprimer les Ozobot', 'projet_id' => $this->projet->id, 'date_limite' => '2026-10-12', 'responsables' => [$this->marie->id],
        ])->assertOk();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_le_statut_notifie_les_referents_seulement(): void
    {
        $t = $this->tache();

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/statut", ['statut' => 'bloque', 'raison' => 'Attente devis'])->assertOk();

        $n = $this->de($this->admin, 'tache.statut')->firstOrFail();
        $this->assertStringContainsString('À faire → Bloqué', $n->contexte);
        $this->assertStringContainsString('Attente devis', $n->contexte);
        $this->assertSame(0, $this->de($this->marie)->count(), 'le responsable qui agit n\'est pas notifié');
        $this->assertSame(0, $this->de($this->paul)->count());
    }

    public function test_a_valider_et_terminee_ont_leur_propre_type(): void
    {
        $t = $this->tache();

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/statut", ['statut' => 'a_valider'])->assertOk();
        $this->assertSame(1, $this->de($this->admin, 'tache.a_valider')->count());

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/statut", ['statut' => 'termine'])->assertOk();
        $this->assertSame(1, $this->de($this->admin, 'tache.terminee')->count());
        $this->assertSame(0, $this->de($this->admin, 'tache.statut')->count());
    }

    public function test_un_statut_inchange_ne_notifie_pas(): void
    {
        $t = $this->tache();

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/statut", ['statut' => 'a_faire'])->assertOk();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_une_tache_simple_previent_son_createur(): void
    {
        $t = Tache::factory()->create(['projet_id' => null, 'created_by' => $this->paul->id]);
        $t->responsables()->attach($this->marie->id);

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/statut", ['statut' => 'en_cours'])->assertOk();

        $this->assertSame(1, $this->de($this->paul, 'tache.statut')->count());
    }

    public function test_supprimer_une_tache_notifie_ses_responsables(): void
    {
        $t = $this->tache([], [$this->marie->id]);

        $this->actingAs($this->admin)->deleteJson("/taches/{$t->id}")->assertOk();

        $n = $this->de($this->marie, 'tache.supprimee')->firstOrFail();
        $this->assertSame('Alice Admin a supprimé **Imprimer les Ozobot**', $n->contexte);
    }

    public function test_la_derniere_tache_terminee_previent_les_referents(): void
    {
        $a = $this->tache(['titre' => 'A']);
        $b = $this->tache(['titre' => 'B']);

        $this->actingAs($this->marie)->postJson("/taches/{$a->id}/statut", ['statut' => 'termine'])->assertOk();
        $this->assertSame(0, $this->de($this->admin, 'projet.taches_terminees')->count(), 'il reste une tâche ouverte');

        $this->actingAs($this->marie)->postJson("/taches/{$b->id}/statut", ['statut' => 'termine'])->assertOk();
        $this->assertSame(1, $this->de($this->admin, 'projet.taches_terminees')->count());
    }

    // ---- Commentaires ----------------------------------------------------

    public function test_un_commentaire_de_tache_previent_responsables_referents_et_precedents(): void
    {
        $t = $this->tache([], [$this->marie->id]);
        $t->commentaires()->create(['user_id' => $this->paul->id, 'contenu' => 'Premier']);

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/commentaires", ['contenu' => 'Les fichiers sont prêts'])->assertOk();

        $this->assertSame(1, $this->de($this->admin, 'tache.commentaire')->count(), 'référent');
        $this->assertSame(1, $this->de($this->paul, 'tache.commentaire')->count(), 'auteur précédent');
        $this->assertSame(0, $this->de($this->marie)->count(), 'auteur');
        $this->assertStringContainsString('« Les fichiers sont prêts »', $this->de($this->admin)->first()->contexte);
    }

    public function test_un_commentaire_de_projet_previent_les_impliques(): void
    {
        $this->tache([], [$this->marie->id]);

        $this->actingAs($this->paul)->postJson("/projets/{$this->projet->id}/commentaires", ['contenu' => 'Point d\'étape'])->assertOk();

        $this->assertSame(1, $this->de($this->admin, 'projet.commentaire')->count());
        $this->assertSame(1, $this->de($this->marie, 'projet.commentaire')->count());
    }

    public function test_le_commentaire_respecte_la_preference_de_la_personne(): void
    {
        NotificationPreference::create(['user_id' => $this->admin->id, 'type' => 'tache.commentaire', 'actif' => false]);
        $t = $this->tache();

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/commentaires", ['contenu' => 'Salut'])->assertOk();

        $this->assertSame(0, $this->de($this->admin)->count());
    }

    // ---- Projets ---------------------------------------------------------

    public function test_creer_un_projet_previent_les_referents(): void
    {
        $this->actingAs($this->admin)->postJson('/projets', [
            'nom' => 'Rentrée', 'couleur' => '#2fb344', 'icone' => 'flask', 'etat' => 'en_cours',
            'referents' => [$this->marie->id, $this->admin->id],
        ])->assertOk();

        $this->assertSame(1, $this->de($this->marie, 'projet.referent')->count());
        $this->assertSame(0, $this->de($this->admin)->count());
    }

    public function test_changer_les_referents_previent_les_concernes(): void
    {
        $this->actingAs($this->admin)->putJson("/projets/{$this->projet->id}", [
            'nom' => 'Fête de la science', 'couleur' => '#2fb344', 'icone' => 'flask',
            'referents' => [$this->admin->id, $this->paul->id],
        ])->assertOk();
        $this->assertSame(1, $this->de($this->paul, 'projet.referent')->count());

        $this->actingAs($this->admin)->putJson("/projets/{$this->projet->id}", [
            'nom' => 'Fête de la science', 'couleur' => '#2fb344', 'icone' => 'flask', 'referents' => [$this->admin->id],
        ])->assertOk();
        $this->assertSame(2, $this->de($this->paul, 'projet.referent')->count());
        $this->assertStringContainsString('retiré', $this->de($this->paul)->orderByDesc('id')->first()->contexte);
    }

    public function test_changer_l_etat_du_projet(): void
    {
        $this->tache([], [$this->marie->id]);

        $this->actingAs($this->paul)->postJson("/projets/{$this->projet->id}/etat", ['etat' => 'bloque', 'raison' => 'Attente mairie'])->assertOk();

        $n = $this->de($this->admin, 'projet.etat')->firstOrFail();
        $this->assertStringContainsString('En cours → Bloqué', $n->contexte);
        $this->assertStringContainsString('Attente mairie', $n->contexte);
        $this->assertSame(1, $this->de($this->marie, 'projet.etat')->count(), 'impliqué');
    }

    public function test_projet_a_valider_previent_les_referents_et_les_autres_recoivent_le_changement_d_etat(): void
    {
        $this->tache([], [$this->marie->id]);
        $this->projet->tachesOuvertes(); // relation chargée à rafraîchir par le contrôleur

        $this->actingAs($this->paul)->postJson("/projets/{$this->projet->id}/etat", ['etat' => 'a_valider'])->assertOk();

        $this->assertSame(1, $this->de($this->admin, 'projet.a_valider')->count());
        $this->assertSame(0, $this->de($this->admin, 'projet.etat')->count());
        $this->assertSame(1, $this->de($this->marie, 'projet.etat')->count());
    }

    public function test_supprimer_un_projet_previent_les_impliques(): void
    {
        $this->tache([], [$this->marie->id]);

        $this->actingAs($this->admin)->deleteJson("/projets/{$this->projet->id}")->assertOk();

        $this->assertSame(1, $this->de($this->marie, 'projet.supprime')->count());
    }

    public function test_le_service_civique_responsable_est_notifie(): void
    {
        $this->actingAs($this->admin)->postJson('/taches', [
            'titre' => 'Accueil', 'projet_id' => $this->projet->id, 'date_limite' => '2026-10-15', 'responsables' => [$this->civique->id],
        ])->assertOk();

        $this->assertSame(1, $this->de($this->civique, 'tache.assignee')->count());
    }

    public function test_une_erreur_de_notification_ne_bloque_pas_l_action(): void
    {
        $this->app->bind(\App\Services\Notifier::class, fn () => new class extends \App\Services\Notifier {
            public function envoyer(iterable $destinataires, string $type, array $data, ?User $acteur = null): int
            {
                throw new \RuntimeException('boum');
            }
        });
        $t = $this->tache();

        $this->actingAs($this->marie)->postJson("/taches/{$t->id}/statut", ['statut' => 'en_cours'])->assertOk();

        $this->assertSame('en_cours', $t->fresh()->statut->value);
    }
}
