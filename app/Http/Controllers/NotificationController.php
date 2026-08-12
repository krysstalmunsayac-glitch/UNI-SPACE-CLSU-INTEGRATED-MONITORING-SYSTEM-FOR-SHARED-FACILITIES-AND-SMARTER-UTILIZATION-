<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $data = $notification->data;
        if (! empty($data['action_url'])) {
            return redirect()->to($data['action_url']);
        }

        $requestId = isset($data['request_id']) ? (int) $data['request_id'] : null;

        return match ($request->user()->user_type) {
            'user' => redirect()->to(
                route('dashboard', array_filter(['request' => $requestId]))
                .($requestId ? '#request-'.$requestId : '#requests')
            ),
            'admin', 'super_admin' => redirect()->route('Request', array_filter(['request' => $requestId])),
            default => redirect()->route('dashboard'),
        };
    }

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

    public function recent(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? 'You have a new notification.',
                'facility' => $notification->data['facility'] ?? null,
                'reason' => $notification->data['rejection_reason'] ?? null,
                'actionUrl' => $notification->data['action_url'] ?? null,
                'time' => $notification->created_at->diffForHumans(),
                'unread' => is_null($notification->read_at),
            ])
            ->values();

        return response()->json(['items' => $notifications]);
    }
}
