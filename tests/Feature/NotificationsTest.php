<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notifier;
use App\Support\NotificationTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $membre;
    private User $autre;
    private User $civique;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        config(['features.projets_taches' => true]);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->membre = User::factory()->create();
        $this->autre = User::factory()->create();
        $this->civique = User::factory()->create(['is_civique' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function envoyer(iterable $destinataires, string $type = 'tache.assignee', array $data = [], ?User $acteur = null): int
    {
        return app(Notifier::class)->envoyer($destinataires, $type, $data + ['titre' => 'Titre'], $acteur);
    }

    private function notif(User $u, array $attrs = []): AppNotification
    {
        return AppNotification::create($attrs + [
            'user_id' => $u->id, 'type' => 'tache.assignee', 'categorie' => 'tache', 'titre' => 'Une tâche',
        ]);
    }

    // ---- Notifier --------------------------------------------------------

    public function test_envoyer_cree_une_notification_par_destinataire(): void
    {
        $n = $this->envoyer([$this->membre, $this->autre->id], 'tache.assignee', [
            'contexte' => 'Marie vous a assigné **Imprimer**', 'projet_nom' => 'Fête', 'jalon' => 'J-3', 'url' => '/taches?tache=4',
            'echeance' => '2026-10-12',
        ]);

        $this->assertSame(2, $n);
        $a = AppNotification::where('user_id', $this->membre->id)->firstOrFail();
        $this->assertSame('tache', $a->categorie);
        $this->assertSame('Marie vous a assigné **Imprimer**', $a->contexte);
        $this->assertSame('2026-10-12', $a->echeance->toDateString());
        $this->assertNull($a->vue_at);
    }

    public function test_l_acteur_les_doublons_et_l_equipe_ne_sont_pas_notifies(): void
    {
        $n = $this->envoyer([$this->membre, $this->membre, $this->autre, 0, null], 'tache.assignee', [], $this->autre);

        $this->assertSame(1, $n);
        $this->assertSame([$this->membre->id], AppNotification::pluck('user_id')->all());
    }

    public function test_un_type_desactivable_respecte_les_preferences(): void
    {
        NotificationPreference::create(['user_id' => $this->membre->id, 'type' => 'tache.commentaire', 'actif' => false]);

        $n = $this->envoyer([$this->membre, $this->autre], 'tache.commentaire');

        $this->assertSame(1, $n);
        $this->assertSame([$this->autre->id], AppNotification::pluck('user_id')->all());
    }

    public function test_un_type_impose_ignore_les_preferences(): void
    {
        NotificationPreference::create(['user_id' => $this->membre->id, 'type' => 'tache.assignee', 'actif' => false]);

        $this->assertSame(1, $this->envoyer([$this->membre], 'tache.assignee'));
    }

    public function test_la_cle_evite_les_doublons(): void
    {
        $this->assertSame(1, $this->envoyer([$this->membre], 'tache.rappel', ['cle' => 'tache:4:J-3']));
        $this->assertSame(0, $this->envoyer([$this->membre], 'tache.rappel', ['cle' => 'tache:4:J-3']));
        $this->assertSame(1, $this->envoyer([$this->membre], 'tache.rappel', ['cle' => 'tache:4:J-0']));
        $this->assertSame(2, AppNotification::count());
    }

    public function test_un_type_inconnu_est_refuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->envoyer([$this->membre], 'inconnu.type');
    }

    public function test_le_catalogue_est_coherent(): void
    {
        $this->assertTrue(NotificationTypes::imposee('tache.assignee'));
        $this->assertFalse(NotificationTypes::imposee('tache.commentaire'));
        $this->assertContains('projet.etat', NotificationTypes::desactivables());
        $this->assertNotContains('evenement.cree', NotificationTypes::desactivables());
        foreach (['tache', 'projet', 'evenement', 'admin'] as $c) {
            $this->assertNotEmpty(NotificationTypes::parCategorie($c));
            $this->assertArrayHasKey($c, NotificationTypes::CATEGORIES);
        }
    }

    // ---- Tiroir (JSON) ---------------------------------------------------

    public function test_la_liste_ne_contient_que_mes_notifications_recentes(): void
    {
        $this->notif($this->membre, ['titre' => 'Non lue']);
        $this->notif($this->membre, ['titre' => 'Lue récente', 'vue_at' => now()->subDays(5)]);
        $this->notif($this->membre, ['titre' => 'Lue ancienne', 'vue_at' => now()->subDays(31)]);
        $this->notif($this->autre, ['titre' => 'À un autre']);

        $r = $this->actingAs($this->membre)->getJson('/notifications')->assertOk();

        $titres = collect($r->json('notifications'))->pluck('titre')->all();
        $this->assertEqualsCanonicalizing(['Non lue', 'Lue récente'], $titres);
        $this->assertSame(1, $r->json('non_lues'));
    }

    public function test_la_liste_fournit_les_champs_du_tiroir(): void
    {
        $this->envoyer([$this->membre], 'tache.rappel', [
            'contexte' => '**Réserver** la salle', 'projet_nom' => 'Fête', 'jalon' => 'J-3', 'echeance' => '2026-10-12', 'url' => '/taches?tache=1',
        ]);

        $n = $this->actingAs($this->membre)->getJson('/notifications')->json('notifications.0');

        $this->assertSame('tache', $n['categorie']);
        $this->assertSame('clock', $n['icone']);
        $this->assertSame(3, $n['jours']);
        $this->assertSame('J-3', $n['jalon']);
        $this->assertSame('Fête', $n['projet']);
        $this->assertFalse($n['vue']);
    }

    public function test_les_plus_recentes_viennent_en_premier(): void
    {
        $vieille = $this->notif($this->membre, ['titre' => 'Vieille']);
        $vieille->forceFill(['created_at' => now()->subHours(3)])->save();
        $this->notif($this->membre, ['titre' => 'Récente']);

        $titres = collect($this->actingAs($this->membre)->getJson('/notifications')->json('notifications'))->pluck('titre')->all();

        $this->assertSame(['Récente', 'Vieille'], $titres);
    }

    public function test_le_compteur(): void
    {
        $this->notif($this->membre);
        $this->notif($this->membre, ['vue_at' => now()]);

        $this->actingAs($this->membre)->getJson('/notifications/compte')->assertOk()->assertJson(['non_lues' => 1]);
    }

    public function test_marquer_comme_lue_puis_remettre_en_non_lue(): void
    {
        $n = $this->notif($this->membre);

        $this->actingAs($this->membre)->postJson("/notifications/{$n->id}/vue", ['vue' => true])->assertOk()->assertJson(['non_lues' => 0]);
        $this->assertNotNull($n->fresh()->vue_at);

        $this->actingAs($this->membre)->postJson("/notifications/{$n->id}/vue", ['vue' => false])->assertOk()->assertJson(['non_lues' => 1]);
        $this->assertNull($n->fresh()->vue_at);
    }

    public function test_on_ne_touche_pas_aux_notifications_des_autres(): void
    {
        $n = $this->notif($this->autre);

        $this->actingAs($this->membre)->postJson("/notifications/{$n->id}/vue", ['vue' => true])->assertForbidden();
        $this->assertNull($n->fresh()->vue_at);
    }

    public function test_tout_marquer_comme_lu_ne_concerne_que_moi(): void
    {
        $this->notif($this->membre);
        $this->notif($this->membre);
        $autre = $this->notif($this->autre);

        $this->actingAs($this->membre)->postJson('/notifications/tout-vu')->assertOk()->assertJson(['non_lues' => 0]);

        $this->assertSame(0, $this->membre->notificationsApp()->nonVues()->count());
        $this->assertNull($autre->fresh()->vue_at);
    }

    public function test_le_service_civique_peut_marquer_comme_lue(): void
    {
        $n = $this->notif($this->civique);

        $this->actingAs($this->civique)->postJson("/notifications/{$n->id}/vue")->assertOk();
        $this->actingAs($this->civique)->postJson('/notifications/tout-vu')->assertOk();
    }

    // ---- Ouverture progressive ------------------------------------------

    public function test_reserve_aux_administrateurs_tant_que_le_module_n_est_pas_ouvert(): void
    {
        config(['features.projets_taches' => false]);

        $this->actingAs($this->membre)->getJson('/notifications')->assertNotFound();
        $this->actingAs($this->admin)->getJson('/notifications')->assertOk();
    }

    public function test_la_cloche_est_dans_la_barre_de_navigation(): void
    {
        $this->notif($this->admin);
        $this->notif($this->admin);

        $html = $this->actingAs($this->admin)->get('/profile')->assertOk()->getContent();

        $this->assertStringContainsString('data-bs-target="#offcanvasNotifications"', $html);
        $this->assertStringContainsString('id="offcanvasNotifications"', $html);
        $this->assertMatchesRegularExpression('/js-notif-badge\s*">2</', $html);

        config(['features.projets_taches' => false]);
        $html = $this->actingAs($this->membre)->get('/profile')->assertOk()->getContent();
        $this->assertStringNotContainsString('offcanvasNotifications', $html);
    }

    public function test_le_tiroir_se_ferme_par_clic_exterieur_sans_voile(): void
    {
        $html = $this->actingAs($this->admin)->get('/profile')->assertOk()->getContent();

        // pas de voile Bootstrap (créé en double, il restait affiché) et fermeture par clic en dehors
        $this->assertMatchesRegularExpression('/id="offcanvasNotifications"[^>]*data-bs-backdrop="false"/', $html);
        $this->assertStringContainsString("document.querySelectorAll('.offcanvas.show')", $html);
    }

    // ---- Préférences du Profil ------------------------------------------

    public function test_la_page_profil_liste_les_preferences(): void
    {
        $this->actingAs($this->membre)->get('/profile')->assertOk()
            ->assertSee('Toujours active')
            ->assertSee('Nouveau commentaire sur une tâche')
            ->assertSee('name="types[]" value="tache.commentaire"', false);
    }

    public function test_enregistrer_les_preferences(): void
    {
        $this->actingAs($this->membre)->patch('/profile/notifications', ['types' => ['tache.commentaire', 'projet.etat', 'tache.assignee', 'n_importe_quoi']])
            ->assertRedirect(route('profile.edit'));

        $actifs = NotificationPreference::where('user_id', $this->membre->id)->where('actif', true)->pluck('type')->all();
        $this->assertEqualsCanonicalizing(['tache.commentaire', 'projet.etat'], $actifs);

        $this->assertFalse(NotificationPreference::where('user_id', $this->membre->id)->where('type', 'tache.modifiee')->value('actif'));
        $this->assertSame(0, NotificationPreference::where('type', 'tache.assignee')->count(), 'un type imposé ne se règle pas');
        $this->assertSame(0, NotificationPreference::where('type', 'n_importe_quoi')->count());

        // Le choix est pris en compte à l'envoi.
        $this->assertSame(0, $this->envoyer([$this->membre], 'tache.modifiee'));
        $this->assertSame(1, $this->envoyer([$this->membre], 'tache.commentaire'));
    }

    public function test_les_preferences_cochees_apparaissent_cochees(): void
    {
        NotificationPreference::create(['user_id' => $this->membre->id, 'type' => 'tache.commentaire', 'actif' => false]);

        $html = $this->actingAs($this->membre)->get('/profile')->getContent();

        $this->assertMatchesRegularExpression('/value="tache\.commentaire"\s*>/', $html);
        $this->assertMatchesRegularExpression('/value="tache\.modifiee"\s*checked/', $html);
    }
}
