<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * List the user's invitations.
     */
    public function index(Request $request): Response
    {
        $invitations = $request->user()->invitations()
            ->with('acceptedBy:id,name,email')
            ->latest()
            ->get()
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'url' => $invitation->isUsable() ? route('invitations.show', $invitation->token) : null,
                'expires_at' => $invitation->expires_at->toIso8601String(),
                'accepted_at' => $invitation->accepted_at?->toIso8601String(),
                'accepted_by' => $invitation->acceptedBy?->name,
                'expired' => $invitation->accepted_at === null && ! $invitation->isUsable(),
            ]);

        return Inertia::render('settings/Invitations', [
            'invitations' => $invitations,
        ]);
    }

    /**
     * Generate a new invitation link.
     */
    public function store(Request $request): RedirectResponse
    {
        Invitation::generateFor($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation link created.')]);

        return to_route('invitations.index');
    }

    /**
     * Revoke an invitation that has not been used yet.
     */
    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->user_id === $request->user()->id, 404);
        abort_if($invitation->accepted_at !== null, 422, 'This invitation was already used.');

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return to_route('invitations.index');
    }
}
