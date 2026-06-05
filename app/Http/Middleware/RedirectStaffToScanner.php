<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectStaffToScanner
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role->value === 'staff') {
            // Staff only allowed on scanner routes + logout
            if (!$request->routeIs('book-scanner*') && !$request->routeIs('logout')) {
                return redirect()->route('book-scanner.index');
            }
        }

        return $next($request);
    }
}
