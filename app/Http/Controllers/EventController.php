<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Notifications\NewEventCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EventController extends Controller
{
    public function create()
    {
        return view('Event.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Event_Title' => ['required', 'string', 'max:255'],
            'Description' => ['nullable', 'string'],
            'Type_Event' => ['nullable', 'string', 'max:100'],
            'Event_Scope' => ['required', 'in:Internal,External'],
        ]);

        $event = Event::create([
            'Event_Title' => $validated['Event_Title'],
            'Description' => $validated['Description'] ?? null,
            'Type_Event' => $validated['Type_Event'] ?? null,
            'Event_Scope' => $validated['Event_Scope'],
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
