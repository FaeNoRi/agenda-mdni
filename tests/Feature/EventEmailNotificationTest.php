<?php

namespace Tests\Feature;

use App\Models\Evenements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.brevo.api_key' => 'test-key',
            'services.brevo.sender_email' => 'agenda@example.test',
            'services.brevo.sender_name' => 'Agenda',
        ]);

        DB::table('users')->insert([
            'id' => 0, 'name' => "Toute l'équipe", 'email' => 'equipe@example.test', 'password' => 'x',
            'is_admin' => 0, 'is_equipe' => 0, 'id_horaire' => 0, 'is_email' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);
    }

    private function person(string $email): User
    {
        return User::factory()->create(['email' => $email, 'is_email' => 1, 'is_equipe' => 1]);
    }

    private function event(array $userIds, array $overrides = []): Evenements
    {
        $event = Evenements::create(array_merge([
            'nom_event' => 'Point projet', 'type_event' => 'Réunion', 'type_public' => 'Autres',
            'desc_event' => 'x', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'facture' => 'Non', 'devis' => 'Non', 'objet' => 'Non', 'auteur' => 'Test',
            'date_heure_debut' => '2026-09-17 10:30:00', 'date_heure_fin' => '2026-09-17 11:30:00',
        ], $overrides));
        $event->users()->sync($userIds);

        return $event;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nom_event' => 'Point projet', 'commanditaire_event' => 'MDNI', 'nbpart' => 4,
            'type_event' => 'Réunion', 'type_public' => 'Autres', 'devis' => 'Non', 'facture' => 'Non',
            'reglement' => 'Non', 'desc_event' => 'x', 'objet' => 'Non',
            'date_heure_debut' => '2026-09-17T10:30', 'date_heure_fin' => '2026-09-17T11:30',
        ], $overrides);
    }

    /** @return array<string, array{subject:string, ics:string}> indexé par destinataire */
    private function sent(): array
    {
        $out = [];
        foreach (Http::recorded() as [$request]) {
            /** @var Request $request */
            $out[$request['to'][0]['email']] = [
                'subject' => $request['subject'],
                'ics' => base64_decode($request['attachment'][0]['content']),
            ];
        }

        return $out;
    }

    public function test_creation_sends_publish_ics_to_named_attendees(): void
    {
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');

        $this->actingAs($admin)->post('/evenements', $this->payload(['users' => [$anne->id]]))->assertRedirect();

        $sent = $this->sent();
        $this->assertSame(['anne@example.test'], array_keys($sent));
        $this->assertStringStartsWith('Nouvel événement', $sent['anne@example.test']['subject']);
        $this->assertStringContainsString('METHOD:PUBLISH', $sent['anne@example.test']['ics']);
    }

    public function test_update_sends_same_uid_with_updated_subject(): void
    {
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');
        $event = $this->event([$anne->id]);

        $this->actingAs($admin)->put("/evenements/{$event->id}", $this->payload([
            'nom_event' => 'Point projet v2', 'users' => [$anne->id],
        ]))->assertRedirect();

        $mail = $this->sent()['anne@example.test'];
        $this->assertStringStartsWith('Événement modifié', $mail['subject']);
        $this->assertStringContainsString("UID:event-{$event->id}@floppybord", $mail['ics']);
        $this->assertStringContainsString('METHOD:PUBLISH', $mail['ics']);
        $this->assertStringContainsString('SUMMARY:Point projet v2', $mail['ics']);
    }

    public function test_person_removed_from_event_receives_a_cancellation(): void
    {
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');
        $bob = $this->person('bob@example.test');
        $event = $this->event([$anne->id, $bob->id]);

        $this->actingAs($admin)->put("/evenements/{$event->id}", $this->payload(['users' => [$anne->id]]))->assertRedirect();

        $sent = $this->sent();
        $this->assertStringContainsString('METHOD:PUBLISH', $sent['anne@example.test']['ics']);
        $this->assertStringStartsWith('Événement annulé', $sent['bob@example.test']['subject']);
        $this->assertStringContainsString('METHOD:CANCEL', $sent['bob@example.test']['ics']);
        $this->assertStringContainsString("UID:event-{$event->id}@floppybord", $sent['bob@example.test']['ics']);
    }

    public function test_switching_type_to_cancelled_sends_cancellation(): void
    {
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');
        $event = $this->event([$anne->id]);

        $this->actingAs($admin)->put("/evenements/{$event->id}", $this->payload([
            'type_event' => 'Annule', 'users' => [$anne->id],
        ]))->assertRedirect();

        $mail = $this->sent()['anne@example.test'];
        $this->assertStringStartsWith('Événement annulé', $mail['subject']);
        $this->assertStringContainsString('METHOD:CANCEL', $mail['ics']);
    }

    public function test_deleting_an_event_sends_cancellation(): void
    {
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');
        $event = $this->event([$anne->id]);

        $this->actingAs($admin)->delete("/evenements/{$event->id}")->assertRedirect();

        $this->assertDatabaseMissing('evenements', ['id' => $event->id]);
        $mail = $this->sent()['anne@example.test'];
        $this->assertStringStartsWith('Événement annulé', $mail['subject']);
        $this->assertStringContainsString('METHOD:CANCEL', $mail['ics']);
    }

    public function test_whole_team_placeholder_notifies_every_team_member(): void
    {
        $admin = $this->person('admin@example.test');
        $this->person('anne@example.test');
        User::factory()->create(['email' => 'ext@example.test', 'is_email' => 1, 'is_equipe' => 0]);
        $event = $this->event([0]);

        app(\App\Services\SendEventEmailService::class)->send($event);

        $this->assertEqualsCanonicalizing(['admin@example.test', 'anne@example.test'], array_keys($this->sent()));
    }

    public function test_mail_provider_failure_does_not_break_saving(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response('boom', 500)]);
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');
        $event = $this->event([$anne->id]);

        $this->actingAs($admin)->put("/evenements/{$event->id}", $this->payload([
            'nom_event' => 'Renommé', 'users' => [$anne->id],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Renommé', $event->fresh()->nom_event);
    }

    public function test_network_error_does_not_break_saving(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));
        $admin = $this->person('admin@example.test');
        $anne = $this->person('anne@example.test');

        $this->actingAs($admin)->post('/evenements', $this->payload(['users' => [$anne->id]]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('evenements', ['nom_event' => 'Point projet']);
    }
}
