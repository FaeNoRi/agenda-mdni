<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationMobileTest extends TestCase
{
    use RefreshDatabase;

    private function panneau(User $u): string
    {
        $html = $this->actingAs($u)->get('/dashboard')->assertOk()->getContent();

        $debut = strpos($html, 'nm-panneau">');
        $this->assertNotFalse($debut, 'le menu mobile est présent');

        return substr($html, $debut, strpos($html, '</nav>', $debut) - $debut);
    }

    public function test_une_seule_entree_par_destination_et_pas_d_ecran_de_saisie(): void
    {
        $menu = $this->panneau(User::factory()->create(['is_admin' => true]));

        $this->assertSame(1, substr_count($menu, route('dashboard')), 'Tableau de bord une seule fois');
        $this->assertSame(1, substr_count($menu, route('projets.index')));
        $this->assertSame(1, substr_count($menu, route('taches.index')));
        $this->assertSame(1, substr_count($menu, route('presence')));
        $this->assertSame(1, substr_count($menu, route('profile.edit')));

        // Écrans de saisie (Planning, Données, Exporter) : volontairement absents du téléphone.
        foreach (['users.index', 'salles.index', 'objets.index', 'materiels.index', 'conges.index', 'evenements.index', 'changements_horaires.index', 'adherents.index', 'export.form'] as $nom) {
            $this->assertStringNotContainsString(route($nom), $menu, "{$nom} ne doit pas être dans le menu mobile");
        }
    }

    public function test_le_menu_est_le_meme_pour_un_utilisateur_simple(): void
    {
        $menu = $this->panneau(User::factory()->create());

        $this->assertStringContainsString('Tableau de bord', $menu);
        $this->assertStringContainsString('Projets', $menu);
        $this->assertStringContainsString('Se déconnecter', $menu);
        $this->assertStringNotContainsString('Log Out', $menu);
        $this->assertStringNotContainsString('Dashboard', $menu);
    }

    public function test_les_pictogrammes_de_la_barre_suivent_la_couleur_du_texte_du_theme(): void
    {
        $html = $this->actingAs(User::factory()->create(['theme' => 'menthe']))->get('/dashboard')->getContent();

        $this->assertMatchesRegularExpression('/\.nm-bouton \{ color: var\(--tblr-primary-fg\) !important;/', $html);
        $this->assertStringNotContainsString('text-gray-400 hover:text-gray-500', $html, 'plus de gris fixe sur le hamburger');
    }
}
