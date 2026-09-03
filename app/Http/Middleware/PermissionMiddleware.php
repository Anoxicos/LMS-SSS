<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!auth()->check()) {
            abort(403);
        }

        // OR: l'utilisateur doit avoir au moins une permission
        if (!auth()->user()->hasAnyPermission($permissions)) {
            return redirect()->route('no.access');
        }

        return $next($request);
    }
}
