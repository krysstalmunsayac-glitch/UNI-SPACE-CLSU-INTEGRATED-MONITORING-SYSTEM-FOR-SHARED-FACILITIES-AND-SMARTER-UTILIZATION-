<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenities;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\User;
use App\Services\AdminReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(private readonly AdminReportExporter $exporter) {}

    public function facilitiesCsv(Request $request): StreamedResponse
    {
        $facilities = $this->facilityQuery($request)->orderBy('Facility_Name')->get();

        return response()->streamDownload(function () use ($facilities) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Facility ID', 'Facility Name', 'Type', 'Price (PHP)', 'Capacity', 'Location', 'Office', 'Status']);

            foreach ($facilities as $facility) {
                fputcsv($output, [
                    $facility->FID,
                    $facility->Facility_Name,
                    $facility->facility_type,
                    $facility->Price,
                    $facility->Capacity,
                    $facility->Location,
                    $facility->Office,
                    $facility->Status,
                ]);
            }

            fclose($output);
        }, 'facilities-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function facilitiesPdf(Request $request)
    {
        $facilities = $this->facilityQuery($request)->orderBy('Facility_Name')->get();
        $content = $this->exporter->facilitiesPdf($facilities, $this->scopeLabel($request));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facilities-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function facilitiesXlsx(Request $request)
    {
        $facilities = $this->facilityQuery($request)->orderBy('Facility_Name')->get();
        $content = $this->exporter->facilitiesXlsx($facilities);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="facilities-'.now()->format('Y-m-d').'.xlsx"',
        ]);
    }

    public function requestsCsv(Request $request): StreamedResponse
    {
        $requests = $this->requestQuery($request)->latest('Created_at')->get();

        return response()->streamDownload(function () use ($requests) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Request ID', 'Requester', 'Email', 'Facility', 'First Day', 'Last Day', 'Start Time', 'End Time', 'Attendees', 'Status', 'Purpose']);

            foreach ($requests as $facilityRequest) {
                fputcsv($output, [
                    $facilityRequest->RID,
                    $facilityRequest->user?->name,
                    $facilityRequest->user?->email,
                    $facilityRequest->facility?->Facility_Name,
                    $facilityRequest->Proposed_Date
                        ? '="'.$facilityRequest->Proposed_Date->format('M d, Y').'"'
                        : '',
                    ($facilityRequest->Proposed_End_Date ?? $facilityRequest->Proposed_Date)
                        ? '="'.($facilityRequest->Proposed_End_Date ?? $facilityRequest->Proposed_Date)->format('M d, Y').'"'
                        : '',
                    $facilityRequest->Proposed_Start_Time?->format('H:i'),
                    $facilityRequest->Proposed_End_Time?->format('H:i'),
                    $facilityRequest->Capacity,
                    $facilityRequest->Review_Requested_At && $facilityRequest->Status === 'Pending' ? 'Needs Revision' : $facilityRequest->Status,
                    $facilityRequest->Purpose,
                ]);
            }

            fclose($output);
        }, 'facility-requests-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function requestsPdf(Request $request)
    {
        $requests = $this->requestQuery($request)->latest('Created_at')->get();
        $content = $this->exporter->requestsPdf($requests, $this->scopeLabel($request));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facility-requests-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function requestsXlsx(Request $request)
    {
        $requests = $this->requestQuery($request)->latest('Created_at')->get();
        $content = $this->exporter->requestsXlsx($requests);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="facility-requests-'.now()->format('Y-m-d').'.xlsx"',
        ]);
    }

    public function usersCsv(): StreamedResponse
    {
        $users = User::query()->orderBy('name')->get();

        return response()->streamDownload(function () use ($users) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['User ID', 'Name', 'Email', 'Role', 'Contact Number', 'Office', 'Status', 'Email Verified']);

            foreach ($users as $user) {
                fputcsv($output, [$user->id, $user->name, $user->email, $user->roleLabel(), $user->contact_number, $user->office, $user->is_active ? 'Active' : 'Inactive', $user->email_verified_at ? 'Yes' : 'No']);
            }

            fclose($output);
        }, 'users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function usersPdf()
    {
        $content = $this->exporter->usersPdf(User::query()->orderBy('name')->get());

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="users-'.now()->format('Y-m-d').'.pdf"']);
    }

    public function usersXlsx()
    {
        $content = $this->exporter->usersXlsx(User::query()->orderBy('name')->get());

        return response($content, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="users-'.now()->format('Y-m-d').'.xlsx"']);
    }

    public function amenitiesCsv(): StreamedResponse
    {
        $amenities = $this->amenityQuery()->get();

        return response()->streamDownload(function () use ($amenities) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Amenity ID', 'Name', 'Description', 'Status', 'Reservation Limit', 'Facilities', 'Created By']);

            foreach ($amenities as $amenity) {
                fputcsv($output, [$amenity->AID, $amenity->name, $amenity->Description, $amenity->Status, $amenity->reservation_limit ?? 'Unlimited', $amenity->facilities->pluck('Facility_Name')->join(', '), $amenity->creator?->name]);
            }

            fclose($output);
        }, 'amenities-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function amenitiesPdf()
    {
        $content = $this->exporter->amenitiesPdf($this->amenityQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="amenities-'.now()->format('Y-m-d').'.pdf"']);
    }

    public function amenitiesXlsx()
    {
        $content = $this->exporter->amenitiesXlsx($this->amenityQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="amenities-'.now()->format('Y-m-d').'.xlsx"']);
    }

    private function amenityQuery(): Builder
    {
        return Amenities::query()->with(['facilities:FID,Facility_Name', 'creator:id,name'])->orderBy('name');
    }

    private function facilityQuery(Request $request): Builder
    {
        return Facilities::query()
            ->when($request->user()->isAdmin(), fn (Builder $query) => $query->assignedToAdmin($request->user()));
    }

    private function requestQuery(Request $request): Builder
    {
        return Requests::query()
            ->with(['user', 'facility'])
            ->when($request->user()->isAdmin(), fn (Builder $query) => $query
                ->whereHas('facility.assignedAdmins', fn (Builder $adminQuery) => $adminQuery
                    ->where('users.id', $request->user()->id)));
    }

    private function scopeLabel(Request $request): string
    {
        return $request->user()->isAdmin()
            ? 'Assigned facilities only'
            : 'All facilities';
    }
}
