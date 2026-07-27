<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->user_type;
        $normalizedRoles = array_map(
            fn (string $role): string => $role === 'office_admin' ? 'admin' : $role,
            $roles
        );

        if (! in_array($userRole, $normalizedRoles, true)) {
            abort(403, 'Unauthorized. Your role does not have access to this resource.');
        }

        return $next($request);
    }
}
