<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->unreadNotifications->isNotEmpty()) {
            $user->unreadNotifications->markAsRead();
        }

        return match ($user->user_type) {
            'user' => redirect()->to(route('dashboard').'#requests'),
            'admin', 'super_admin' => redirect()->route('Request'),
            default => redirect()->route('dashboard'),
        };
    }

    public function markAllAsRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }
}
