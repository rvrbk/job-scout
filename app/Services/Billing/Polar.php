<?php

namespace App\Services\Billing;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Polar
{
    /**
     * Subscriptions are only required once Polar is fully configured, so deploying this code never locks anyone out.
     */
    public static function enforced(): bool
    {
        return filled(config('services.polar.token')) && filled(config('services.polar.product_id'));
    }

    /**
     * Start a checkout for the subscription product and return the hosted checkout URL.
     */
    public function checkoutUrl(User $user, string $successUrl): string
    {
        $response = $this->client()->post('/v1/checkouts/', [
            'products' => [config('services.polar.product_id')],
            'external_customer_id' => (string) $user->id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'success_url' => $successUrl,
        ]);

        return $this->field($response->throw()->json(), 'url');
    }

    /**
     * A short-lived link to Polar's customer portal, where the user manages or cancels the subscription.
     */
    public function portalUrl(User $user, string $returnUrl): string
    {
        $response = $this->client()->post('/v1/customer-sessions/', [
            'external_customer_id' => (string) $user->id,
            'return_url' => $returnUrl,
        ]);

        return $this->field($response->throw()->json(), 'customer_portal_url');
    }

    /**
     * Check a webhook delivery against the Standard Webhooks signature scheme.
     *
     * Polar secrets created before 8 September 2026 sign with the UTF-8 bytes of the whole "whsec_…" string;
     * newer ones sign with the base64-decoded part after the prefix. Both are accepted.
     */
    public function verifyWebhook(string $payload, ?string $id, ?string $timestamp, ?string $signatures): bool
    {
        $secret = (string) config('services.polar.webhook_secret');

        if ($secret === '' || ! $id || ! $timestamp || ! $signatures || ! ctype_digit($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $keys = [$secret];

        if (str_starts_with($secret, 'whsec_') && ($decoded = base64_decode(substr($secret, 6), true)) !== false) {
            $keys[] = $decoded;
        }

        $signed = "{$id}.{$timestamp}.{$payload}";

        foreach (explode(' ', $signatures) as $candidate) {
            [$version, $signature] = array_pad(explode(',', $candidate, 2), 2, '');

            if ($version !== 'v1') {
                continue;
            }

            foreach ($keys as $key) {
                if (hash_equals(base64_encode(hash_hmac('sha256', $signed, $key, true)), $signature)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function client(): PendingRequest
    {
        $token = config('services.polar.token');

        if (blank($token)) {
            throw new RuntimeException('Polar is not configured.');
        }

        $base = config('services.polar.server') === 'sandbox'
            ? 'https://sandbox-api.polar.sh'
            : 'https://api.polar.sh';

        return Http::baseUrl($base)->withToken($token)->acceptJson()->asJson()->timeout(20);
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function field(?array $json, string $key): string
    {
        $value = $json[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new RuntimeException("Polar response is missing \"{$key}\".");
        }

        return $value;
    }
}
