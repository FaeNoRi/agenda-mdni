<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ThemeColors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_couleurs_de_la_palette_sont_acceptees(): void
    {
        $user = User::factory()->create();

        foreach (ThemeColors::noms() as $couleur) {
            $this->actingAs($user)->postJson('/user/theme-color', ['color' => $couleur])
                ->assertOk()->assertJsonPath('couleur.nom', $couleur);

            $this->assertSame($couleur, $user->fresh()->theme);
        }
    }

    public function test_une_couleur_libre_est_acceptee(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/user/theme-color', ['color' => '#A1B2C3'])
            ->assertOk()->assertJsonPath('couleur.hex', '#a1b2c3');

        $this->assertSame('#A1B2C3', $user->fresh()->theme);
    }

    public function test_une_couleur_invalide_est_refusee(): void
    {
        $user = User::factory()->create(['theme' => 'green']);

        foreach (['rouge-vif', '#12345', '#ggg111', 'javascript:alert(1)', '"><script>', ''] as $mauvaise) {
            $this->actingAs($user)->postJson('/user/theme-color', ['color' => $mauvaise])->assertStatus(422);
        }

        $this->assertSame('green', $user->fresh()->theme);
    }

    public function test_les_classiques_d_origine_gardent_leur_rendu(): void
    {
        $this->assertSame('#0000ff', ThemeColors::resolve('blue')['hex']);
        $this->assertSame('#800080', ThemeColors::resolve('purple')['hex']);
        $this->assertSame('#008000', ThemeColors::resolve('green')['hex']);
    }

    public function test_le_texte_s_adapte_a_la_couleur(): void
    {
        $this->assertSame('#ffffff', ThemeColors::resolve('blue')['fg'], 'bleu foncé : texte blanc');
        $this->assertFalse(ThemeColors::resolve('blue')['sombre']);

        $this->assertSame(ThemeColors::TEXTE_SOMBRE, ThemeColors::resolve('beurre')['fg'], 'pastel : texte sombre');
        $this->assertTrue(ThemeColors::resolve('menthe')['sombre']);
        $this->assertTrue(ThemeColors::resolve('#ffff00')['sombre']);
        $this->assertFalse(ThemeColors::resolve('#101010')['sombre']);

        foreach (array_merge(array_keys(ThemeColors::CLASSIQUES), array_keys(ThemeColors::PASTELS)) as $nom) {
            $c = ThemeColors::resolve($nom);
            $this->assertGreaterThanOrEqual(4.5, ThemeColors::contraste($c['hex'], $c['fg']), "contraste insuffisant pour {$nom}");
        }
    }

    public function test_une_valeur_inconnue_retombe_sur_le_defaut(): void
    {
        $this->assertSame('blue', ThemeColors::resolve('nimporte')['nom']);
        $this->assertSame('blue', ThemeColors::resolve(null)['nom']);
    }

    public function test_le_layout_expose_la_couleur_et_son_texte(): void
    {
        $pastel = User::factory()->create(['theme' => 'menthe']);
        $html = $this->actingAs($pastel)->get('/dashboard')->getContent();

        $this->assertStringContainsString('--tblr-primary: #a9dfb0', $html);
        $this->assertStringContainsString('--tblr-primary-fg: #182433', $html);
        $this->assertStringContainsString('data-primary-fg="dark"', $html);

        $libre = User::factory()->create(['theme' => '#336699']);
        $html = $this->actingAs($libre)->get('/dashboard')->getContent();
        $this->assertStringContainsString('--tblr-primary: #336699', $html);
        $this->assertStringContainsString('data-primary-fg="light"', $html);
    }

    public function test_aucun_element_ne_peut_redefinir_la_couleur_du_theme(): void
    {
        // Tabler redéfinit --tblr-primary (bleu par défaut) sur tout élément portant data-bs-theme, sans toucher
        // au texte : le thème apparaissait bleu avec un texte sombre. Chaque élément reprend la valeur de son parent.
        $html = $this->actingAs(User::factory()->create(['theme' => 'mauve']))->get('/dashboard')->getContent();

        $this->assertMatchesRegularExpression('/html \*, html \*::before, html \*::after \{\s*--tblr-primary: inherit !important;/', $html);
    }

    public function test_le_selecteur_est_dans_le_profil_et_plus_dans_la_barre(): void
    {
        $user = User::factory()->create(['theme' => 'sauge']);

        $profil = $this->actingAs($user)->get('/profile')->assertOk()->getContent();
        preg_match_all('/class="pf-puce" data-couleur="([a-z-]+)"/', $profil, $m);
        $this->assertEqualsCanonicalizing(ThemeColors::noms(), $m[1]);
        $this->assertStringContainsString('class="pf-rond"', $profil);
        $this->assertMatchesRegularExpression('/data-couleur="sauge"[^>]*aria-pressed="true"/', $profil);

        $dashboard = $this->actingAs($user)->get('/dashboard')->getContent();
        $this->assertStringNotContainsString('form-colorinput', $dashboard);
        $this->assertStringNotContainsString('Couleur du site', $dashboard);
    }
}
