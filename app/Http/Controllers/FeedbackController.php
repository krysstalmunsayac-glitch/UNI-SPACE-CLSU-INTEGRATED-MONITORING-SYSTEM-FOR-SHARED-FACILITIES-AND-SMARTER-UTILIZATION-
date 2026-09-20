<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\FacilityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
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
        $facilityRequest = FacilityRequest::withTrashed()->findOrFail($facilityRequest);

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
        $facilityRequest = FacilityRequest::withTrashed()->findOrFail($facilityRequest);

        abort_unless(
            $facilityRequest->User_ID === $request->user()->id
                && $facilityRequest->Facility_ID
                && $facilityRequest->Status === 'Ended',
            403,
        );

        $validated = $request->validate([
            'Rating' => ['required', 'integer', 'between:1,5'],
            'Reservation_Frequency' => ['required', Rule::in(['First time', 'Occasionally (1–3 times per year)', 'Regularly (monthly)', 'Frequently (weekly)'])],
            'Purpose_Importance' => ['required', Rule::in(['Very Important', 'Important', 'Neutral', 'Slightly Important', 'Not Important'])],
            'Requirements_Met' => ['required', Rule::in(['Yes, completely', 'Mostly', 'Partially', 'No'])],
            'Reserve_Again' => ['required', Rule::in(['Definitely Yes', 'Probably Yes', 'Not Sure', 'Probably No', 'Definitely No'])],
            'Comment' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($facilityRequest, $request, $validated): void {
            $lockedRequest = FacilityRequest::withTrashed()->lockForUpdate()->findOrFail($facilityRequest->RID);

            abort_if($lockedRequest->feedback()->exists(), 409, 'Feedback has already been submitted for this request.');

            Feedback::create([
                'User_ID' => $request->user()->id,
                'Request_ID' => $lockedRequest->RID,
                'Facility_ID' => $lockedRequest->Facility_ID,
                'Rating' => $validated['Rating'],
                'Reservation_Frequency' => $validated['Reservation_Frequency'],
                'Purpose_Importance' => $validated['Purpose_Importance'],
                'Requirements_Met' => $validated['Requirements_Met'],
                'Reserve_Again' => $validated['Reserve_Again'],
                'Comment' => filled($validated['Comment'] ?? null) ? trim($validated['Comment']) : null,
            ]);
        }, 3);

        return redirect(route('dashboard').'#requests')
            ->with('success', 'Thank you! Your feedback was submitted successfully.')
            ->with('sweet_alert', [
                'title' => 'Feedback submitted',
                'text' => 'Thank you for sharing your facility rating.',
                'icon' => 'success',
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Feedback $feedbacks)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedback $feedbacks)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feedback $feedbacks)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedbacks)
    {
        //
    }
}
