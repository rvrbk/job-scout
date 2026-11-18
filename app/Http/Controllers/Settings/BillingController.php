<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Billing\Polar;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Billing', [
            'enforced' => Polar::enforced(),
            'exempt' => $user->billing_exempt,
            'subscribed' => $user->hasActiveSubscription(),
            'status' => $user->subscription_status,
            'cancels' => $user->subscription_cancels,
            'endsAt' => $user->subscription_ends_at?->toIso8601String(),
            'hasCustomer' => $user->polar_subscription_id !== null,
            'justPaid' => $request->query('checkout') === 'success',
        ]);
    }

    public function checkout(Request $request, Polar $polar): SymfonyResponse|RedirectResponse
    {
        abort_unless(Polar::enforced(), 404);

        if ($request->user()->hasActiveSubscription()) {
            return to_route('billing.index');
        }

        return $this->external(fn () => $polar->checkoutUrl($request->user(), route('billing.index', ['checkout' => 'success'])));
    }

    public function portal(Request $request, Polar $polar): SymfonyResponse|RedirectResponse
    {
        abort_unless(Polar::enforced(), 404);

        return $this->external(fn () => $polar->portalUrl($request->user(), route('billing.index')));
    }

    /**
     * Send the browser to a Polar-hosted page, or back with an error if Polar cannot be reached.
     *
     * @param  callable(): string  $url
     */
    private function external(callable $url): SymfonyResponse|RedirectResponse
    {
        try {
            return Inertia::location($url());
        } catch (RequestException|RuntimeException $exception) {
            Log::error('Polar request failed', ['error' => $exception->getMessage()]);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Could not reach the payment provider. Try again in a moment.')]);

            return back();
        }
    }
}
