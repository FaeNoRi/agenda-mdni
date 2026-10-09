<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TachesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Benjamin Leroy']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tache(array $attrs = [], ?Projet $projet = null, array $responsables = []): Tache
    {
        $t = Tache::factory()->create(array_merge(['projet_id' => $projet?->id], $attrs));
        if ($responsables) {
            $t->responsables()->attach(collect($responsables)->pluck('id')->all());
        }

        return $t;
    }

    // ---- Accès -----------------------------------------------------------

    public function test_invite_redirige(): void
    {
        $this->get('/taches')->assertRedirect('/login');
    }

    public function test_module_ferme_la_page_et_la_modale_sont_reservees_aux_admins(): void
    {
        config(['features.projets_taches' => false]);
        $tache = $this->tache();
        $normal = User::factory()->create();

        $this->actingAs($normal)->get('/taches')->assertNotFound();
        $this->actingAs($normal)->get("/taches/{$tache->id}")->assertNotFound();
        $this->actingAs($this->admin)->get('/taches')->assertOk();
        $this->actingAs($this->admin)->get("/taches/{$tache->id}")->assertOk();
    }

    public function test_module_ouvert_le_service_civique_consulte(): void
    {
        config(['features.projets_taches' => true]);
        $tache = $this->tache();
        $sc = User::factory()->create(['is_civique' => true]);

        $this->actingAs($sc)->get('/taches')->assertOk();
        $this->actingAs($sc)->get("/taches/{$tache->id}")->assertOk();
    }

    // ---- Liste groupée ---------------------------------------------------

    public function test_les_taches_sont_groupees_par_projet_puis_sans_projet(): void
    {
        $projet = Projet::factory()->create(['nom' => 'Fête de la science', 'etat' => 'en_cours']);
        $this->tache(['titre' => 'Imprimer les Ozobot'], $projet);
        $this->tache(['titre' => 'Relancer Veolia']);
        Projet::factory()->create(['nom' => 'Projet vide']);

        $this->actingAs($this->admin)->get('/taches')
            ->assertOk()
            ->assertSeeInOrder(['Fête de la science', 'Imprimer les Ozobot', 'Sans projet', 'Relancer Veolia'])
            ->assertDontSee('Projet vide');
    }

    public function test_les_projets_clos_sont_replies_et_passent_apres(): void
    {
        $clos = Projet::factory()->create(['nom' => 'Projet clos', 'etat' => 'termine', 'date_limite' => '2026-01-01']);
        $ouvert = Projet::factory()->create(['nom' => 'Projet ouvert', 'etat' => 'en_cours', 'date_limite' => '2027-01-01']);
        $this->tache([], $clos);
        $this->tache([], $ouvert);

        $html = $this->actingAs($this->admin)->get('/taches')->assertSeeInOrder(['Projet ouvert', 'Projet clos'])->getContent();

        $this->assertMatchesRegularExpression('/data-ouvert="0"[^>]*>\s*<button[^>]*aria-expanded="false"/', $html);
        $this->assertMatchesRegularExpression('/data-ouvert="1"[^>]*>\s*<button[^>]*aria-expanded="true"/', $html);
    }

    public function test_les_taches_ouvertes_passent_avant_les_terminees_puis_par_date(): void
    {
        $projet = Projet::factory()->create();
        $this->tache(['titre' => 'T-terminee', 'statut' => 'termine', 'date_limite' => '2026-09-01'], $projet);
        $this->tache(['titre' => 'T-tard', 'date_limite' => '2026-12-01'], $projet);
        $this->tache(['titre' => 'T-tot', 'date_limite' => '2026-10-15'], $projet);

        $this->actingAs($this->admin)->get('/taches')->assertSeeInOrder(['T-tot', 'T-tard', 'T-terminee']);
    }

    public function test_les_cartes_portent_les_donnees_de_filtrage(): void
    {
        $autre = User::factory()->create();
        $mienne = $this->tache(['date_limite' => '2026-10-01', 'statut' => 'en_cours'], null, [$this->admin]);
        $this->tache(['date_limite' => '2026-10-30'], null, [$autre, $this->admin]);

        $html = $this->actingAs($this->admin)->get('/taches')->getContent();

        $this->assertStringContainsString('data-tache="'.$mienne->id.'"', $html);
        $this->assertStringContainsString('data-statut="en_cours"', $html);
        $this->assertMatchesRegularExpression('/data-retard="1"\s+data-resp="'.$this->admin->id.'"\s+data-mien="1"\s+data-date="2026-10-01"/', $html);
        $ids = collect([$autre->id, $this->admin->id])->sort()->join(',');
        $this->assertStringContainsString('data-resp="'.$ids.'"', $html);
    }

    public function test_le_filtre_personne_ne_propose_que_les_responsables(): void
    {
        $resp = User::factory()->create(['name' => 'Clément Deloison']);
        User::factory()->create(['name' => 'Jamais Assigné']);
        $this->tache([], null, [$resp]);

        $this->actingAs($this->admin)->get('/taches')
            ->assertSee('data-personne="'.$resp->id.'"', false)
            ->assertDontSee('Jamais Assigné');
    }

    public function test_compteur_de_retard_et_filtre_projet(): void
    {
        $projet = Projet::factory()->create(['nom' => 'Alpha']);
        $this->tache(['date_limite' => '2026-10-01', 'statut' => 'en_cours'], $projet);
        $this->tache(['date_limite' => '2026-10-02', 'statut' => 'termine']);   // terminée : pas en retard
        $this->tache(['date_limite' => '2026-10-03', 'statut' => 'a_valider']); // à valider : en retard

        $response = $this->actingAs($this->admin)->get('/taches');

        $this->assertSame(2, $response->viewData('nbRetard'));
        $response->assertSee('<option value="'.$projet->id.'">Alpha</option>', false)
            ->assertSee('<option value="0">Sans projet</option>', false);
    }

    public function test_page_vide(): void
    {
        $this->actingAs($this->admin)->get('/taches')->assertOk()->assertSee('Aucune tâche pour le moment.');
    }

    public function test_les_onglets_et_la_modale_sont_presents_sur_les_deux_pages(): void
    {
        $projet = Projet::factory()->create();

        $this->actingAs($this->admin)->get('/taches')->assertSee('id="tacheModal"', false)->assertSee(route('projets.index'), false);
        $this->actingAs($this->admin)->get('/projets')->assertSee(route('taches.index'), false);
        $this->actingAs($this->admin)->get("/projets/{$projet->id}")->assertSee('id="tacheModal"', false);
    }

    // ---- Fiche (modale) ---------------------------------------------------

    public function test_la_modale_montre_tout_le_detail(): void
    {
        $referent = User::factory()->create(['name' => 'Alex Blanc']);
        $resp = User::factory()->create(['name' => 'Clément Deloison']);
        $projet = Projet::factory()->create(['nom' => 'Fête de la science', 'icone' => 'flask', 'couleur' => '#2fb344']);
        $projet->membres()->attach($referent->id, ['role' => 'referent']);
        $tache = $this->tache([
            'titre' => 'Obtenir l\'autorisation', 'details' => 'Relancer la mairie.', 'statut' => 'en_cours',
            'date_limite' => '2026-10-03', 'created_by' => $this->admin->id,
        ], $projet, [$resp]);
        $tache->changerStatut(\App\Enums\TacheStatut::Bloque, $resp, 'En attente du retour de la mairie');
        $tache->liens()->create(['libelle' => 'Courrier type', 'url' => 'https://exemple.test/courrier']);
        $tache->commentaires()->create(['user_id' => $resp->id, 'contenu' => 'Relancée le 02/10']);

        $this->actingAs($this->admin)->get("/taches/{$tache->id}")
            ->assertOk()
            ->assertSee('Détail tâche')
            ->assertSee('ID:'.$tache->id)
            ->assertSee('Bloqué')
            ->assertSee('Obtenir l\'autorisation')
            ->assertSee('Fête de la science')
            ->assertSee('Relancer la mairie.')
            ->assertSee('En attente du retour de la mairie')
            ->assertSee('6 j de retard')
            ->assertSee('Courrier type')
            ->assertSee('Relancée le 02/10')
            ->assertSee('Benjamin Leroy')       // créateur
            // toutes les personnes impliquées sont visibles, avec leur rôle
            ->assertSee('Clément Deloison')
            ->assertSee('Alex Blanc')
            ->assertSee('Responsable')
            ->assertSee('Référent');
    }

    public function test_la_modale_affiche_l_historique(): void
    {
        $tache = $this->tache(['created_by' => $this->admin->id]);
        $tache->changerStatut(\App\Enums\TacheStatut::EnCours, $this->admin);
        $tache->changerStatut(\App\Enums\TacheStatut::Termine, $this->admin);

        $this->actingAs($this->admin)->get("/taches/{$tache->id}")
            ->assertSeeInOrder(['Historique', 'À faire', 'En cours', 'Terminé']);
    }

    public function test_une_tache_simple_a_son_createur_comme_referent(): void
    {
        $createur = User::factory()->create(['name' => 'Marie Dupont']);
        $tache = $this->tache(['created_by' => $createur->id]);

        $this->actingAs($this->admin)->get("/taches/{$tache->id}")
            ->assertSee('Sans projet')
            ->assertSee('Marie Dupont')
            ->assertSee('Référent');
    }

    public function test_la_modale_signale_quand_on_est_responsable(): void
    {
        $tache = $this->tache([], null, [$this->admin]);
        $autre = $this->tache();

        $this->actingAs($this->admin)->get("/taches/{$tache->id}")->assertSee('Vous êtes responsable');
        $this->actingAs($this->admin)->get("/taches/{$autre->id}")->assertDontSee('Vous êtes responsable');
    }

    public function test_tache_inconnue(): void
    {
        $this->actingAs($this->admin)->get('/taches/999')->assertNotFound();
    }

    public function test_la_legende_a_disparu_de_la_page_projets(): void
    {
        Projet::factory()->create();

        $this->actingAs($this->admin)->get('/projets')->assertDontSee('Légende')->assertDontSee('dans l\'anneau');
    }
}
