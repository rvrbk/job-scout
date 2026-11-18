<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Billing\Polar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class PolarWebhookController extends Controller
{
    public function __invoke(Request $request, Polar $polar): Response
    {
        $valid = $polar->verifyWebhook(
            $request->getContent(),
            $request->header('webhook-id'),
            $request->header('webhook-timestamp'),
            $request->header('webhook-signature'),
        );

        abort_unless($valid, 403);

        $type = (string) $request->json('type');
        $data = $request->json('data');

        if (str_starts_with($type, 'subscription.') && is_array($data)) {
            $this->syncSubscription($data);
        }

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSubscription(array $data): void
    {
        $subscriptionId = $data['id'] ?? null;
        $externalId = data_get($data, 'customer.external_id');

        $user = is_scalar($externalId) ? User::find($externalId) : null;
        $user ??= is_string($subscriptionId) ? User::where('polar_subscription_id', $subscriptionId)->first() : null;

        if ($user === null || ! is_string($subscriptionId)) {
            return;
        }

        // A user who resubscribed must not be switched back by a late event about their old subscription.
        if ($user->polar_subscription_id !== null && $user->polar_subscription_id !== $subscriptionId && $user->hasActiveSubscription()) {
            return;
        }

        $end = $data['ends_at'] ?? $data['current_period_end'] ?? null;

        $user->forceFill([
            'polar_subscription_id' => $subscriptionId,
            'subscription_status' => $data['status'] ?? null,
            'subscription_ends_at' => $end ? Carbon::parse($end) : null,
            'subscription_cancels' => (bool) ($data['cancel_at_period_end'] ?? false),
        ])->save();
    }
}
