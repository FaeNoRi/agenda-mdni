<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\Objets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQuickFiltersTest extends TestCase
{
    use RefreshDatabase;

    private const DAY = '2026-10-07';

    private function event(string $nom, array $overrides = [], array $users = [], array $objets = []): Evenements
    {
        $event = Evenements::create(array_merge([
            'nom_event' => $nom, 'type_event' => 'Atelier', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'T',
            'date_heure_debut' => self::DAY.' 10:00:00', 'date_heure_fin' => self::DAY.' 11:00:00',
        ], $overrides));

        $event->users()->sync($users);

        foreach ($objets as $objNom => $etat) {
            $event->objets()->attach(Objets::create(['nom_obj' => $objNom])->id, ['etat' => $etat]);
        }

        return $event;
    }

    private function cards(User $user, string $query = '')
    {
        return $this->actingAs($user)->get('/dashboard/cards?from='.self::DAY.'&to='.self::DAY.$query);
    }

    public function test_sans_filtre_tous_les_evenements_sont_la(): void
    {
        $this->event('Alpha');
        $this->event('Bravo');

        $this->cards(User::factory()->create())->assertSee('Alpha')->assertSee('Bravo');
    }

    public function test_filtre_vous_participez(): void
    {
        $moi = User::factory()->create();
        $autre = User::factory()->create();
        $this->event('Avec moi', [], [$moi->id]);
        $this->event('Avec autre', [], [$autre->id]);
        $this->event('Annulé pour moi', ['type_event' => 'Annulé'], [$moi->id]);

        $this->cards($moi, '&flag[]=participe')
            ->assertSee('Avec moi')
            ->assertDontSee('Avec autre')
            ->assertDontSee('Annulé pour moi');
    }

    public function test_filtre_participe_inclut_toute_l_equipe_pour_un_membre(): void
    {
        $equipe = User::factory()->create(['is_equipe' => true]);
        $exterieur = User::factory()->create(['is_equipe' => false]);
        User::unguarded(fn () => User::create([
            'id' => 0, 'name' => 'Toute l\'équipe', 'email' => 'team@x.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0,
        ]));
        $this->event('Pour l\'équipe', [], [0]);

        $this->cards($equipe, '&flag[]=participe')->assertSee('Pour l\'équipe');
        $this->cards($exterieur, '&flag[]=participe')->assertDontSee('Pour l\'équipe');
    }

    public function test_filtre_objets_a_faire_ne_garde_que_les_objets_en_attente(): void
    {
        $this->event('Objets en cours', ['objet' => 'Oui'], [], ['Ozobot' => 'A faire']);
        $this->event('Objets prêts', ['objet' => 'Oui'], [], ['Support' => 'Fait']);
        $this->event('Sans objet');

        $this->cards(User::factory()->create(), '&flag[]=objets')
            ->assertSee('Objets en cours')
            ->assertDontSee('Objets prêts')
            ->assertDontSee('Sans objet');
    }

    public function test_filtre_photos_a_prendre(): void
    {
        $this->event('Avec photos', ['prendre_photos' => true]);
        $this->event('Sans photos');
        $this->event('Photos annulées', ['prendre_photos' => true, 'type_event' => 'Annule']);

        $this->cards(User::factory()->create(), '&flag[]=photos')
            ->assertSee('Avec photos')
            ->assertDontSee('Sans photos')
            ->assertDontSee('Photos annulées');
    }

    public function test_les_filtres_rapides_se_cumulent(): void
    {
        $moi = User::factory()->create();
        $this->event('Moi + photos', ['prendre_photos' => true], [$moi->id]);
        $this->event('Moi seul', [], [$moi->id]);
        $this->event('Photos seules', ['prendre_photos' => true]);

        $this->cards($moi, '&flag[]=participe&flag[]=photos')
            ->assertSee('Moi + photos')
            ->assertDontSee('Moi seul')
            ->assertDontSee('Photos seules');
    }

    public function test_un_drapeau_inconnu_est_ignore(): void
    {
        $this->event('Alpha');

        $this->cards(User::factory()->create(), '&flag[]=nimporte')->assertSee('Alpha');
    }

    public function test_plusieurs_types_peuvent_etre_selectionnes(): void
    {
        $this->event('Un atelier', ['type_event' => 'Atelier']);
        $this->event('Une réunion', ['type_event' => 'Réunion']);
        $this->event('Une location', ['type_event' => 'Location']);

        $this->cards(User::factory()->create(), '&type[]=Atelier&type[]=Réunion')
            ->assertSee('Un atelier')
            ->assertSee('Une réunion')
            ->assertDontSee('Une location');
    }

    public function test_le_calendrier_applique_les_memes_filtres_rapides(): void
    {
        $this->event('Avec photos', ['prendre_photos' => true]);
        $this->event('Sans photos');

        $titles = collect(
            $this->actingAs(User::factory()->create())
                ->getJson('/dashboard/calendar-data?flag[]=photos&start='.self::DAY.'&end=2026-10-08')
                ->assertOk()->json()
        )->pluck('title');

        $this->assertSame(['Avec photos'], $titles->all());
    }

    public function test_le_tableau_de_bord_affiche_les_pastilles_de_filtre(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertSee('id="quickFilters"', false)
            ->assertSee('data-flag="participe"', false)
            ->assertSee('data-flag="objets"', false)
            ->assertSee('data-flag="photos"', false);
    }

    public function test_le_filtre_personne_exclut_l_equipe_et_non(): void
    {
        User::unguarded(fn () => User::create([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'team@x.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0,
        ]));
        User::factory()->create(['name' => 'Non']);
        User::factory()->create(['name' => 'Marie Dupont']);

        $html = $this->actingAs(User::factory()->create(['name' => 'Admin']))->get('/dashboard')->getContent();
        preg_match('/id="filter-user".*?<\/select>/s', $html, $m);

        $this->assertStringContainsString('Marie Dupont', $m[0]);
        $this->assertStringNotContainsString("Toute l'équipe", $m[0]);
        $this->assertStringNotContainsString('>Non<', $m[0]);
    }

    public function test_l_ordre_des_sections_du_tiroir(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertSeeInOrder(['Filtres rapides', 'data-count-for="filter-type"', 'data-count-for="filter-user"', 'data-count-for="filter-salle"'], false);
    }
}
