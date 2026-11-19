<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        config([
            'services.polar.token' => 'polar_oat_test',
            'services.polar.product_id' => 'prod_1',
            'services.polar.webhook_secret' => 'whsec_'.base64_encode('secret-bytes'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function webhook(array $body, ?string $key = null, ?int $timestamp = null): TestResponse
    {
        $payload = json_encode($body);
        $timestamp ??= time();
        $signature = base64_encode(hash_hmac('sha256', "msg_1.{$timestamp}.{$payload}", $key ?? 'secret-bytes', true));

        return $this->call('POST', route('webhooks.polar'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_ID' => 'msg_1',
            'HTTP_WEBHOOK_TIMESTAMP' => (string) $timestamp,
            'HTTP_WEBHOOK_SIGNATURE' => "v1,{$signature}",
        ], $payload);
    }

    public function test_everything_is_open_while_polar_is_not_configured(): void
    {
        $user = User::factory()->create();

        config(['app.admin_email' => $user->email]);
        $this->actingAs($user)->post(route('jobs.search'))->assertRedirect();
        $this->assertTrue($user->hasAccess());
    }

    public function test_unsubscribed_users_are_sent_to_billing_when_configured(): void
    {
        $this->configure();

        $this->actingAs(User::factory()->create())
            ->post(route('documents.store-general'), ['type' => 'cv'])
            ->assertRedirect(route('billing.index'));

        $this->actingAs(User::factory()->create())
            ->postJson(route('jobs.boards.suggest'))
            ->assertStatus(402);
    }

    public function test_exempt_users_keep_access_when_configured(): void
    {
        $this->configure();

        $this->assertTrue(User::factory()->create(['billing_exempt' => true])->hasAccess());
        $this->assertFalse(User::factory()->create()->hasAccess());
    }

    public function test_checkout_redirects_to_polar(): void
    {
        $this->configure();
        Http::fake(['api.polar.sh/v1/checkouts/' => Http::response(['url' => 'https://polar.sh/checkout/abc'], 201)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('billing.checkout'))->assertRedirect('https://polar.sh/checkout/abc');

        Http::assertSent(fn ($request) => $request['external_customer_id'] === (string) $user->id
            && $request['products'] === ['prod_1']
            && $request->hasHeader('Authorization', 'Bearer polar_oat_test'));
    }

    public function test_webhook_activates_and_revokes_a_subscription(): void
    {
        $this->configure();
        $user = User::factory()->create();
        $event = fn (string $type, string $status) => [
            'type' => $type,
            'data' => [
                'id' => 'sub_1',
                'status' => $status,
                'current_period_end' => '2027-01-01T00:00:00Z',
                'cancel_at_period_end' => false,
                'customer' => ['external_id' => (string) $user->id],
            ],
        ];

        $this->webhook($event('subscription.active', 'active'))->assertNoContent();
        $this->assertTrue($user->fresh()->hasAccess());

        $this->webhook($event('subscription.revoked', 'canceled'))->assertNoContent();
        $this->assertFalse($user->fresh()->hasAccess());
    }

    public function test_webhook_with_bad_signature_or_stale_timestamp_is_rejected(): void
    {
        $this->configure();
        $user = User::factory()->create();
        $body = ['type' => 'subscription.active', 'data' => ['id' => 'sub_1', 'status' => 'active', 'customer' => ['external_id' => (string) $user->id]]];

        $this->webhook($body, 'wrong-key')->assertForbidden();
        $this->webhook($body, null, time() - 3600)->assertForbidden();
        $this->assertNull($user->fresh()->subscription_status);
    }

    public function test_webhook_accepts_legacy_secret_signing(): void
    {
        config(['services.polar.webhook_secret' => 'whsec_legacy']);
        $user = User::factory()->create();

        $this->webhook(['type' => 'subscription.active', 'data' => ['id' => 'sub_2', 'status' => 'active', 'customer' => ['external_id' => (string) $user->id]]], 'whsec_legacy')
            ->assertNoContent();
        $this->assertSame('active', $user->fresh()->subscription_status);
    }
}
