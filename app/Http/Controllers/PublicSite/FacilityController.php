<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\FacilityAvailabilityService;
use Illuminate\Contracts\View\View;

class FacilityController extends Controller
{
    public function show(Facility $facility, FacilityAvailabilityService $availability): View
    {
        $availability->reactivateExpired();
        $facility->refresh();

        $facility->load([
            'images',
            'assignedAdmins' => fn ($query) => $query
                ->where('users.user_type', 'admin')
                ->select('users.id', 'users.name', 'users.email'),
            'amenities' => fn ($query) => $query
                ->where('amenities.Status', 'Available')
                ->orderBy('amenities.name'),
        ]);

        return view('pages.facilities.show', compact('facility'));
    }
}
