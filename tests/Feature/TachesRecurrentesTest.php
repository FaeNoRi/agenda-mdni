<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\Recurrence;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TachesRecurrentesTest extends TestCase
{
    use RefreshDatabase;

    private User $membre;
    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);

        $this->membre = User::factory()->create();
        $this->projet = Projet::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function creer(array $surcharge = []): Tache
    {
        $this->actingAs($this->membre)->postJson('/taches', array_merge([
            'titre' => 'Relever le courrier',
            'projet_id' => $this->projet->id,
            'date_limite' => '2026-10-12',
            'details' => 'Boîte aux lettres',
            'responsables' => [$this->membre->id],
            'liens' => [['libelle' => 'Procédure', 'url' => 'https://exemple.test/p']],
            'recurrent' => 1,
            'frequence' => 'hebdomadaire',
            'date_fin' => '2026-11-02',
        ], $surcharge))->assertOk();

        return Tache::orderBy('id')->firstOrFail();
    }

    private function statut(Tache $t, string $statut, array $plus = [])
    {
        return $this->actingAs($this->membre)->postJson(route('taches.statut', $t), ['statut' => $statut] + $plus);
    }

    public function test_creer_une_tache_recurrente(): void
    {
        $t = $this->creer();

        $this->assertNotNull($t->recurrence_id);
        $this->assertSame('hebdomadaire', $t->recurrence->frequence);
        $this->assertSame('2026-11-02', $t->recurrence->date_fin->toDateString());
        $this->assertSame(1, Tache::count(), 'une seule occurrence au départ');
    }

    public function test_une_tache_simple_n_a_pas_de_recurrence(): void
    {
        $this->creer(['recurrent' => 0]);

        $this->assertNull(Tache::first()->recurrence_id);
        $this->assertSame(0, Recurrence::count());
    }

    public function test_la_frequence_et_la_date_de_fin_sont_exigees_et_coherentes(): void
    {
        $base = ['titre' => 'X', 'date_limite' => '2026-10-12', 'responsables' => [$this->membre->id], 'recurrent' => 1];

        $this->actingAs($this->membre)->postJson('/taches', $base)
            ->assertStatus(422)->assertJsonValidationErrors(['frequence', 'date_fin']);
        $this->actingAs($this->membre)->postJson('/taches', $base + ['frequence' => 'hebdomadaire', 'date_fin' => '2026-10-12'])
            ->assertStatus(422)->assertJsonValidationErrors('date_fin');
        $this->actingAs($this->membre)->postJson('/taches', $base + ['frequence' => 'annuelle', 'date_fin' => '2027-01-01'])
            ->assertStatus(422)->assertJsonValidationErrors('frequence');
        $this->assertSame(0, Tache::count());
    }

    public function test_terminer_une_occurrence_cree_la_suivante(): void
    {
        $t = $this->creer();

        $this->statut($t, 'termine')->assertOk();

        $this->assertSame(2, Tache::count());
        $suivante = Tache::orderByDesc('id')->first();
        $this->assertSame('2026-10-19', $suivante->date_limite->toDateString());
        $this->assertSame('a_faire', $suivante->statut->value);
        $this->assertSame($t->recurrence_id, $suivante->recurrence_id);
        $this->assertSame('Relever le courrier', $suivante->titre);
        $this->assertSame('Boîte aux lettres', $suivante->details);
        $this->assertSame($this->projet->id, $suivante->projet_id);
        $this->assertSame([$this->membre->id], $suivante->responsables->pluck('id')->all());
        $this->assertSame(['https://exemple.test/p'], $suivante->liens->pluck('url')->all());
        $this->assertSame(1, $suivante->historiques()->count());
    }

    public function test_rouvrir_puis_reterminer_ne_duplique_pas(): void
    {
        $t = $this->creer();

        $this->statut($t, 'termine');
        $this->statut($t->fresh(), 'en_cours');
        $this->statut($t->fresh(), 'termine');

        $this->assertSame(2, Tache::count());
    }

    public function test_pas_de_suivante_apres_la_date_de_fin(): void
    {
        $t = $this->creer(['date_fin' => '2026-10-20']);

        $this->statut($t, 'termine');                       // 19/10 créée
        $this->statut(Tache::orderByDesc('id')->first(), 'termine'); // 26/10 > fin : stop

        $this->assertSame(2, Tache::count());
    }

    public function test_les_autres_statuts_ne_creent_rien(): void
    {
        $t = $this->creer();

        foreach (['en_cours', 'a_valider'] as $s) {
            $this->statut($t->fresh(), $s)->assertOk();
        }
        $this->statut($t->fresh(), 'bloque', ['raison' => 'Attente'])->assertOk();

        $this->assertSame(1, Tache::count());
    }

    public function test_annuler_exige_de_choisir_la_portee(): void
    {
        $t = $this->creer();

        $this->statut($t, 'annule')->assertStatus(422)->assertJsonValidationErrors('portee');
        $this->assertSame('a_faire', $t->fresh()->statut->value);
    }

    public function test_annuler_cette_occurrence_seulement_cree_la_suivante(): void
    {
        $t = $this->creer();

        $this->statut($t, 'annule', ['portee' => 'occurrence'])->assertOk();

        $this->assertSame('annule', $t->fresh()->statut->value);
        $this->assertSame(2, Tache::count());
        $this->assertSame('2026-10-19', Tache::orderByDesc('id')->first()->date_limite->toDateString());
        $this->assertFalse($t->recurrence->fresh()->arretee);
    }

    public function test_annuler_toutes_les_occurrences_arrete_la_serie(): void
    {
        $t = $this->creer();

        $this->statut($t, 'annule', ['portee' => 'serie'])->assertOk();

        $this->assertSame(1, Tache::count());
        $this->assertTrue($t->recurrence->fresh()->arretee);
    }

    public function test_une_tache_simple_s_annule_sans_portee(): void
    {
        $t = Tache::factory()->create(['projet_id' => $this->projet->id]);

        $this->statut($t, 'annule')->assertOk();
    }

    public function test_mensuel_sans_derive_de_fin_de_mois(): void
    {
        $t = $this->creer(['frequence' => 'mensuelle', 'date_limite' => '2026-01-31', 'date_fin' => '2026-06-30']);

        $dates = [];
        $courante = $t;
        for ($i = 0; $i < 4; $i++) {
            $this->statut($courante, 'termine');
            $courante = Tache::orderByDesc('id')->first();
            $dates[] = $courante->date_limite->toDateString();
        }

        $this->assertSame(['2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31'], $dates);
    }

    public function test_la_fiche_et_le_formulaire_montrent_la_recurrence(): void
    {
        $t = $this->creer();

        $this->actingAs($this->membre)->get(route('taches.show', $t))
            ->assertOk()->assertSee('Chaque semaine')->assertSee('02/11/2026')
            ->assertSee('data-portee-bloc', false);
        $this->actingAs($this->membre)->get(route('taches.create'))
            ->assertOk()->assertSee('Tâche récurrente');
        $this->actingAs($this->membre)->get(route('taches.edit', $t))
            ->assertOk()->assertDontSee('name="recurrent"', false);
    }
}
