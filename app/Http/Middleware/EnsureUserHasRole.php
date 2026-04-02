<?php

namespace App\Http\Middleware;

use Closure;
use BackedEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $roleValue = $user->role instanceof BackedEnum ? $user->role->value : (string) $user->role;

        if (! in_array($roleValue, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
