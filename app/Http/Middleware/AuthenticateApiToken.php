<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API token required'
            ], 401);
        }

        $apiToken = $this->findValidToken($token);

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired API token'
            ], 401);
        }

        // Set the authenticated user
        Auth::setUser($apiToken->user);

        // Mark token as used
        $apiToken->markAsUsed();

        return $next($request);
    }

    private function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');
        
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7); // Remove 'Bearer ' prefix
    }

    private function findValidToken(string $plainTextToken): ?ApiToken
    {
        $tokens = ApiToken::with('user')->get();

        foreach ($tokens as $token) {
            if (Hash::check($plainTextToken, $token->token) && $token->isActive()) {
                return $token;
            }
        }

        return null;
    }
}
