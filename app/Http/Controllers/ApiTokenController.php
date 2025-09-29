<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = Auth::user()->apiTokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'display_token' => $token->display_token,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'is_expired' => $token->isExpired(),
                    'is_active' => $token->isActive(),
                    'created_at' => $token->created_at,
                ];
            });

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $plainTextToken = ApiToken::generateToken();
        $hashedToken = Hash::make($plainTextToken);
        $displayToken = ApiToken::createDisplayToken($plainTextToken);

        $token = Auth::user()->apiTokens()->create([
            'name' => $request->name,
            'token' => $hashedToken,
            'display_token' => $displayToken,
            'abilities' => $request->abilities ?? ['*'], // Default to all abilities
            'expires_at' => $request->expires_at,
        ]);

        // Return to the same page with the new token data
        $tokens = Auth::user()->apiTokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'display_token' => $token->display_token,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'is_expired' => $token->isExpired(),
                    'is_active' => $token->isActive(),
                    'created_at' => $token->created_at,
                ];
            });

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
            'newToken' => [
                'plaintext' => $plainTextToken,
                'name' => $token->name,
            ],
            'flash' => [
                'success' => 'API token created successfully',
            ],
        ]);
    }

    public function update(Request $request, ApiToken $apiToken)
    {
        // Ensure token belongs to authenticated user
        if ($apiToken->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $apiToken->update([
            'name' => $request->name,
            'abilities' => $request->abilities ?? $apiToken->abilities,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('settings.api-tokens')
            ->with('success', 'API token updated successfully');
    }

    public function destroy(ApiToken $apiToken)
    {
        // Ensure token belongs to authenticated user
        if ($apiToken->user_id !== Auth::id()) {
            abort(403);
        }

        $apiToken->delete();

        return redirect()->route('settings.api-tokens')
            ->with('success', 'API token revoked successfully');
    }

    public function regenerate(ApiToken $apiToken)
    {
        // Ensure token belongs to authenticated user
        if ($apiToken->user_id !== Auth::id()) {
            abort(403);
        }

        $plainTextToken = ApiToken::generateToken();
        $hashedToken = Hash::make($plainTextToken);
        $displayToken = ApiToken::createDisplayToken($plainTextToken);

        $apiToken->update([
            'token' => $hashedToken,
            'display_token' => $displayToken,
            'last_used_at' => null,
        ]);

        // Return to the same page with the regenerated token data
        $tokens = Auth::user()->apiTokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'display_token' => $token->display_token,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'is_expired' => $token->isExpired(),
                    'is_active' => $token->isActive(),
                    'created_at' => $token->created_at,
                ];
            });

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
            'newToken' => [
                'plaintext' => $plainTextToken,
                'name' => $apiToken->name,
            ],
            'flash' => [
                'success' => 'API token regenerated successfully',
            ],
        ]);
    }
}
