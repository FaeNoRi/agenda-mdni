<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeSombreTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_mode_clair_est_le_defaut(): void
    {
        $user = User::factory()->create();

        $this->assertSame('clair', $user->fresh()->mode);
        $this->assertFalse($user->fresh()->modeSombre());

        $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<html[^>]*data-bs-theme="dark"/', $html);
    }

    public function test_le_mode_sombre_est_pose_sur_la_page(): void
    {
        $user = User::factory()->create(['mode' => 'sombre']);

        $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<html[^>]*data-bs-theme="dark"/', $html);
        $this->assertStringContainsString('id="variables-ui"', $html, 'les variables de couleurs sont chargées');
        $this->assertStringNotContainsString('tabler-theme.min.js', $html, 'le script Tabler ne réécrit plus le mode');
    }

    public function test_enregistrer_le_mode(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/user/mode', ['mode' => 'sombre'])
            ->assertOk()->assertJson(['mode' => 'sombre']);
        $this->assertTrue($user->fresh()->modeSombre());

        $this->actingAs($user)->postJson('/user/mode', ['mode' => 'clair'])->assertOk();
        $this->assertFalse($user->fresh()->modeSombre());
    }

    public function test_un_mode_inconnu_est_refuse(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/user/mode', ['mode' => 'neon'])->assertUnprocessable();
        $this->assertSame('clair', $user->fresh()->mode);
    }

    public function test_invite_ne_peut_pas_changer_de_mode(): void
    {
        $this->postJson('/user/mode', ['mode' => 'sombre'])->assertUnauthorized();
    }

    public function test_le_service_civique_peut_choisir_son_mode(): void
    {
        $civique = User::factory()->create(['is_civique' => true]);

        $this->actingAs($civique)->postJson('/user/mode', ['mode' => 'sombre'])->assertOk();
        $this->assertTrue($civique->fresh()->modeSombre());
    }

    public function test_le_profil_propose_les_deux_modes(): void
    {
        $user = User::factory()->create(['mode' => 'sombre']);

        $this->actingAs($user)->get('/profile')->assertOk()
            ->assertSee('data-mode="clair"', false)
            ->assertSee('data-mode="sombre"', false)
            ->assertSee("Mode d'affichage", false);
    }
}
