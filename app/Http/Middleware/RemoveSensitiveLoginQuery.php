<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveSensitiveLoginQuery
{
    /**
     * Remove credentials from the URL before rendering the login page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query->has('password')) {
            $email = $request->query('email');
            $query = is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)
                ? ['email' => $email]
                : [];

            return redirect()->route('login', $query);
        }

        return $next($request);
    }
}
