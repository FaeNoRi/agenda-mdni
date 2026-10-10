<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Evenements;
use App\Models\Objets;
use App\Models\Projet;
use App\Models\Salles;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RappelsPlanifiesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $marie;
    private User $paul;
    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 07:00:00');
        config(['features.projets_taches' => true]);

        DB::table('users')->insert([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'equipe@example.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0, 'is_email' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Alice Admin']);
        $this->marie = User::factory()->create(['name' => 'Marie Durand', 'is_equipe' => 1]);
        $this->paul = User::factory()->create(['name' => 'Paul Martin', 'is_equipe' => 1]);

        $this->projet = Projet::factory()->create(['nom' => 'Fête de la science', 'etat' => 'en_cours', 'date_limite' => '2026-12-31']);
        $this->projet->membres()->attach($this->admin->id, ['role' => 'referent']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function lancer(?string $date = null)
    {
        return $this->artisan('notifications:rappels', $date ? ['--date' => $date] : [])->assertSuccessful();
    }

    private function tache(string $date, array $attrs = []): Tache
    {
        $t = Tache::factory()->create($attrs + ['projet_id' => $this->projet->id, 'titre' => 'Imprimer', 'date_limite' => $date]);
        $t->responsables()->attach($this->marie->id);

        return $t;
    }

    private function de(User $u, string $type)
    {
        return AppNotification::where('user_id', $u->id)->where('type', $type);
    }

    private function evenement(string $debut, array $users = [], array $o = []): Evenements
    {
        $e = Evenements::create($o + [
            'nom_event' => 'Atelier Ozobot', 'type_event' => 'Atelier', 'type_public' => 'Autres', 'desc_event' => 'x',
            'commanditaire_event' => 'MDNI', 'nbpart' => 4, 'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'T',
            'date_heure_debut' => $debut, 'date_heure_fin' => Carbon::parse($debut)->addHours(2)->toDateTimeString(),
        ]);
        $e->users()->sync($users);

        return $e;
    }

    // ---- Tâches ----------------------------------------------------------

    /** @dataProvider jalons */
    public function test_rappel_de_tache_aux_jalons(string $echeance, ?string $jalon): void
    {
        $this->tache($echeance);

        $this->lancer();

        $n = $this->de($this->marie, 'tache.rappel')->first();
        if ($jalon === null) {
            $this->assertNull($n);
            return;
        }
        $this->assertNotNull($n);
        $this->assertSame($jalon, $n->jalon);
        $this->assertStringContainsString('**Imprimer**', $n->contexte);
        $this->assertSame(1, $this->de($this->admin, 'tache.rappel')->count(), 'le référent est prévenu aussi');
    }

    public static function jalons(): array
    {
        return [
            'J-15' => ['2026-10-24', 'J-15'],
            'J-7' => ['2026-10-16', 'J-7'],
            'J-3' => ['2026-10-12', 'J-3'],
            'jour J' => ['2026-10-09', "Aujourd'hui"],
            'J-10 : rien' => ['2026-10-19', null],
            'J-1 : rien' => ['2026-10-10', null],
        ];
    }

    public function test_relancer_la_commande_ne_cree_pas_de_doublon(): void
    {
        $this->tache('2026-10-12');

        $this->lancer();
        $this->lancer();

        $this->assertSame(1, $this->de($this->marie, 'tache.rappel')->count());
    }

    public function test_pas_de_rappel_pour_une_tache_terminee_ou_annulee(): void
    {
        $this->tache('2026-10-12', ['statut' => 'termine']);
        $this->tache('2026-10-12', ['statut' => 'annule']);

        $this->lancer();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_une_tache_a_valider_est_rappelee(): void
    {
        $this->tache('2026-10-12', ['statut' => 'a_valider']);

        $this->lancer();

        $this->assertSame(1, $this->de($this->marie, 'tache.rappel')->count());
    }

    public function test_relance_hebdomadaire_des_taches_en_retard(): void
    {
        $this->tache('2026-10-08');   // 1 jour de retard aujourd'hui

        $this->lancer();
        $n = $this->de($this->marie, 'tache.retard')->firstOrFail();
        $this->assertSame('1 j de retard', $n->jalon);

        $this->lancer('2026-10-10');  // 2 jours : pas de relance
        $this->assertSame(1, $this->de($this->marie, 'tache.retard')->count());

        $this->lancer('2026-10-15');  // 7 jours de retard : pas encore
        $this->assertSame(1, $this->de($this->marie, 'tache.retard')->count());

        $this->lancer('2026-10-16');  // 8 jours de retard : relance hebdomadaire
        $this->assertSame(2, $this->de($this->marie, 'tache.retard')->count());
    }

    public function test_une_tache_en_retard_respecte_la_preference_de_la_personne(): void
    {
        \App\Models\NotificationPreference::create(['user_id' => $this->marie->id, 'type' => 'tache.retard', 'actif' => false]);
        $this->tache('2026-10-08');

        $this->lancer();

        $this->assertSame(0, $this->de($this->marie, 'tache.retard')->count());
        $this->assertSame(1, $this->de($this->admin, 'tache.retard')->count());
    }

    // ---- Projets ---------------------------------------------------------

    public function test_rappel_d_echeance_de_projet(): void
    {
        $this->projet->update(['date_limite' => '2026-10-12']);

        $this->lancer();

        $n = $this->de($this->admin, 'projet.rappel')->firstOrFail();
        $this->assertSame('J-3', $n->jalon);
        $this->assertSame(0, $this->de($this->marie, 'projet.rappel')->count(), 'les référents seulement');
    }

    public function test_projet_depasse_et_projet_clos(): void
    {
        $this->projet->update(['date_limite' => '2026-10-08']);
        $clos = Projet::factory()->create(['etat' => 'termine', 'date_limite' => '2026-10-12']);
        $clos->membres()->attach($this->admin->id, ['role' => 'referent']);

        $this->lancer();

        $n = $this->de($this->admin, 'projet.rappel')->get();
        $this->assertCount(1, $n);
        $this->assertSame('1 j de retard', $n->first()->jalon);
    }

    // ---- Événements ------------------------------------------------------

    public function test_objets_a_remettre_a_j7_et_j3(): void
    {
        $e = $this->evenement('2026-10-16 14:00:00', [$this->marie->id], ['objet' => 'Oui']);
        $e->objets()->sync([Objets::create(['nom_obj' => 'Kit Ozobot'])->id => ['etat' => 'A faire']]);

        $this->lancer();
        $n = $this->de($this->marie, 'evenement.objets')->firstOrFail();
        $this->assertSame('J-7', $n->jalon);
        $this->assertStringContainsString('Kit Ozobot', $n->contexte);
        $this->assertStringContainsString('Prenez contact au plus vite', $n->contexte);

        $this->lancer('2026-10-13');
        $this->assertSame(2, $this->de($this->marie, 'evenement.objets')->count());
        $this->assertSame(0, $this->de($this->paul, 'evenement.objets')->count(), 'participants uniquement');
    }

    public function test_pas_de_rappel_quand_les_objets_sont_prets(): void
    {
        $e = $this->evenement('2026-10-16 14:00:00', [$this->marie->id], ['objet' => 'Oui']);
        $e->objets()->sync([Objets::create(['nom_obj' => 'Kit'])->id => ['etat' => 'Fait']]);

        $this->lancer();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_photos_la_veille(): void
    {
        $this->evenement('2026-10-10 14:00:00', [0], ['prendre_photos' => true]);

        $this->lancer();

        $this->assertSame(1, $this->de($this->marie, 'evenement.photos')->count(), 'toute l\'équipe');
        $this->assertSame(1, $this->de($this->paul, 'evenement.photos')->count());
        $this->assertSame('J-1', $this->de($this->marie, 'evenement.photos')->first()->jalon);
    }

    public function test_pas_de_photos_pour_un_evenement_annule(): void
    {
        $this->evenement('2026-10-10 14:00:00', [$this->marie->id], ['prendre_photos' => true, 'type_event' => 'Annule']);

        $this->lancer();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_conflit_de_salle_a_j7_pour_les_administrateurs(): void
    {
        $salle = Salles::create(['nom_salle' => 'Salle 2', 'type_salle' => 'Salle']);
        $a = $this->evenement('2026-10-16 14:00:00', [$this->marie->id], ['nom_event' => 'Atelier Scratch']);
        $b = $this->evenement('2026-10-16 15:00:00', [$this->paul->id], ['nom_event' => 'Permanence Pix']);
        $a->salles()->sync([$salle->id]);
        $b->salles()->sync([$salle->id]);

        $this->lancer();

        $n = $this->de($this->admin, 'admin.conflit')->get();
        $this->assertCount(1, $n, 'une seule alerte par paire');
        $this->assertStringContainsString('salle : Salle 2', $n->first()->contexte);
        $this->assertSame(0, $this->de($this->marie, 'admin.conflit')->count());
    }

    public function test_pas_de_conflit_sans_chevauchement_ni_pour_un_evenement_annule(): void
    {
        $salle = Salles::create(['nom_salle' => 'Salle 2', 'type_salle' => 'Salle']);
        $a = $this->evenement('2026-10-16 09:00:00', [$this->marie->id]);          // 9h-11h
        $b = $this->evenement('2026-10-16 11:00:00', [$this->marie->id]);          // 11h-13h : colle, ne chevauche pas
        $c = $this->evenement('2026-10-16 10:00:00', [$this->marie->id], ['type_event' => 'Annule']);
        foreach ([$a, $b, $c] as $e) {
            $e->salles()->sync([$salle->id]);
        }

        $this->lancer();

        $this->assertSame(0, $this->de($this->admin, 'admin.conflit')->count());
    }

    public function test_la_personne_en_commun_fait_un_conflit(): void
    {
        $this->evenement('2026-10-16 14:00:00', [$this->marie->id], ['nom_event' => 'A']);
        $this->evenement('2026-10-16 15:00:00', [$this->marie->id], ['nom_event' => 'B']);

        $this->lancer();

        $this->assertStringContainsString('personne : Marie Durand', $this->de($this->admin, 'admin.conflit')->firstOrFail()->contexte);
    }

    // ---- Nettoyage -------------------------------------------------------

    public function test_les_notifications_lues_depuis_plus_de_30_jours_sont_supprimees(): void
    {
        $base = ['user_id' => $this->marie->id, 'type' => 'tache.commentaire', 'categorie' => 'tache', 'titre' => 'x'];
        AppNotification::create($base + ['vue_at' => '2026-08-01 10:00:00']);
        AppNotification::create($base + ['vue_at' => '2026-10-01 10:00:00']);
        AppNotification::create($base);

        $this->lancer();

        $this->assertSame(2, AppNotification::count());
        $this->assertSame(1, AppNotification::whereNull('vue_at')->count());
    }

    public function test_rappels_php_ne_s_execute_pas_depuis_le_web(): void
    {
        $this->assertFileExists(base_path('rappels.php'));
        $this->assertStringContainsString("PHP_SAPI !== 'cli'", file_get_contents(base_path('rappels.php')));
    }
}
