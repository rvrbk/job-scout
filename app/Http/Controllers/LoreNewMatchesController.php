<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only count of new matches for one user, for the Lore dashboard. Guarded by a shared bearer token.
 */
class LoreNewMatchesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('services.lore.token');
        abort_if($token === '', 404);
        abort_unless(hash_equals($token, (string) $request->bearerToken()), 401);

        $email = (string) $request->query('email');
        $user = User::whereRaw('lower(email) = ?', [strtolower($email)])->first();
        abort_if($user === null, 404);

        return response()->json([
            'new' => $user->jobPostings()->newMatches($user->jobSearchPreference?->min_score ?? 60)->count(),
            'url' => route('jobs.index'),
        ]);
    }
}
