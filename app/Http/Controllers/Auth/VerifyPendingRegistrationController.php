<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class VerifyPendingRegistrationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $registration = json_decode(
                Crypt::decryptString($request->string('payload')->toString()),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (DecryptException|\JsonException) {
            abort(403, 'The registration verification link is invalid.');
        }

        if (! is_array($registration) || ! isset(
            $registration['name'],
            $registration['email'],
            $registration['password'],
            $registration['contact_number'],
            $registration['address'],
        )) {
            abort(403, 'The registration verification link is invalid.');
        }

        if (User::query()->where('email', $registration['email'])->exists()) {
            return redirect()->route('login')->with(
                'status',
                'An account with this email address already exists. You can sign in now.',
            );
        }

        $user = DB::transaction(function () use ($registration): User {
            $user = User::query()->create([
                'name' => $registration['name'],
                'email' => $registration['email'],
                'password' => $registration['password'],
                'contact_number' => $registration['contact_number'],
                'address' => $registration['address'],
                'email_verified_at' => now(),
            ]);

            event(new Registered($user));
            event(new Verified($user));

            return $user;
        });

        Auth::login($user);

        return redirect()->route('dashboard')->with(
            'status',
            'Your email has been verified and your account was created successfully.',
        );
    }
}
