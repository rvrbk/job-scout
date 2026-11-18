<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class VerifiedEmailResponse implements VerifyEmailResponse
{
    /**
     * After verifying their address, send people who still need a subscription straight to checkout.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        if (! $request->user()->hasAccess()) {
            return to_route('billing.index', ['verified' => 1]);
        }

        return redirect()->intended(config('fortify.home').'?verified=1');
    }
}
