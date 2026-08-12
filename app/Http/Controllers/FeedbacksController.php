<?php

namespace App\Http\Controllers;

use App\Models\Feedbacks;
use App\Models\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
    public function create(Request $request, int $facilityRequest): View|RedirectResponse
    {
        $facilityRequest = Requests::withTrashed()->findOrFail($facilityRequest);

        abort_unless(
            $facilityRequest->User_ID === $request->user()->id
                && $facilityRequest->Facility_ID
                && $facilityRequest->Status === 'Ended',
            403,
        );

        if ($facilityRequest->feedback()->exists()) {
            return redirect(route('dashboard').'#requests')
                ->with('success', 'You already submitted feedback for this request.');
        }

        $facilityRequest->load('facility:FID,Facility_Name');

        return view('feedback.create', compact('facilityRequest'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, int $facilityRequest): RedirectResponse
    {
        $facilityRequest = Requests::withTrashed()->findOrFail($facilityRequest);

        abort_unless(
            $facilityRequest->User_ID === $request->user()->id
                && $facilityRequest->Facility_ID
                && $facilityRequest->Status === 'Ended',
            403,
        );

        abort_if($facilityRequest->feedback()->exists(), 409, 'Feedback has already been submitted for this request.');

        $validated = $request->validate([
            'Rating' => ['required', 'integer', 'between:1,5'],
            'Comment' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        Feedbacks::create([
            'User_ID' => $request->user()->id,
            'Request_ID' => $facilityRequest->RID,
            'Facility_ID' => $facilityRequest->Facility_ID,
            'Rating' => $validated['Rating'],
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
