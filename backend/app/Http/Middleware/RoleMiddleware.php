<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  string  ...$roles Comma-delimited roles are accepted by routing group usage.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $accepted = collect($roles)
            ->flatMap(fn ($r) => array_map('trim', explode(',', $r)))
            ->filter()
            ->values();

        if ($accepted->isEmpty()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $accepted->contains($user->role->name)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

