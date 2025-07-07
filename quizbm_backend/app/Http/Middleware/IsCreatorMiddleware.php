<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsCreatorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'creator') {
            abort(403, 'Unauthorized. Only creators can access this resource.');
        }
        return $next($request);
    }
} 