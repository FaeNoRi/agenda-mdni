<?php

namespace Tests\Feature;

use App\Models\Commentaire;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProjetsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function projet(array $attrs = [], array $statuts = []): Projet
    {
        $projet = Projet::factory()->create($attrs);

        foreach ($statuts as $statut) {
            Tache::factory()->create([
                'projet_id' => $projet->id, 'statut' => $statut, 'raison' => in_array($statut, ['bloque', 'en_attente']) ? 'Attente mairie' : null,
            ]);
        }

        return $projet;
    }

    // ---- Accès -----------------------------------------------------------

    public function test_invite_redirige_vers_la_connexion(): void
    {
        $this->get('/projets')->assertRedirect('/login');
    }

    public function test_module_ferme_seuls_les_admins_y_accedent(): void
    {
        config(['features.projets_taches' => false]);
        $projet = $this->projet();

        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/projets')->assertOk();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get("/projets/{$projet->id}")->assertOk();

        $normal = User::factory()->create();
        $this->actingAs($normal)->get('/projets')->assertNotFound();
        $this->actingAs($normal)->get("/projets/{$projet->id}")->assertNotFound();
    }

    public function test_module_ouvert_tout_le_monde_peut_consulter_y_compris_le_service_civique(): void
    {
        config(['features.projets_taches' => true]);
        $projet = $this->projet();

        foreach ([User::factory()->create(), User::factory()->create(['is_civique' => true])] as $u) {
            $this->actingAs($u)->get('/projets')->assertOk();
            $this->actingAs($u)->get("/projets/{$projet->id}")->assertOk();
        }
    }

    public function test_aucune_entree_de_menu(): void
    {
        config(['features.projets_taches' => true]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('projets.index'), false);
    }

    // ---- Liste -----------------------------------------------------------

    public function test_la_liste_affiche_les_projets_avec_anneau_et_resume(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->projet(['nom' => 'Fête de la science'], ['termine', 'termine', 'en_cours', 'bloque']);

        $html = $this->actingAs($admin)->get('/projets')->assertOk()->getContent();

        $this->assertStringContainsString('Fête de la science', $html);
        $this->assertStringContainsString('2<span class="pt-anneau__sur">/4</span>', $html);
        $this->assertStringContainsString('1 bloquée', $html);
        $this->assertStringContainsString('1 en cours', $html);
    }

    public function test_les_cartes_portent_les_donnees_de_filtrage(): void
    {
        $moi = User::factory()->create(['is_admin' => true]);
        $mien = $this->projet(['nom' => 'Le mien', 'date_limite' => '2026-10-01', 'etat' => 'en_cours']);
        $mien->membres()->attach($moi->id, ['role' => 'referent']);
        $this->projet(['nom' => "Celui d'un autre", 'date_limite' => '2026-12-01', 'etat' => 'termine']);

        $html = $this->actingAs($moi)->get('/projets')->getContent();

        $this->assertMatchesRegularExpression('/data-etat="en_cours" data-mien="1" data-retard="1"/', $html);
        $this->assertMatchesRegularExpression('/data-etat="termine" data-mien="0" data-retard="0"/', $html);
    }

    public function test_une_tache_en_retard_met_le_projet_en_retard_et_affiche_un_badge(): void
    {
        $moi = User::factory()->create(['is_admin' => true]);
        $p = $this->projet(['nom' => 'Date lointaine', 'date_limite' => '2026-12-01', 'etat' => 'en_cours']);
        Tache::factory()->create(['projet_id' => $p->id, 'statut' => 'en_cours', 'date_limite' => '2026-10-01']);
        Tache::factory()->create(['projet_id' => $p->id, 'statut' => 'a_faire', 'date_limite' => '2026-10-02']);
        Tache::factory()->create(['projet_id' => $p->id, 'statut' => 'termine', 'date_limite' => '2026-09-01']);
        $this->projet(['nom' => 'Sans retard', 'date_limite' => '2026-12-01', 'etat' => 'en_cours'], ['en_cours']);

        $response = $this->actingAs($moi)->get('/projets');
        $html = $response->getContent();

        $this->assertTrue($p->fresh()->aDuRetard());
        $this->assertMatchesRegularExpression('/data-etat="en_cours" data-mien="0" data-retard="1"/', $html);
        $this->assertSame(1, $response->viewData('retardProjets'));
        $this->assertStringContainsString('2 tâches en retard', $html);
        $this->assertSame(1, substr_count($html, 'pt-badge pt-badge--retard'));
    }

    public function test_un_projet_est_a_moi_si_je_suis_responsable_d_une_de_ses_taches(): void
    {
        $moi = User::factory()->create(['is_admin' => true]);
        $projet = $this->projet([], ['en_cours']);
        $projet->taches->first()->responsables()->attach($moi->id);

        $this->actingAs($moi)->get('/projets')->assertSee('data-mien="1"', false);
    }

    public function test_les_projets_ouverts_passent_avant_les_clos(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->projet(['nom' => 'AAA clos', 'etat' => 'termine', 'date_limite' => '2026-01-01']);
        $this->projet(['nom' => 'ZZZ ouvert', 'etat' => 'en_cours', 'date_limite' => '2027-01-01']);

        $this->actingAs($admin)->get('/projets')->assertSeeInOrder(['ZZZ ouvert', 'AAA clos']);
    }

    public function test_chiffres_cles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->projet(['etat' => 'en_cours']);
        $this->projet(['etat' => 'termine']);
        Tache::factory()->create(['statut' => 'en_cours', 'date_limite' => '2026-10-01']);   // ouverte + en retard
        Tache::factory()->create(['statut' => 'a_valider', 'date_limite' => '2026-10-20']);  // ouverte + à valider
        Tache::factory()->create(['statut' => 'termine', 'date_limite' => '2026-09-01']);    // ni ouverte ni en retard

        $kpis = $this->actingAs($admin)->get('/projets')->viewData('kpis');

        $this->assertSame(['projets' => 1, 'ouvertes' => 2, 'retard' => 1, 'a_valider' => 1], $kpis);
    }

    public function test_page_vide(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/projets')
            ->assertOk()->assertSee('Aucun projet pour le moment.');
    }

    // ---- Avatars : seul le mien est en couleur -----------------------------

    public function test_seul_mon_avatar_est_en_couleur(): void
    {
        $moi = User::factory()->create(['is_admin' => true, 'name' => 'Benjamin Leroy']);
        $autre = User::factory()->create(['name' => 'Clément Deloison']);
        $projet = $this->projet();
        $projet->membres()->attach($moi->id, ['role' => 'referent']);
        Tache::factory()->create(['projet_id' => $projet->id])->responsables()->attach($autre->id);

        $html = $this->actingAs($moi)->get('/projets')->getContent();

        $this->assertSame(1, preg_match_all('/pt-avatar pt-avatar--moi" title="Benjamin Leroy"[^>]*>BL</', $html));
        $this->assertMatchesRegularExpression('/pt-avatar " title="Clément Deloison"[^>]*>CD</', $html);
    }

    // ---- Fiche projet ----------------------------------------------------

    public function test_la_fiche_affiche_le_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Marie Dupont']);
        $projet = $this->projet(['nom' => 'Refonte du site', 'description' => 'Choix du prestataire', 'etat' => 'bloque', 'raison' => 'Attente des devis'], ['termine', 'en_cours']);
        $projet->membres()->attach($admin->id, ['role' => 'referent']);
        $projet->taches->each->update(['titre' => 'Tâche test']);
        $projet->commentaires()->create(['user_id' => $admin->id, 'contenu' => 'Bien avancé']);
        $projet->liens()->create(['libelle' => 'Cahier des charges', 'url' => 'https://exemple.test/cdc']);

        $this->actingAs($admin)->get("/projets/{$projet->id}")
            ->assertOk()
            ->assertSee('Refonte du site')
            ->assertSee('Choix du prestataire')
            ->assertSee('Attente des devis')
            ->assertSee('Marie Dupont')
            ->assertSee('2 tâches — 1 terminée — 1 en cours')
            ->assertSee('Tâche test')
            ->assertSee('Bien avancé')
            ->assertSee('Cahier des charges')
            ->assertSee('https://exemple.test/cdc');
    }

    public function test_la_fiche_d_un_projet_sans_tache(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $projet = $this->projet();

        $this->actingAs($admin)->get("/projets/{$projet->id}")
            ->assertOk()->assertSee('Aucune tâche dans ce projet.')->assertSee('Aucun commentaire.')->assertSee('Aucun lien.');
    }

    public function test_fiche_inconnue(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/projets/999')->assertNotFound();
    }

    // ---- Pastilles d'échéance --------------------------------------------

    public function test_pastilles_d_echeance(): void
    {
        $retard = Tache::factory()->create(['date_limite' => '2026-10-06', 'statut' => 'en_cours']);
        $this->assertSame(['ton' => 'retard', 'texte' => '3 j de retard', 'icone' => 'alert-triangle'], $retard->echeance());

        $this->assertSame('Aujourd\'hui', Tache::factory()->create(['date_limite' => '2026-10-09'])->echeance()['texte']);
        $this->assertSame('Demain', Tache::factory()->create(['date_limite' => '2026-10-10'])->echeance()['texte']);

        $futur = Tache::factory()->create(['date_limite' => '2026-10-14'])->echeance();
        $this->assertSame(['ton' => 'normal', 'texte' => '14 oct.', 'icone' => 'calendar'], $futur);

        $this->assertSame('calme', Tache::factory()->create(['date_limite' => '2026-11-01', 'statut' => 'a_valider'])->echeance()['ton']);
        $this->assertSame('calme', Tache::factory()->create(['date_limite' => '2026-10-01', 'statut' => 'termine'])->echeance()['ton']);

        $projet = Projet::factory()->create(['date_limite' => '2026-10-14', 'etat' => 'en_cours']);
        $this->assertSame('14 oct. · J-5', $projet->echeance(true)['texte']);
        $this->assertSame('flag', $projet->echeance()['icone']);
        $this->assertSame('Sans date', Projet::factory()->create(['date_limite' => null])->echeance()['texte']);
    }

    public function test_les_icones_connues_et_inconnues_s_affichent(): void
    {
        $this->assertTrue(\App\Support\Icones::existe('flask'));
        $this->assertStringContainsString('<svg', \App\Support\Icones::svg('flask', 20));
        $this->assertStringContainsString('<svg', \App\Support\Icones::svg('nexiste-pas'));

        foreach (\App\Support\Icones::PROJET as $nom) {
            $this->assertTrue(\App\Support\Icones::existe($nom), $nom);
        }
        foreach (array_merge(\App\Enums\TacheStatut::cases(), \App\Enums\ProjetEtat::cases()) as $statut) {
            $this->assertTrue(\App\Support\Icones::existe($statut->icone()), $statut->name);
        }
    }

    public function test_les_formulaires_en_modale_restent_defilables(): void
    {
        // Le <form> s'intercale entre .modal-content et .modal-body : sans cette règle, le bas de la
        // fenêtre (bouton d'enregistrement) devenait inatteignable sur un écran peu haut.
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['/projets', '/taches'] as $page) {
            $this->actingAs($admin)->get($page)
                ->assertSee('.modal-dialog-scrollable .modal-content > form', false)
                ->assertSee('overflow-y: auto', false);
        }
    }

    public function test_le_pied_de_carte_a_deux_lignes_fixes(): void
    {
        // Ligne 1 : les personnes seules ; ligne 2 : échéance à gauche, état à droite.
        $admin = User::factory()->create(['is_admin' => true]);
        $this->projet(['nom' => 'Pied de carte', 'etat' => 'en_cours', 'date_limite' => '2026-10-23']);

        $this->actingAs($admin)->get('/projets')
            ->assertSeeInOrder(['pt-carte__pied', 'pt-avatars', 'pt-carte__ligne--ecarte', 'pt-echeance', 'pt-badge'], false);
    }
}
