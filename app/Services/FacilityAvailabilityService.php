<?php

namespace App\Services;

use App\Models\Facilities;
use App\Models\Requests;
use App\Notifications\FacilityUnavailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FacilityAvailabilityService
{
    /**
     * Toggle a facility between Available and Unavailable.
     *
     * @return int Number of active requests cancelled
     */
    public function toggle(Facilities $facility): int
    {
        if ($facility->Status === 'Unavailable') {
            $facility->update(['Status' => 'Available']);

            return 0;
        }

        $cancelledRequests = DB::transaction(function () use ($facility) {
            $facility->update(['Status' => 'Unavailable']);

            $requests = Requests::query()
                ->with(['user', 'facility', 'event', 'schedule'])
                ->where('Facility_ID', $facility->FID)
                ->whereIn('Status', ['Pending', 'Approved'])
                ->lockForUpdate()
                ->get();

            foreach ($requests as $request) {
                $request->update([
                    'Status' => 'Cancelled',
                    'Cancellation_Reason' => 'The facility has been marked unavailable by the facility administrator.',
                ]);

                if ($request->schedule) {
                    $request->schedule->delete();
                }

                if ($request->event && ! in_array($request->event->Status, ['Completed', 'Cancelled'], true)) {
                    $request->event->update(['Status' => 'Cancelled']);
                }
            }

            return $requests;
        });

        foreach ($cancelledRequests as $request) {
            if ($request->user) {
                Notification::send($request->user, new FacilityUnavailable($request));
            }
        }

        return $cancelledRequests->count();
    }
}
