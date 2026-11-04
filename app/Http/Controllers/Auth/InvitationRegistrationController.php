<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationRegistrationController extends Controller
{
    /**
     * Show the registration form for a valid invitation.
     */
    public function show(string $token): Response
    {
        $invitation = $this->usableInvitation($token);

        return Inertia::render('auth/Register', [
            'token' => $invitation->token,
            'inviter' => $invitation->inviter->name,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Register the invited user, consuming the invitation.
     */
    public function store(Request $request, string $token, CreateNewUser $creator): RedirectResponse
    {
        $this->usableInvitation($token);

        $user = DB::transaction(function () use ($request, $token, $creator) {
            // Lock the row so one link cannot create two accounts.
            $invitation = Invitation::usable()->where('token', $token)->lockForUpdate()->first();

            abort_if($invitation === null, 404);

            $user = $creator->create($request->all());

            // There is no mail yet, so the invitation stands in for email verification.
            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->forceFill([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ])->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }

    private function usableInvitation(string $token): Invitation
    {
        return Invitation::usable()->where('token', $token)->with('inviter:id,name')->firstOrFail();
    }
}
