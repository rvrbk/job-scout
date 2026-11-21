<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /**
     * List the user's access tokens and the address of the MCP server. A freshly created token is shown once.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/ApiTokens', [
            'tokens' => $request->user()->tokens()->latest()->get()->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at->toIso8601String(),
            ]),
            'mcpUrl' => url('/mcp/ledger'),
            'newToken' => $request->session()->get('newToken'),
        ]);
    }

    /**
     * Create a token for a coding assistant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $token = $request->user()->createToken($validated['name'], ['ledger']);

        return to_route('api-tokens.index')->with('newToken', $token->plainTextToken);
    }

    /**
     * Revoke a token.
     */
    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Token revoked.')]);

        return to_route('api-tokens.index');
    }
}
