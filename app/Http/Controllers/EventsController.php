<?php

namespace App\Http\Controllers;

use App\Models\Events;
use App\Models\User;
use App\Notifications\NewEventCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EventsController extends Controller
{
    public function create()
    {
        return view('Events.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Event_Title' => ['required', 'string', 'max:255'],
            'Description' => ['nullable', 'string'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
        ]);

        $event = Events::create([
            'Event_Title' => $validated['Event_Title'],
            'Description' => $validated['Description'] ?? null,
            'Type_Event' => $validated['Type_Event'] ?? null,
            'User_ID' => auth()->id(),
        ]);

        Notification::send(
            User::whereIn('user_type', ['admin', 'super_admin'])->get(),
            new NewEventCreated($event)
        );

        return redirect()
            ->route('events.create')
            ->with('success', 'Your event has been created successfully.');
    }
}
