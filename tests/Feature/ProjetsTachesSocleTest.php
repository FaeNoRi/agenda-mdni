<?php

namespace Tests\Feature;

use App\Enums\ProjetEtat;
use App\Enums\TacheStatut;
use App\Models\Commentaire;
use App\Models\Lien;
use App\Models\Projet;
use App\Models\Recurrence;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjetsTachesSocleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tache(array $attrs = [], ?Projet $projet = null): Tache
    {
        return Tache::factory()->create(array_merge(['projet_id' => $projet?->id], $attrs));
    }

    // ---- Statuts ---------------------------------------------------------

    public function test_libelles_et_regles_des_statuts(): void
    {
        $this->assertSame('À valider', TacheStatut::AValider->label());
        $this->assertSame('En attente', ProjetEtat::EnAttente->label());

        foreach ([TacheStatut::Termine, TacheStatut::Annule] as $s) {
            $this->assertFalse($s->estOuvert());
        }
        foreach ([TacheStatut::AFaire, TacheStatut::EnCours, TacheStatut::AValider, TacheStatut::EnAttente, TacheStatut::Bloque] as $s) {
            $this->assertTrue($s->estOuvert());
        }

        $this->assertTrue(TacheStatut::Bloque->exigeRaison());
        $this->assertTrue(TacheStatut::EnAttente->exigeRaison());
        $this->assertFalse(TacheStatut::EnCours->exigeRaison());
        $this->assertTrue(ProjetEtat::Bloque->exigeRaison());
    }

    public function test_accord_des_libelles_au_nombre(): void
    {
        $this->assertSame('bloquée', TacheStatut::Bloque->accorde(1));
        $this->assertSame('bloquées', TacheStatut::Bloque->accorde(2));
        $this->assertSame('terminées', TacheStatut::Termine->accorde(5));
        $this->assertSame('en cours', TacheStatut::EnCours->accorde(3));
    }

    public function test_statut_stocke_en_texte_et_relu_en_enum(): void
    {
        $t = $this->tache(['statut' => 'en_cours']);

        $this->assertSame(TacheStatut::EnCours, $t->fresh()->statut);
        $this->assertDatabaseHas('taches', ['id' => $t->id, 'statut' => 'en_cours']);
    }

    // ---- Retard ----------------------------------------------------------

    public function test_une_tache_en_retard(): void
    {
        $t = $this->tache(['date_limite' => '2026-10-06', 'statut' => 'en_cours']);

        $this->assertTrue($t->estEnRetard());
        $this->assertSame(3, $t->joursDeRetard());
    }

    public function test_pas_de_retard_le_jour_meme_ni_dans_le_futur(): void
    {
        $this->assertFalse($this->tache(['date_limite' => '2026-10-09'])->estEnRetard());
        $this->assertFalse($this->tache(['date_limite' => '2026-10-20'])->estEnRetard());
    }

    public function test_termine_et_annule_ne_sont_jamais_en_retard(): void
    {
        $this->assertFalse($this->tache(['date_limite' => '2026-09-01', 'statut' => 'termine'])->estEnRetard());
        $this->assertFalse($this->tache(['date_limite' => '2026-09-01', 'statut' => 'annule'])->estEnRetard());
    }

    public function test_a_valider_en_attente_et_bloque_restent_en_retard(): void
    {
        foreach (['a_valider', 'en_attente', 'bloque'] as $statut) {
            $this->assertTrue(
                $this->tache(['date_limite' => '2026-09-01', 'statut' => $statut, 'raison' => 'x'])->estEnRetard(),
                $statut
            );
        }
    }

    public function test_retard_d_un_projet(): void
    {
        $this->assertTrue(Projet::factory()->create(['date_limite' => '2026-09-30', 'etat' => 'en_cours'])->estEnRetard());
        $this->assertTrue(Projet::factory()->create(['date_limite' => '2026-09-30', 'etat' => 'a_valider'])->estEnRetard());
        $this->assertFalse(Projet::factory()->create(['date_limite' => '2026-09-30', 'etat' => 'termine'])->estEnRetard());
        $this->assertFalse(Projet::factory()->create(['date_limite' => null])->estEnRetard());
    }

    // ---- Résumé d'un projet ----------------------------------------------

    public function test_resume_du_projet_comme_dans_le_cahier_des_charges(): void
    {
        $projet = Projet::factory()->create();
        foreach (array_merge(
            array_fill(0, 5, 'termine'),
            array_fill(0, 2, 'en_cours'),
            ['bloque']
        ) as $statut) {
            $this->tache(['statut' => $statut, 'raison' => 'x'], $projet);
        }

        $resume = $projet->resume();

        $this->assertSame('8 tâches — 5 terminées — 2 en cours — 1 bloquée', $resume['texte']);
        $this->assertSame(8, $resume['total']);
        $this->assertSame(8, $resume['actives']);
        $this->assertSame(5, $resume['terminees']);
    }

    public function test_les_taches_annulees_sortent_de_l_anneau_mais_pas_du_texte(): void
    {
        $projet = Projet::factory()->create();
        $this->tache(['statut' => 'termine'], $projet);
        $this->tache(['statut' => 'a_faire'], $projet);
        $this->tache(['statut' => 'annule'], $projet);

        $resume = $projet->resume();

        $this->assertSame('3 tâches — 1 terminée — 1 à faire — 1 annulée', $resume['texte']);
        $this->assertSame(2, $resume['actives']);
        $this->assertSame(1, $projet->tachesOuvertes()); // seule la tâche « à faire » est ouverte
    }

    public function test_resume_d_un_projet_vide_et_comptage_des_retards(): void
    {
        $projet = Projet::factory()->create();
        $this->assertSame('Aucune tâche', $projet->resume()['texte']);

        $this->tache(['date_limite' => '2026-10-01', 'statut' => 'en_cours'], $projet);
        $this->tache(['date_limite' => '2026-10-01', 'statut' => 'termine'], $projet);
        $this->assertSame(1, $projet->fresh()->resume()['en_retard']);
        $this->assertSame('2 tâches — 1 terminée — 1 en cours', $projet->fresh()->resume()['texte']);
    }

    // ---- Statut et historique --------------------------------------------

    public function test_la_creation_ecrit_la_premiere_ligne_d_historique(): void
    {
        $auteur = User::factory()->create();
        $t = $this->tache(['created_by' => $auteur->id]);

        $this->assertCount(1, $t->historiques);
        $this->assertSame(TacheStatut::AFaire, $t->historiques->first()->statut);
        $this->assertSame($auteur->id, $t->historiques->first()->user_id);
    }

    public function test_changer_de_statut_ecrit_l_historique(): void
    {
        $moi = User::factory()->create();
        $t = $this->tache();

        $this->assertTrue($t->changerStatut(TacheStatut::EnCours, $moi));
        $this->assertTrue($t->changerStatut(TacheStatut::Termine, $moi));

        $this->assertSame(TacheStatut::Termine, $t->fresh()->statut);
        $this->assertSame(
            ['a_faire', 'en_cours', 'termine'],
            $t->historiques()->get()->map(fn ($h) => $h->statut->value)->all()
        );
        $this->assertSame($moi->id, $t->historiques()->get()->last()->user_id);
    }

    public function test_meme_statut_n_ecrit_rien(): void
    {
        $t = $this->tache();

        $this->assertFalse($t->changerStatut(TacheStatut::AFaire));
        $this->assertCount(1, $t->historiques()->get());
    }

    public function test_bloque_et_en_attente_exigent_une_raison(): void
    {
        $t = $this->tache();

        foreach ([TacheStatut::Bloque, TacheStatut::EnAttente] as $statut) {
            foreach ([null, '', '   '] as $raison) {
                try {
                    $t->changerStatut($statut, null, $raison);
                    $this->fail('La raison aurait dû être exigée.');
                } catch (ValidationException $e) {
                    $this->assertArrayHasKey('raison', $e->errors());
                }
            }
        }

        $this->assertSame(TacheStatut::AFaire, $t->fresh()->statut);
        $this->assertCount(1, $t->historiques()->get());
    }

    public function test_la_raison_est_gardee_puis_effacee(): void
    {
        $t = $this->tache();

        $t->changerStatut(TacheStatut::Bloque, null, '  Attente de la mairie ');
        $this->assertSame('Attente de la mairie', $t->fresh()->raison);
        $this->assertSame('Attente de la mairie', $t->historiques()->get()->last()->raison);

        $t->changerStatut(TacheStatut::EnCours, null, 'ignorée');
        $this->assertNull($t->fresh()->raison);
        $this->assertNull($t->historiques()->get()->last()->raison);
    }

    // ---- Personnes -------------------------------------------------------

    public function test_un_projet_peut_avoir_plusieurs_referents(): void
    {
        [$a, $b, $c] = User::factory()->count(3)->create();
        $projet = Projet::factory()->create();
        $projet->membres()->attach([$a->id => ['role' => 'referent'], $b->id => ['role' => 'referent']]);
        Tache::factory()->create(['projet_id' => $projet->id])->responsables()->attach($c->id);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $projet->referents->pluck('id')->all());
        $this->assertSame([$c->id], $projet->impliquesHorsReferents()->pluck('id')->all());
        $this->assertCount(3, $projet->personnesImpliquees());
        $this->assertCount(2, $projet->membres);
        $this->assertEqualsCanonicalizing([$projet->id], $a->projets->pluck('id')->all());
    }

    public function test_referents_d_une_tache_de_projet(): void
    {
        [$ref, $resp] = User::factory()->count(2)->create();
        $projet = Projet::factory()->create();
        $projet->membres()->attach($ref->id, ['role' => 'referent']);
        $t = $this->tache([], $projet);
        $t->responsables()->attach($resp->id);

        $this->assertSame([$ref->id], $t->referents()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$ref->id, $resp->id], $t->personnesImpliquees()->pluck('id')->all());
        $this->assertSame([$t->id], $resp->tachesResponsable->pluck('id')->all());
    }

    public function test_tache_simple_le_createur_est_referent(): void
    {
        $createur = User::factory()->create();
        $t = $this->tache(['created_by' => $createur->id]);

        $this->assertTrue($t->estSimple());
        $this->assertSame([$createur->id], $t->referents()->pluck('id')->all());
    }

    public function test_projet_sans_referent_retombe_sur_le_createur_de_la_tache(): void
    {
        $createur = User::factory()->create();
        $t = $this->tache(['created_by' => $createur->id], Projet::factory()->create());

        $this->assertFalse($t->estSimple());
        $this->assertSame([$createur->id], $t->referents()->pluck('id')->all());
    }

    public function test_personnes_impliquees_sans_doublon(): void
    {
        $createur = User::factory()->create();
        $t = $this->tache(['created_by' => $createur->id]);
        $t->responsables()->attach($createur->id);

        $this->assertCount(1, $t->personnesImpliquees());
    }

    // ---- Relations et suppression ----------------------------------------

    public function test_commentaires_et_liens_sur_projet_et_tache(): void
    {
        $user = User::factory()->create();
        $projet = Projet::factory()->create();
        $t = $this->tache([], $projet);

        $projet->commentaires()->create(['user_id' => $user->id, 'contenu' => 'Bien avancé']);
        $t->commentaires()->create(['user_id' => $user->id, 'contenu' => 'À relire']);
        $t->liens()->create(['libelle' => 'Maquette', 'url' => 'https://exemple.test/maquette']);

        $this->assertCount(1, $projet->commentaires);
        $this->assertCount(1, $t->commentaires);
        $this->assertSame('Maquette', $t->liens->first()->libelle);
        $this->assertSame($user->id, Commentaire::first()->user->id);
        $this->assertSame(1, Lien::count());
    }

    public function test_supprimer_un_projet_supprime_ses_taches_et_leurs_dependances(): void
    {
        $user = User::factory()->create();
        $projet = Projet::factory()->create();
        $projet->membres()->attach($user->id, ['role' => 'referent']);
        $t = $this->tache([], $projet);
        $t->responsables()->attach($user->id);

        $projet->delete();

        $this->assertDatabaseCount('taches', 0);
        $this->assertDatabaseCount('tache_user', 0);
        $this->assertDatabaseCount('tache_historiques', 0);
        $this->assertDatabaseCount('projet_user', 0);
    }

    public function test_supprimer_un_utilisateur_garde_les_taches(): void
    {
        $user = User::factory()->create();
        $t = $this->tache(['created_by' => $user->id]);

        $user->delete();

        $this->assertNull($t->fresh()->created_by);
    }

    public function test_recurrence_regroupe_des_taches(): void
    {
        $rec = Recurrence::create(['frequence' => Recurrence::HEBDOMADAIRE, 'date_fin' => '2026-12-31']);
        $t = $this->tache(['recurrence_id' => $rec->id]);

        $this->assertSame($rec->id, $t->recurrence->id);
        $this->assertSame('2026-12-31', $rec->fresh()->date_fin->toDateString());
        $this->assertCount(1, $rec->taches);
    }
}
