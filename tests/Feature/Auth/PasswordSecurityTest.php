<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['password' => Hash::make('ancien-mdp-123')]);
    }

    private function changer(User $user, string $nouveau, ?string $confirmation = null, string $actuel = 'ancien-mdp-123')
    {
        return $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => $actuel,
            'password' => $nouveau,
            'password_confirmation' => $confirmation ?? $nouveau,
        ]);
    }

    // ---- Changement de mot de passe -------------------------------------

    public function test_dix_caracteres_minimum_avec_message_en_francais(): void
    {
        $user = $this->user();

        $this->changer($user, 'court-123')->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->assertSame('Le mot de passe doit contenir au moins 10 caractères.',
            session('errors')->getBag('updatePassword')->first('password'));

        $this->changer($user, 'dix-carac-1')->assertSessionHasNoErrors();   // 11 caractères
        $this->assertTrue(Hash::check('dix-carac-1', $user->fresh()->password));
    }

    public function test_le_nouveau_mot_de_passe_doit_differer_de_l_ancien(): void
    {
        $user = $this->user();

        $this->changer($user, 'ancien-mdp-123')->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->assertStringContainsString('différent', session('errors')->getBag('updatePassword')->first('password'));
    }

    public function test_les_mots_de_passe_existants_plus_courts_restent_valides(): void
    {
        // Personne n'est forcé de changer : seul un nouveau mot de passe est soumis à la règle.
        $court = User::factory()->create(['password' => Hash::make('court')]);

        $this->post('/login', ['email' => $court->email, 'password' => 'court'])->assertRedirect();
        $this->assertAuthenticatedAs($court);
    }

    public function test_les_autres_appareils_sont_deconnectes_apres_le_changement(): void
    {
        $user = $this->user();

        // Session d'un autre appareil, ouverte avec l'ancien mot de passe.
        $autre = $this->withSession(['password_hash_web' => $user->password])->actingAs($user)->get('/dashboard');
        $autre->assertOk();

        $this->changer($user, 'nouveau-mdp-456')->assertSessionHasNoErrors();

        // L'autre appareil présente encore l'empreinte de l'ancien mot de passe : il est renvoyé à la connexion.
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->withSession(['password_hash_web' => 'empreinte-de-l-ancien-mot-de-passe'])
            ->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_la_session_courante_reste_ouverte(): void
    {
        $user = $this->user();

        $this->changer($user, 'nouveau-mdp-456')->assertSessionHasNoErrors();

        $this->actingAs($user->fresh())->get('/profile')->assertOk();
    }

    public function test_tentatives_limitees(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 6; $i++) {
            $this->changer($user, 'nouveau-mdp-456', null, 'faux-'.$i);
        }

        $this->changer($user, 'nouveau-mdp-456')->assertStatus(429);
    }

    // ---- Mot de passe oublié --------------------------------------------

    public function test_message_neutre_que_l_adresse_existe_ou_non(): void
    {
        Notification::fake();
        $user = $this->user();

        $existe = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $existe->assertSessionHas('status', __('passwords.sent_neutral'))->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);

        $inconnue = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'inconnu@example.test']);
        $inconnue->assertSessionHas('status', __('passwords.sent_neutral'))->assertSessionHasNoErrors();
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_demandes_rapprochees_ne_revelent_rien(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email]);
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', __('passwords.sent_neutral'))->assertSessionHasNoErrors();
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_la_reinitialisation_applique_la_longueur_minimale(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $this->post('/reset-password', ['token' => $n->token, 'email' => $user->email, 'password' => 'court-123', 'password_confirmation' => 'court-123'])
                ->assertSessionHasErrors('password');

            $this->post('/reset-password', ['token' => $n->token, 'email' => $user->email, 'password' => 'nouveau-mdp-456', 'password_confirmation' => 'nouveau-mdp-456'])
                ->assertSessionHasNoErrors()->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('nouveau-mdp-456', $user->fresh()->password));
    }

    public function test_la_reinitialisation_deconnecte_les_autres_appareils(): void
    {
        Notification::fake();
        $user = $this->user();
        $ancienneEmpreinte = $user->password;
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $this->post('/reset-password', ['token' => $n->token, 'email' => $user->email, 'password' => 'nouveau-mdp-456', 'password_confirmation' => 'nouveau-mdp-456']);

            return true;
        });

        $this->assertNotSame($ancienneEmpreinte, $user->fresh()->password);

        // Une session ouverte avec l'ancien mot de passe est refusée.
        $this->withSession(['password_hash_web' => $ancienneEmpreinte])->actingAs($user->fresh())->get('/dashboard')->assertRedirect('/login');
    }
}
