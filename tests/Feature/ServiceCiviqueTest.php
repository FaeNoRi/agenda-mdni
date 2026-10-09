<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCiviqueTest extends TestCase
{
    use RefreshDatabase;

    private function civique(): User
    {
        return User::factory()->create(['is_civique' => true]);
    }

    private function event(): Evenements
    {
        return Evenements::create([
            'nom_event' => 'Atelier', 'type_event' => 'Atelier', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'T',
            'date_heure_debut' => '2026-10-07 10:00:00', 'date_heure_fin' => '2026-10-07 11:00:00',
        ]);
    }

    public function test_civique_peut_consulter_le_tableau_de_bord_et_les_details(): void
    {
        $event = $this->event();
        $user = $this->civique();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('id="btnAddEvent"', false);
        $this->actingAs($user)->get("/evenements/{$event->id}/details")->assertOk();
    }

    public function test_civique_ne_voit_pas_les_actions_de_modification(): void
    {
        $this->event();

        $this->actingAs($this->civique())->get('/dashboard/cards?from=2026-10-07&to=2026-10-07')
            ->assertOk()
            ->assertDontSee('openEvenementForm');

        $this->actingAs(User::factory()->create())->get('/dashboard/cards?from=2026-10-07&to=2026-10-07')
            ->assertSee('openEvenementForm');
    }

    public function test_civique_ne_peut_ni_creer_ni_modifier_ni_supprimer(): void
    {
        $event = $this->event();
        $user = $this->civique();

        $this->actingAs($user)->get('/evenements/create')->assertForbidden();
        $this->actingAs($user)->get("/evenements/{$event->id}/edit")->assertForbidden();
        $this->actingAs($user)->get("/evenements/{$event->id}/duplicate")->assertForbidden();
        $this->actingAs($user)->post('/evenements', ['nom_event' => 'X'])->assertForbidden();
        $this->actingAs($user)->put("/evenements/{$event->id}", ['nom_event' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete("/evenements/{$event->id}")->assertForbidden();
        $this->actingAs($user)->deleteJson("/evenements/{$event->id}")->assertForbidden();

        $this->assertSame('Atelier', $event->fresh()->nom_event);
    }

    public function test_civique_ne_peut_pas_ecrire_ailleurs(): void
    {
        $user = $this->civique();

        $this->actingAs($user)->post('/salles', ['nom_salle' => 'X'])->assertForbidden();
        $this->actingAs($user)->post('/users', ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->post('/conges', [])->assertForbidden();
    }

    public function test_civique_garde_la_main_sur_son_compte(): void
    {
        $user = $this->civique();

        $this->actingAs($user)->post('/user/theme-color', ['color' => 'green'])->assertOk();
        $this->assertSame('green', $user->fresh()->theme);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_un_utilisateur_normal_n_est_pas_restreint(): void
    {
        $this->actingAs(User::factory()->create())->get('/evenements/create')->assertOk();
    }

    public function test_le_statut_se_gere_depuis_la_fiche_utilisateur(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $cible = User::factory()->create();

        $this->actingAs($admin)->put("/users/{$cible->id}", [
            'name' => $cible->name, 'email' => $cible->email, 'is_civique' => 1,
            'jours' => [], 'horaire_id' => '',
        ]);

        $this->assertTrue($cible->fresh()->is_civique);
    }
}
