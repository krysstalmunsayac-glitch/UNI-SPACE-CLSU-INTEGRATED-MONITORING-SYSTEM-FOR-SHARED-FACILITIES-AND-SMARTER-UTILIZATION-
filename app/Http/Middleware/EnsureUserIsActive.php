<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (! Auth::user()->is_active || ! Auth::user()->email_verified_at)) {
            $message = Auth::user()->email_verified_at
                ? 'Your account has been deactivated. Contact an administrator for assistance.'
                : 'Your email invitation must be completed before you can sign in. Ask an administrator to resend it if needed.';

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                $message,
            );
        }

        return $next($request);
    }
}
