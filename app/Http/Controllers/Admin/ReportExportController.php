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
            fputcsv($output, $this->exporter->facilityHeaders());

            foreach ($facilities as $facility) {
                fputcsv($output, $this->exporter->facilityRow($facility));
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
            fputcsv($output, $this->exporter->requestHeaders());

            foreach ($requests as $facilityRequest) {
                fputcsv($output, $this->exporter->requestRow($facilityRequest));
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
        $users = $this->userQuery()->get();

        return response()->streamDownload(function () use ($users) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $this->exporter->userHeaders());

            foreach ($users as $user) {
                fputcsv($output, $this->exporter->userRow($user));
            }

            fclose($output);
        }, 'users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function usersPdf()
    {
        $content = $this->exporter->usersPdf($this->userQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="users-'.now()->format('Y-m-d').'.pdf"']);
    }

    public function usersXlsx()
    {
        $content = $this->exporter->usersXlsx($this->userQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="users-'.now()->format('Y-m-d').'.xlsx"']);
    }

    public function amenitiesCsv(): StreamedResponse
    {
        $amenities = $this->amenityQuery()->get();

        return response()->streamDownload(function () use ($amenities) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $this->exporter->amenityHeaders());

            foreach ($amenities as $amenity) {
                fputcsv($output, $this->exporter->amenityRow($amenity));
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
        return Amenities::query()
            ->with(['facilities:FID,Facility_Name', 'creator:id,name'])
            ->withCount('requests')
            ->orderBy('name');
    }

    private function facilityQuery(Request $request): Builder
    {
        return Facilities::query()
            ->with('amenities:AID,name')
            ->when($request->user()->isAdmin(), fn (Builder $query) => $query->assignedToAdmin($request->user()));
    }

    private function requestQuery(Request $request): Builder
    {
        return Requests::query()
            ->with(['user', 'creator', 'facility', 'event', 'amenities:AID,name'])
            ->when($request->user()->isAdmin(), fn (Builder $query) => $query
                ->whereHas('facility.assignedAdmins', fn (Builder $adminQuery) => $adminQuery
                    ->where('users.id', $request->user()->id)));
    }

    private function userQuery(): Builder
    {
        return User::query()
            ->with('facilities:FID,Facility_Name')
            ->orderBy('name');
    }

    private function scopeLabel(Request $request): string
    {
        return $request->user()->isAdmin()
            ? 'Assigned facilities only'
            : 'All facilities';
    }
}
