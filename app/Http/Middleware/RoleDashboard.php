<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleDashboard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $role = Auth::user()->user_type;

        return match ($role) {
            'super_admin' => redirect()->route('dashboard.superadmin'),
            'admin' => redirect()->route('dashboard.officeadmin'),
            default => $next($request),
        };
    }
}
