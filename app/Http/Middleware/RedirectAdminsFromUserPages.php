<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminsFromUserPages
{
    /**
     * Keep management accounts out of public and reservation-only pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        return match ($user->user_type) {
            'super_admin' => redirect()->route('dashboard.superadmin'),
            'admin' => redirect()->route('dashboard.officeadmin'),
            default => $next($request),
        };
    }
}
