<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloPilotAccueilTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_bouton_flopilot_mene_directement_aux_taches(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $html = $this->actingAs($admin)->get('/taches')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('taches.index').'" class="nav-tool nav-tool-plein', $html);
        $this->assertStringNotContainsString('nav-tool-plein dropdown-toggle', $html, 'plus de menu déroulant Projets / Tâches');
    }

    public function test_bascule_mes_toutes_et_option_terminees_sur_la_page_taches(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $html = $this->actingAs($admin)->get('/taches')->assertOk()->getContent();

        foreach (['data-pf-seg="mien"', 'data-pf-seg="tous"', 'Toutes les tâches', 'data-pf-rapide="terminees" aria-pressed', 'tri: true'] as $attendu) {
            $this->assertStringContainsString($attendu, $html, $attendu);
        }
        $this->assertStringContainsString('"taches"', $html, 'mémoire locale des filtres');

        $projets = $this->actingAs($admin)->get('/projets')->assertOk()->getContent();
        $this->assertStringContainsString('Tous les projets', $projets);
        $this->assertStringNotContainsString('data-pf-rapide="terminees" aria-pressed', $projets);
    }
}
