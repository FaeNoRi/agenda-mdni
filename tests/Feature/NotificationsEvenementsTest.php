<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Evenements;
use App\Models\Salles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationsEvenementsTest extends TestCase
{
    use RefreshDatabase;

    private User $auteur;
    private User $anne;
    private User $bob;
    private User $clara;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);
        Http::fake();

        DB::table('users')->insert([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'equipe@example.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0, 'is_email' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->auteur = User::factory()->create(['is_admin' => true, 'is_equipe' => 1]);
        $this->anne = User::factory()->create(['is_equipe' => 1, 'is_email' => 0]);   // sans e-mail : notifiée quand même
        $this->bob = User::factory()->create(['is_equipe' => 1]);
        $this->clara = User::factory()->create(['is_equipe' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(array $userIds, array $o = []): Evenements
    {
        $e = Evenements::create(array_merge([
            'nom_event' => 'Atelier Ozobot', 'type_event' => 'Atelier', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'Test',
            'date_heure_debut' => '2026-10-13 14:00:00', 'date_heure_fin' => '2026-10-13 16:00:00',
        ], $o));
        $e->users()->sync($userIds);

        return $e;
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'nom_event' => 'Atelier Ozobot', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'type_event' => 'Atelier', 'type_public' => 'Autres', 'devis' => 'Non', 'facture' => 'Non',
            'reglement' => 'Non', 'desc_event' => 'x', 'objet' => 'Non',
            'date_heure_debut' => '2026-10-13T14:00', 'date_heure_fin' => '2026-10-13T16:00',
        ], $o);
    }

    private function de(User $u, ?string $type = null)
    {
        $q = AppNotification::where('user_id', $u->id);

        return $type ? $q->where('type', $type) : $q;
    }

    public function test_creation_notifie_les_animateurs_cites(): void
    {
        $this->actingAs($this->auteur)->post('/evenements', $this->payload(['users' => [$this->anne->id, $this->auteur->id]]))->assertRedirect();

        $n = $this->de($this->anne, 'evenement.cree')->firstOrFail();
        $this->assertSame('Nouvel événement', $n->titre);
        $this->assertStringContainsString('**Atelier Ozobot**', $n->contexte);
        $this->assertStringContainsString('Mardi 13 Octobre', $n->contexte);
        $this->assertStringContainsString('14h00 - 16h00', $n->contexte);
        $this->assertSame('J-4', $n->jalon);
        $this->assertSame('2026-10-13', $n->echeance->toDateString());
        $this->assertSame(0, $this->de($this->auteur)->count(), "l'auteur n'est pas notifié");
        $this->assertSame(0, $this->de($this->bob)->count());
    }

    public function test_toute_l_equipe_vise_les_membres_de_l_equipe(): void
    {
        $this->actingAs($this->auteur)->post('/evenements', $this->payload(['users' => [0]]))->assertRedirect();

        $this->assertSame(1, $this->de($this->anne, 'evenement.cree')->count());
        $this->assertSame(1, $this->de($this->bob, 'evenement.cree')->count());
        $this->assertSame(0, $this->de($this->clara)->count(), 'hors équipe');
        $this->assertSame(0, AppNotification::where('user_id', 0)->count());
    }

    public function test_modification_de_l_horaire_ou_de_la_salle(): void
    {
        $e = $this->event([$this->anne->id]);
        $salle = Salles::create(['nom_salle' => 'Salle 2', 'type_salle' => 'Salle']);

        $this->actingAs($this->auteur)->put("/evenements/{$e->id}", $this->payload([
            'date_heure_debut' => '2026-10-13T15:00', 'date_heure_fin' => '2026-10-13T17:00',
            'users' => [$this->anne->id], 'salles' => [$salle->id],
        ]))->assertRedirect();

        $n = $this->de($this->anne, 'evenement.modifie')->firstOrFail();
        $this->assertStringContainsString('horaire : 14h00 - 16h00 → 15h00 - 17h00', $n->contexte);
        $this->assertStringContainsString('salle : aucune → Salle 2', $n->contexte);
    }

    public function test_une_modification_sans_effet_visible_ne_notifie_pas(): void
    {
        $e = $this->event([$this->anne->id]);

        $this->actingAs($this->auteur)->put("/evenements/{$e->id}", $this->payload(['nom_event' => 'Atelier Ozobot', 'desc_event' => 'autre texte', 'users' => [$this->anne->id]]))
            ->assertRedirect();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_ajout_et_retrait_d_un_participant(): void
    {
        $e = $this->event([$this->anne->id, $this->bob->id]);

        $this->actingAs($this->auteur)->put("/evenements/{$e->id}", $this->payload(['users' => [$this->anne->id, $this->clara->id]]))->assertRedirect();

        $this->assertSame(1, $this->de($this->clara, 'evenement.participation')->count());
        $this->assertSame('Vous êtes ajouté à un événement', $this->de($this->clara)->first()->titre);
        $this->assertSame(1, $this->de($this->bob, 'evenement.participation')->count());
        $this->assertSame("Vous n'êtes plus affecté à un événement", $this->de($this->bob)->first()->titre);
        $this->assertSame(0, $this->de($this->anne)->count(), 'rien de visible n\'a changé pour elle');
    }

    public function test_annulation_puis_retablissement(): void
    {
        $e = $this->event([$this->anne->id]);

        $this->actingAs($this->auteur)->put("/evenements/{$e->id}", $this->payload(['type_event' => 'Annule', 'users' => [$this->anne->id]]))->assertRedirect();
        $this->assertSame('Événement annulé', $this->de($this->anne, 'evenement.annule')->firstOrFail()->titre);

        $this->actingAs($this->auteur)->put("/evenements/{$e->id}", $this->payload(['type_event' => 'Atelier', 'users' => [$this->anne->id]]))->assertRedirect();
        $this->assertSame(2, $this->de($this->anne, 'evenement.annule')->count());
        $this->assertSame('Événement rétabli', $this->de($this->anne)->orderByDesc('id')->first()->titre);
    }

    public function test_une_modification_d_un_evenement_deja_annule_ne_renotifie_pas(): void
    {
        $e = $this->event([$this->anne->id], ['type_event' => 'Annule']);

        $this->actingAs($this->auteur)->put("/evenements/{$e->id}", $this->payload(['type_event' => 'Annule', 'nom_event' => 'Autre nom', 'users' => [$this->anne->id]]))->assertRedirect();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_suppression_d_un_evenement(): void
    {
        $e = $this->event([$this->anne->id]);

        $this->actingAs($this->auteur)->delete("/evenements/{$e->id}")->assertRedirect();

        $this->assertStringContainsString('est supprimé', $this->de($this->anne, 'evenement.annule')->firstOrFail()->contexte);
    }

    public function test_pas_d_arriere_pour_ceux_qui_ne_voient_pas_encore_la_cloche(): void
    {
        config(['features.projets_taches' => false]);
        $simple = User::factory()->create(['is_equipe' => 1]);

        $this->actingAs($this->auteur)->post('/evenements', $this->payload(['users' => [$simple->id, $this->anne->id]]))->assertRedirect();

        $this->assertSame(0, AppNotification::count(), 'le module n\'est pas ouvert : seuls les administrateurs voient la cloche');
    }
}
