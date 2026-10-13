<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiltresProjetsTachesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Benjamin Leroy']);
    }

    /** @dataProvider pages */
    public function test_le_tiroir_de_filtres_remplace_la_barre(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        foreach (['id="pfTiroir"', 'id="pfTexte"', 'id="pfDe"', 'id="pfA"', 'data-pf-rapide="mien"', 'data-pf-rapide="retard"', 'data-dock-filtres'] as $attendu) {
            $this->assertStringContainsString($attendu, $html, $attendu);
        }
        $this->assertStringNotContainsString('id="ptFiltres"', $html, "l'ancienne barre de filtres a disparu");
        $this->assertMatchesRegularExpression('/<details class="fgroup mb-4">\s*<summary class="fsec">Personne/', $html, 'Personne : accordéon replié');
        $this->assertStringNotContainsString('<details class="fgroup mb-4" open', $html);
    }

    public static function pages(): array
    {
        return ['projets' => ['/projets'], 'tâches' => ['/taches']];
    }

    public function test_les_personnes_sont_proposees_avec_leur_nom_complet_sur_les_deux_pages(): void
    {
        $marie = User::factory()->create(['name' => 'Marie Durand']);
        $projet = Projet::factory()->create(['nom' => 'Alpha']);
        $tache = Tache::factory()->create(['projet_id' => $projet->id]);
        $tache->responsables()->attach($marie->id);
        $projet->membres()->attach($this->admin->id, ['role' => 'referent']);

        foreach (['/projets', '/taches'] as $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertSee('data-pf-pers="'.$marie->id.'"', false)
                ->assertSee('>Marie Durand</button>', false);
        }
    }

    public function test_les_cartes_portent_les_donnees_de_filtrage(): void
    {
        $projet = Projet::factory()->create(['nom' => 'Rénovation Salle', 'date_limite' => '2026-11-20']);
        $marie = User::factory()->create();
        $tache = Tache::factory()->create(['projet_id' => $projet->id, 'titre' => 'Peindre le mur']);
        $tache->responsables()->attach($marie->id);

        $html = $this->actingAs($this->admin)->get('/projets')->getContent();
        $this->assertStringContainsString('data-titre="rénovation salle"', $html);
        $this->assertStringContainsString('data-date="2026-11-20"', $html);
        $this->assertStringContainsString('data-pers="'.$marie->id.'"', $html);

        $html = $this->actingAs($this->admin)->get('/taches')->getContent();
        $this->assertStringContainsString('data-titre="peindre le mur"', $html);
        $this->assertStringContainsString('data-nom="rénovation salle"', $html);
    }

    public function test_les_projets_termines_ou_annules_sont_ranges_dans_un_accordeon(): void
    {
        Projet::factory()->create(['nom' => 'En route', 'etat' => 'en_cours']);
        Projet::factory()->create(['nom' => 'Fini', 'etat' => 'termine']);
        Projet::factory()->create(['nom' => 'Abandonné', 'etat' => 'annule']);

        $html = $this->actingAs($this->admin)->get('/projets')->assertOk()->getContent();

        $this->assertStringContainsString('id="ptClos"', $html);
        $this->assertStringContainsString('Projets terminés ou annulés', $html);

        $enCours = substr($html, strpos($html, 'id="ptGrille"'), strpos($html, 'id="ptClos"') - strpos($html, 'id="ptGrille"'));
        $this->assertStringContainsString('En route', $enCours);
        $this->assertStringNotContainsString('Fini', $enCours);
        $this->assertStringNotContainsString('Abandonné', $enCours);

        $clos = substr($html, strpos($html, 'id="ptClos"'));
        $this->assertStringContainsString('Fini', $clos);
        $this->assertStringContainsString('Abandonné', $clos);
        $this->assertStringContainsString('pt-mini-projet', $clos, 'les projets clos sont en mini-cartes');
        $this->assertStringNotContainsString('class="pt-carte ', $clos);
    }

    public function test_pas_d_accordeon_sans_projet_termine(): void
    {
        Projet::factory()->create(['etat' => 'en_cours']);

        $this->actingAs($this->admin)->get('/projets')->assertOk()->assertDontSee('id="ptClos"', false);
    }
}
