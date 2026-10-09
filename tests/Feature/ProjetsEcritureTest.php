<?php

namespace Tests\Feature;

use App\Enums\ProjetEtat;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProjetsEcritureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $membre;
    private User $civique;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->membre = User::factory()->create(['name' => 'Marie Dupont']);
        $this->civique = User::factory()->create(['is_civique' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Fête de la science',
            'description' => 'Atelier Ozobot',
            'couleur' => '#2fb344',
            'icone' => 'flask',
            'date_limite' => '2026-11-15',
            'etat' => 'en_cours',
            'referents' => [$this->admin->id, $this->membre->id],
        ], $surcharge);
    }

    private function projet(array $attrs = []): Projet
    {
        return Projet::factory()->create(array_merge(['created_by' => $this->admin->id], $attrs));
    }

    private function taches(Projet $projet, array $statuts): void
    {
        foreach ($statuts as $statut) {
            Tache::factory()->create(['projet_id' => $projet->id, 'statut' => $statut, 'raison' => in_array($statut, ['bloque', 'en_attente']) ? 'x' : null]);
        }
    }

    // ---- Création --------------------------------------------------------

    public function test_creer_un_projet_avec_plusieurs_referents(): void
    {
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees())->assertOk()->assertJsonPath('ok', true);

        $p = Projet::firstOrFail();
        $this->assertSame('Fête de la science', $p->nom);
        $this->assertSame('#2fb344', $p->couleur);
        $this->assertSame('flask', $p->icone);
        $this->assertSame(ProjetEtat::EnCours, $p->etat);
        $this->assertSame('2026-11-15', $p->date_limite->toDateString());
        $this->assertSame($this->membre->id, $p->created_by);
        $this->assertEqualsCanonicalizing([$this->admin->id, $this->membre->id], $p->referents->pluck('id')->all());
        $this->assertCount(2, $p->membres, 'seuls les référents sont enregistrés sur le projet');
    }

    public function test_nom_et_au_moins_un_referent_sont_obligatoires(): void
    {
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['nom' => '', 'referents' => []]))
            ->assertStatus(422)->assertJsonValidationErrors(['nom', 'referents'])
            ->assertJsonFragment(['referents' => ['Choisissez au moins un référent.']]);

        $this->assertSame(0, Projet::count());
    }

    public function test_la_date_limite_est_facultative(): void
    {
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['date_limite' => null]))->assertOk();

        $this->assertNull(Projet::first()->date_limite);
    }

    public function test_couleur_icone_et_etat_de_depart_sont_controles(): void
    {
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['couleur' => '#123456']))->assertJsonValidationErrors('couleur');
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['icone' => 'pirate']))->assertJsonValidationErrors('icone');
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['etat' => 'termine']))->assertJsonValidationErrors('etat');
        $this->assertSame(0, Projet::count());
    }

    public function test_les_personnes_impliquees_ne_se_saisissent_pas(): void
    {
        // Même si un client envoie « impliques », la liste ne vient que des tâches.
        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['impliques' => [$this->civique->id]]))->assertOk();

        $p = Projet::first();
        $this->assertCount(2, $p->membres);
        $this->assertEqualsCanonicalizing([$this->admin->id, $this->membre->id], $p->personnesImpliquees()->pluck('id')->all());
    }

    public function test_les_impliques_se_deduisent_des_responsables_de_taches(): void
    {
        $p = $this->projet();
        $p->membres()->attach($this->admin->id, ['role' => 'referent']);
        $t = Tache::factory()->create(['projet_id' => $p->id]);
        $t->responsables()->attach([$this->membre->id, $this->admin->id]);
        $annulee = Tache::factory()->create(['projet_id' => $p->id, 'statut' => 'annule']);
        $annulee->responsables()->attach($this->civique->id);

        $p = $p->fresh();
        // référents d'abord, puis responsables ; un référent responsable n'est compté qu'une fois ; l'annulée est ignorée
        $this->assertSame([$this->admin->id, $this->membre->id], $p->personnesImpliquees()->pluck('id')->all());
        $this->assertSame([$this->membre->id], $p->impliquesHorsReferents()->pluck('id')->all());

        // retirer le responsable de la tâche le sort du projet
        $t->responsables()->detach($this->membre->id);
        $this->assertSame([$this->admin->id], $p->fresh()->personnesImpliquees()->pluck('id')->all());
    }

    public function test_le_formulaire_n_a_plus_de_champ_impliques_et_la_fiche_les_calcule(): void
    {
        $this->actingAs($this->membre)->get('/projets/create')
            ->assertDontSee('name="impliques[]"', false)
            ->assertSee('ajoutent automatiquement');

        $p = $this->projet();
        $p->membres()->attach($this->admin->id, ['role' => 'referent']);
        Tache::factory()->create(['projet_id' => $p->id])->responsables()->attach($this->membre->id);

        $this->actingAs($this->admin)->get("/projets/{$p->id}")->assertSee('Marie Dupont');
    }

    public function test_l_equipe_et_les_inconnus_ne_sont_pas_assignables(): void
    {
        User::unguarded(fn () => User::create([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'team@x.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0,
        ]));

        $this->actingAs($this->membre)->postJson('/projets', $this->donnees(['referents' => [0]]))->assertJsonValidationErrors('referents.0');
    }

    public function test_formulaires_de_creation_et_de_modification(): void
    {
        $this->actingAs($this->membre)->get('/projets/create')
            ->assertOk()->assertSee('Nouveau projet')->assertSee('name="couleur"', false)->assertSee('name="icone"', false)->assertSee('État de départ');

        $p = $this->projet(['nom' => 'Projet existant', 'couleur' => '#ae3ec9', 'icone' => 'rocket']);
        $p->membres()->attach($this->membre->id, ['role' => 'referent']);

        $this->actingAs($this->membre)->get("/projets/{$p->id}/edit")
            ->assertOk()->assertSee('Modifier le projet')->assertSee('value="Projet existant"', false)
            ->assertSee('value="#ae3ec9" class="visually-hidden" checked', false)
            ->assertSee('value="rocket" class="visually-hidden" checked', false)
            ->assertDontSee('État de départ');
    }

    // ---- Modification ----------------------------------------------------

    public function test_modifier_un_projet_ne_change_pas_l_etat(): void
    {
        $p = $this->projet(['nom' => 'Ancien', 'etat' => 'a_valider']);
        $p->membres()->attach($this->admin->id, ['role' => 'referent']);

        $this->actingAs($this->membre)->putJson("/projets/{$p->id}", $this->donnees(['nom' => 'Nouveau', 'etat' => 'en_attente']))->assertOk();

        $p->refresh();
        $this->assertSame('Nouveau', $p->nom);
        $this->assertSame(ProjetEtat::AValider, $p->etat);
        $this->assertEqualsCanonicalizing([$this->admin->id, $this->membre->id], $p->referents->pluck('id')->all());
    }

    public function test_retirer_un_referent(): void
    {
        $p = $this->projet();
        $p->membres()->attach([$this->admin->id => ['role' => 'referent'], $this->membre->id => ['role' => 'referent']]);

        $this->actingAs($this->admin)->putJson("/projets/{$p->id}", $this->donnees(['referents' => [$this->admin->id]]))->assertOk();

        $this->assertSame([$this->admin->id], $p->fresh()->membres->pluck('id')->all());
    }

    // ---- Suppression -----------------------------------------------------

    public function test_supprimer_reserve_aux_admins_et_referents_et_emporte_les_taches(): void
    {
        $p = $this->projet();
        $p->membres()->attach($this->membre->id, ['role' => 'referent']);
        $this->taches($p, ['en_cours', 'termine']);
        $autre = User::factory()->create();

        $this->actingAs($autre)->deleteJson("/projets/{$p->id}")->assertForbidden();
        $this->assertSame(1, Projet::count());

        $this->actingAs($this->membre)->deleteJson("/projets/{$p->id}")->assertOk();
        $this->assertSame(0, Projet::count());
        $this->assertSame(0, Tache::count());
    }

    public function test_un_admin_supprime(): void
    {
        $p = $this->projet();

        $this->actingAs($this->admin)->deleteJson("/projets/{$p->id}")->assertOk();
        $this->assertSame(0, Projet::count());
    }

    public function test_la_confirmation_annonce_le_nombre_de_taches(): void
    {
        $p = $this->projet();
        $this->taches($p, ['en_cours', 'termine', 'a_faire']);

        $this->actingAs($this->admin)->get("/projets/{$p->id}")->assertSee('<strong>3 tâches</strong>', false);
        $this->actingAs($this->membre)->get("/projets/{$p->id}")->assertDontSee('Oui, supprimer');
    }

    // ---- Service civique -------------------------------------------------

    public function test_le_service_civique_ne_peut_rien_ecrire_sur_les_projets(): void
    {
        $p = $this->projet();
        $p->membres()->attach($this->civique->id, ['role' => 'implique']);

        $this->actingAs($this->civique)->get('/projets/create')->assertForbidden();
        $this->actingAs($this->civique)->get("/projets/{$p->id}/edit")->assertForbidden();
        $this->actingAs($this->civique)->postJson('/projets', $this->donnees())->assertForbidden();
        $this->actingAs($this->civique)->putJson("/projets/{$p->id}", $this->donnees())->assertForbidden();
        $this->actingAs($this->civique)->deleteJson("/projets/{$p->id}")->assertForbidden();
        $this->actingAs($this->civique)->postJson("/projets/{$p->id}/etat", ['etat' => 'annule'])->assertForbidden();

        $this->assertSame(1, Projet::count());
        $this->assertSame(ProjetEtat::EnCours, $p->fresh()->etat);
    }

    public function test_boutons_selon_les_droits(): void
    {
        $p = $this->projet();

        $this->actingAs($this->membre)->get('/projets')->assertSee('Nouveau projet');
        $this->actingAs($this->civique)->get('/projets')->assertDontSee('Nouveau projet');

        $this->actingAs($this->membre)->get("/projets/{$p->id}")->assertSee('data-modifier-projet="'.$p->id.'"', false)->assertSee("Changer l'état du projet", false);
        $this->actingAs($this->civique)->get("/projets/{$p->id}")->assertOk()->assertDontSee('data-modifier-projet="'.$p->id.'"', false)->assertDontSee("Changer l'état du projet", false);
    }

    // ---- État et règle de l'option 2 -------------------------------------

    public function test_terminer_est_refuse_tant_qu_il_reste_des_taches_ouvertes(): void
    {
        $p = $this->projet(['etat' => 'en_cours']);
        $this->taches($p, ['termine', 'en_cours', 'a_faire']);

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'termine'])
            ->assertStatus(422)->assertJsonValidationErrors('etat')
            ->assertJsonFragment(['etat' => ['2 tâches ne sont pas terminées : terminez-les ou annulez-les avant de clore le projet.']]);

        $this->assertSame(ProjetEtat::EnCours, $p->fresh()->etat);
    }

    public function test_message_au_singulier(): void
    {
        $p = $this->projet();
        $this->taches($p, ['termine', 'bloque']);

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'termine'])
            ->assertJsonFragment(['etat' => ["1 tâche n'est pas terminée : terminez-les ou annulez-les avant de clore le projet."]]);
    }

    public function test_terminer_est_accepte_quand_tout_est_termine_ou_annule(): void
    {
        $p = $this->projet(['etat' => 'a_valider']);
        $this->taches($p, ['termine', 'termine', 'annule']);

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'termine'])
            ->assertOk()->assertJsonPath('change', true)->assertJsonPath('avertissements', []);

        $this->assertSame(ProjetEtat::Termine, $p->fresh()->etat);
    }

    public function test_un_projet_sans_tache_peut_etre_termine(): void
    {
        $p = $this->projet();

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'termine'])->assertOk();
    }

    public function test_bloque_et_en_attente_exigent_une_raison_puis_elle_s_efface(): void
    {
        $p = $this->projet();

        foreach (['bloque', 'en_attente'] as $etat) {
            $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => $etat, 'raison' => '  '])
                ->assertStatus(422)->assertJsonValidationErrors('raison');
        }

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'bloque', 'raison' => 'Attente des devis'])->assertOk();
        $this->assertSame('Attente des devis', $p->fresh()->raison);

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'en_cours', 'raison' => 'ignorée'])->assertOk();
        $this->assertNull($p->fresh()->raison);
    }

    public function test_etat_inconnu_refuse(): void
    {
        $p = $this->projet();

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'nimporte'])->assertStatus(422);
        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", [])->assertStatus(422);
    }

    public function test_meme_etat_ne_change_rien(): void
    {
        $p = $this->projet(['etat' => 'en_cours']);

        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'en_cours'])->assertOk()->assertJsonPath('change', false);
    }

    // ---- Avertissements --------------------------------------------------

    public function test_avertissements_d_incoherence(): void
    {
        $p = $this->projet(['etat' => 'a_valider']);
        $this->taches($p, ['termine', 'en_cours', 'a_faire']);
        $this->assertSame(['2 tâches ne sont pas terminées alors que le projet est à valider.'], $p->avertissements());

        $p2 = $this->projet(['etat' => 'en_cours']);
        $this->taches($p2, ['termine', 'annule']);
        $this->assertSame(['Toutes les tâches sont terminées : le projet peut passer à « À valider ».'], $p2->avertissements());

        $p3 = $this->projet(['etat' => 'annule']);
        $this->taches($p3, ['en_cours']);
        $this->assertSame(["1 tâche n'est pas terminée dans un projet annulé."], $p3->avertissements());

        $p4 = $this->projet(['etat' => 'termine']);
        $this->taches($p4, ['a_faire', 'bloque']);
        $this->assertSame(['2 tâches ne sont pas terminées dans un projet terminé.'], $p4->avertissements());
    }

    public function test_pas_d_avertissement_quand_tout_est_coherent(): void
    {
        $this->assertSame([], $this->projet(['etat' => 'en_cours'])->avertissements(), 'projet vide');

        $p = $this->projet(['etat' => 'en_cours']);
        $this->taches($p, ['termine', 'en_cours']);
        $this->assertSame([], $p->avertissements());

        $clos = $this->projet(['etat' => 'termine']);
        $this->taches($clos, ['termine', 'annule']);
        $this->assertSame([], $clos->avertissements());
    }

    public function test_la_fiche_affiche_les_avertissements(): void
    {
        $p = $this->projet(['etat' => 'a_valider']);
        $this->taches($p, ['en_cours']);

        $this->actingAs($this->membre)->get("/projets/{$p->id}")
            ->assertSee('1 tâche n\'est pas terminée alors que le projet est à valider.');
    }

    public function test_module_ferme_aucune_ecriture_pour_les_non_admins(): void
    {
        config(['features.projets_taches' => false]);
        $p = $this->projet();

        $this->actingAs($this->membre)->postJson('/projets', $this->donnees())->assertNotFound();
        $this->actingAs($this->membre)->deleteJson("/projets/{$p->id}")->assertNotFound();
        $this->actingAs($this->membre)->postJson("/projets/{$p->id}/etat", ['etat' => 'annule'])->assertNotFound();
        $this->assertSame(ProjetEtat::EnCours, $p->fresh()->etat);
    }
}
