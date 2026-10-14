<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PagesAuthentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_connexion_utilise_la_carte_du_site_en_francais(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('auth-carte', $html);
        $this->assertStringContainsString('application-logo.svg', $html);
        foreach (['Connexion', 'Adresse e-mail', 'Mot de passe', 'Rester connecté', 'Mot de passe oublié ?', 'Se connecter'] as $texte) {
            $this->assertStringContainsString($texte, $html, $texte);
        }
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('data-auth-oeil="password"', $html, 'afficher / masquer le mot de passe');
    }

    public function test_la_page_mot_de_passe_oublie_est_dans_le_meme_style(): void
    {
        $html = $this->get('/forgot-password')->assertOk()->getContent();

        $this->assertStringContainsString('auth-carte', $html);
        $this->assertStringContainsString('Envoyer le lien de réinitialisation', $html);
        $this->assertStringContainsString('Retour à la connexion', $html);
    }

    public function test_la_page_de_reinitialisation_est_dans_le_meme_style(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $html = $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk()->getContent();

        $this->assertStringContainsString('auth-carte', $html);
        $this->assertStringContainsString('name="token"', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
        $this->assertStringContainsString('Enregistrer le mot de passe', $html);
    }

    public function test_une_erreur_de_connexion_s_affiche_sous_le_champ(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'faux-mot-de-passe'])
            ->assertRedirect('/login');

        $this->get('/login')->assertSee('auth-erreur', false);
    }

    public function test_la_page_floppy_la_garde_son_propre_gabarit(): void
    {
        $html = $this->get('/presence')->getContent();

        $this->assertStringNotContainsString('auth-carte', $html);
    }
}
