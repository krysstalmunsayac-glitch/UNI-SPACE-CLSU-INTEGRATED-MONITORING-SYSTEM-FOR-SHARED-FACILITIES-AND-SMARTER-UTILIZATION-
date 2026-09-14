<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Facilities;
use App\Services\FacilityAvailabilityService;
use Illuminate\Contracts\View\View;

class FacilityController extends Controller
{
    public function show(Facilities $facility, FacilityAvailabilityService $availability): View
    {
        $availability->reactivateExpired();
        $facility->refresh();

        $facility->load([
            'images',
            'amenities' => fn ($query) => $query
                ->where('amenities.Status', 'Available')
                ->orderBy('amenities.name'),
        ]);

        return view('pages.facilities.show', compact('facility'));
    }
}
