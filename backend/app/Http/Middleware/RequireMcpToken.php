<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RequireMcpToken
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $token = $request->user()?->currentAccessToken();

        if (
            ! $request->bearerToken()
            || ! ($token instanceof PersonalAccessToken)
            || ! $token->can('mcp:read')
        ) {
            abort(403, 'MCP access denied.');
        }

        return $next($request);
    }
}
