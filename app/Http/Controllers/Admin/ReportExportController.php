<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use App\Services\Reports\CsvReportExporter;
use App\Services\Reports\PdfReportExporter;
use App\Services\Reports\XlsxReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly CsvReportExporter $csv,
        private readonly XlsxReportExporter $xlsx,
        private readonly PdfReportExporter $pdf,
    ) {}

    public function facilitiesCsv(Request $request): StreamedResponse
    {
        $facilities = $this->facilityQuery($request)->orderBy('Facility_Name')->get();

        return response()->streamDownload(function () use ($facilities) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $this->csv->facilityHeaders());

            foreach ($facilities as $facility) {
                fputcsv($output, $this->csv->facilityRow($facility));
            }

            fclose($output);
        }, 'facilities-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function facilitiesPdf(Request $request)
    {
        $facilities = $this->facilityQuery($request)->orderBy('Facility_Name')->get();
        $content = $this->pdf->facilities($facilities, $this->scopeLabel($request));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facilities-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function facilitiesXlsx(Request $request)
    {
        $facilities = $this->facilityQuery($request)->orderBy('Facility_Name')->get();
        $content = $this->xlsx->facilities($facilities);

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
            fputcsv($output, $this->csv->requestHeaders());

            foreach ($requests as $facilityRequest) {
                fputcsv($output, $this->csv->requestRow($facilityRequest));
            }

            fclose($output);
        }, 'facility-requests-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function requestsPdf(Request $request)
    {
        $requests = $this->requestQuery($request)->latest('Created_at')->get();
        $content = $this->pdf->requests($requests, $this->scopeLabel($request));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facility-requests-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function requestsXlsx(Request $request)
    {
        $requests = $this->requestQuery($request)->latest('Created_at')->get();
        $content = $this->xlsx->requests($requests);

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
            fputcsv($output, $this->csv->userHeaders());

            foreach ($users as $user) {
                fputcsv($output, $this->csv->userRow($user));
            }

            fclose($output);
        }, 'users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function usersPdf()
    {
        $content = $this->pdf->users($this->userQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="users-'.now()->format('Y-m-d').'.pdf"']);
    }

    public function usersXlsx()
    {
        $content = $this->xlsx->users($this->userQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="users-'.now()->format('Y-m-d').'.xlsx"']);
    }

    public function amenitiesCsv(): StreamedResponse
    {
        $amenities = $this->amenityQuery()->get();

        return response()->streamDownload(function () use ($amenities) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $this->csv->amenityHeaders());

            foreach ($amenities as $amenity) {
                fputcsv($output, $this->csv->amenityRow($amenity));
            }

            fclose($output);
        }, 'amenities-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function amenitiesPdf()
    {
        $content = $this->pdf->amenities($this->amenityQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="amenities-'.now()->format('Y-m-d').'.pdf"']);
    }

    public function amenitiesXlsx()
    {
        $content = $this->xlsx->amenities($this->amenityQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="amenities-'.now()->format('Y-m-d').'.xlsx"']);
    }

    public function auditsCsv(): StreamedResponse
    {
        $logs = $this->auditQuery()->get();

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $this->csv->auditHeaders());

            foreach ($logs as $log) {
                fputcsv($output, $this->csv->auditRow($log));
            }

            fclose($output);
        }, 'audit-history-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function auditsPdf()
    {
        $content = $this->pdf->audits($this->auditQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="audit-history-'.now()->format('Y-m-d').'.pdf"']);
    }

    public function auditsXlsx()
    {
        $content = $this->xlsx->audits($this->auditQuery()->get());

        return response($content, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="audit-history-'.now()->format('Y-m-d').'.xlsx"']);
    }

    private function auditQuery(): Builder
    {
        return AuditLog::query()->with('actor:id,name,user_type')->latest();
    }

    private function amenityQuery(): Builder
    {
        return Amenity::query()
            ->with(['facilities:FID,Facility_Name', 'creator:id,name'])
            ->withCount('requests')
            ->orderBy('name');
    }

    private function facilityQuery(Request $request): Builder
    {
        return Facility::query()
            ->with('amenities:AID,name,inventory_quantity,inventory_type')
            ->when($request->user()->isAdmin(), fn (Builder $query) => $query->assignedToAdmin($request->user()));
    }

    private function requestQuery(Request $request): Builder
    {
        return FacilityRequest::query()
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
