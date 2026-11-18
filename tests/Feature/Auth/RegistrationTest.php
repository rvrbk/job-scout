<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function details(): array
    {
        return [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'a-Strong-pass-123',
            'password_confirmation' => 'a-Strong-pass-123',
        ];
    }

    public function test_registration_page_is_available()
    {
        $this->get('/register')->assertOk();
    }

    public function test_anyone_can_sign_up_and_must_verify_their_email()
    {
        Notification::fake();

        $this->post('/register', $this->details())->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $user = User::where('email', 'new@example.com')->sole();
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->billing_exempt);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_verifying_sends_people_who_must_pay_to_billing()
    {
        config(['services.polar.token' => 'token', 'services.polar.product_id' => 'product']);
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('billing.index', ['verified' => 1]));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
