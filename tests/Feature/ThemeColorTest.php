<?php

namespace Tests\Feature;

use App\Http\Controllers\UserThemeController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_dix_couleurs_du_selecteur_sont_acceptees(): void
    {
        $user = User::factory()->create();

        foreach (UserThemeController::COULEURS as $couleur) {
            $this->actingAs($user)->postJson('/user/theme-color', ['color' => $couleur])
                ->assertOk()->assertJson(['status' => 'ok']);

            $this->assertSame($couleur, $user->fresh()->theme);
        }
    }

    public function test_les_pastilles_du_selecteur_correspondent_aux_couleurs_acceptees(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/dashboard')->getContent();

        preg_match_all('/name="color" type="radio" value="([a-z]+)"/', $html, $m);

        $this->assertEqualsCanonicalizing(UserThemeController::COULEURS, array_unique($m[1]));
    }

    public function test_une_couleur_inconnue_est_refusee(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/user/theme-color', ['color' => 'rouge-vif'])
            ->assertStatus(422);
    }
}
