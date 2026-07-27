<?php

namespace App\Http\Controllers;

use App\Models\Feedbacks;
use App\Models\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class FeedbacksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Requests $facilityRequest): RedirectResponse
    {
        abort_unless(
            $facilityRequest->User_ID === $request->user()->id && $facilityRequest->Facility_ID,
            403,
        );

        $validated = $request->validate([
            'Comment' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        Feedbacks::create([
            'User_ID' => $request->user()->id,
            'Facility_ID' => $facilityRequest->Facility_ID,
            'Comment' => $validated['Comment'],
        ]);

        return redirect(route('dashboard').'#requests')
            ->with('success', 'Thank you! Your feedback was submitted.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Feedbacks $feedbacks)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedbacks $feedbacks)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feedbacks $feedbacks)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedbacks $feedbacks)
    {
        //
    }
}
