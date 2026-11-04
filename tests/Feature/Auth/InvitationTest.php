<?php

namespace Tests\Feature\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function details(array $overrides = []): array
    {
        return [
            'name' => 'Invited User',
            'email' => 'invited@example.com',
            'password' => 'a-Strong-pass-123',
            'password_confirmation' => 'a-Strong-pass-123',
            ...$overrides,
        ];
    }

    public function test_open_registration_is_not_available()
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->details())->assertNotFound();
    }

    public function test_user_can_create_and_revoke_invitations()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('invitations.index'))->assertOk();
        $this->actingAs($user)->post(route('invitations.store'))->assertRedirect(route('invitations.index'));

        $invitation = $user->invitations()->sole();
        $this->assertTrue($invitation->isUsable());

        $this->actingAs($user)->delete(route('invitations.destroy', $invitation))->assertRedirect();
        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_guests_cannot_create_invitations()
    {
        $this->post(route('invitations.store'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_revoke_someone_elses_invitation()
    {
        $invitation = Invitation::generateFor(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete(route('invitations.destroy', $invitation))
            ->assertNotFound();
    }

    public function test_invited_person_can_register_with_a_password()
    {
        $invitation = Invitation::generateFor(User::factory()->create());

        $this->get(route('invitations.show', $invitation->token))->assertOk();

        $this->post(route('invitations.register', $invitation->token), $this->details())
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $user = User::where('email', 'invited@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame($user->id, $invitation->fresh()->accepted_by);
    }

    public function test_invitation_can_only_be_used_once()
    {
        $invitation = Invitation::generateFor(User::factory()->create());

        $this->post(route('invitations.register', $invitation->token), $this->details());
        auth()->logout();

        $this->get(route('invitations.show', $invitation->token))->assertNotFound();
        $this->post(route('invitations.register', $invitation->token), $this->details(['email' => 'other@example.com']))
            ->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'other@example.com']);
    }

    public function test_expired_and_unknown_invitations_are_rejected()
    {
        $invitation = Invitation::generateFor(User::factory()->create());
        $invitation->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->get(route('invitations.show', $invitation->token))->assertNotFound();
        $this->get(route('invitations.show', 'nope'))->assertNotFound();
    }

    public function test_invalid_details_do_not_consume_the_invitation()
    {
        $invitation = Invitation::generateFor(User::factory()->create());

        $this->post(route('invitations.register', $invitation->token), $this->details(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertTrue($invitation->fresh()->isUsable());
    }
}
