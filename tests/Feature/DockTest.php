<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.projets_taches' => true]);
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_le_dock_est_present_avec_la_cloche_et_son_badge(): void
    {
        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="dock"', $html);
        $this->assertStringContainsString('id="dockCloche"', $html);
        $this->assertStringContainsString('id="dockOnglet"', $html);
    }

    public function test_le_tableau_de_bord_declare_son_plus_et_ses_filtres(): void
    {
        $html = $this->actingAs($this->admin)->get('/dashboard')->getContent();

        $this->assertMatchesRegularExpression('/id="btnAddEvent"[^>]*data-dock-plus data-dock-label="Ajouter un événement"/', $html);
        $this->assertMatchesRegularExpression('/id="btnOpenFilters"[^>]*data-dock-filtres/', $html);
    }

    public function test_chaque_page_declare_le_bon_plus(): void
    {
        $projet = Projet::factory()->create();

        $cas = [
            '/projets' => 'Ajouter un projet',
            '/taches' => 'Ajouter une tâche',
            "/projets/{$projet->id}" => 'Ajouter une tâche',
            '/users' => 'Ajouter un utilisateur',
            '/salles' => 'Ajouter une salle',
            '/objets' => 'Ajouter un objet',
            '/materiels' => 'Ajouter un matériel',
            '/conges' => 'Ajouter un congé',
            '/changements_horaires' => 'Ajouter un changement',
            '/adherents' => 'Ajouter un adhérent',
            '/evenements' => 'Ajouter un événement',
        ];

        foreach ($cas as $url => $libelle) {
            $this->actingAs($this->admin)->get($url)->assertOk()
                ->assertSee('data-dock-plus', false)
                ->assertSee('data-dock-label="'.$libelle.'"', false);
        }
    }

    public function test_pas_de_plus_ni_de_filtres_sur_le_profil_et_les_filtres_seulement_au_tableau_de_bord(): void
    {
        // (le dock lui-même cite ces noms dans son CSS : on cherche les attributs portés par un bouton de la page)
        $plus = '/<(?:button|a)\s[^>]*data-dock-label="/';
        $filtres = '/<button\s[^>]*data-dock-filtres/';

        $html = $this->actingAs($this->admin)->get('/profile')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression($plus, $html);
        $this->assertDoesNotMatchRegularExpression($filtres, $html);

        $html = $this->actingAs($this->admin)->get('/projets')->assertOk()->getContent();
        $this->assertMatchesRegularExpression($plus, $html);
        $this->assertDoesNotMatchRegularExpression($filtres, $html);
    }

    public function test_la_cloche_du_dock_n_existe_pas_tant_que_le_module_n_est_pas_ouvert(): void
    {
        config(['features.projets_taches' => false]);
        $membre = User::factory()->create();

        $html = $this->actingAs($membre)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="dockCloche"', $html);
        $this->assertMatchesRegularExpression('/id="btnAddEvent"[^>]*data-dock-plus/', $html, 'le « + » reste disponible');
        $this->assertMatchesRegularExpression('/id="btnOpenFilters"[^>]*data-dock-filtres/', $html);
    }
}
