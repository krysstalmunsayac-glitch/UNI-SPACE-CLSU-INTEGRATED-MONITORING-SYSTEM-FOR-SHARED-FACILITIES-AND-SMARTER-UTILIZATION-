<?php

namespace App\Livewire\Queries;

use App\Models\FacilityRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RequestListQuery
{
    public function findVisible(User $actor, int $requestId): FacilityRequest
    {
        return $this->visibleTo($actor)->findOrFail($requestId);
    }

    public function active(
        User $actor,
        string $search,
        string $status,
        string $sortBy,
        string $sortDirection,
    ): LengthAwarePaginator {
        return $this->visibleTo($actor)
            ->with($this->listRelations())
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->when($status === 'Needs Revision', fn (Builder $query) => $query
                ->where('Status', 'Pending')
                ->whereNotNull('Review_Requested_At'))
            ->when(
                $status !== '' && $status !== 'Needs Revision',
                fn (Builder $query) => $query->where('Status', $status),
            )
            ->when($sortBy === 'priority', fn (Builder $query) => $query
                ->orderByRaw("CASE Status
                    WHEN 'Pending' THEN 0
                    WHEN 'Awaiting Payment' THEN 1
                    WHEN 'Approved' THEN 2
                    WHEN 'Rejected' THEN 3
                    WHEN 'Cancelled' THEN 4
                    WHEN 'Ended' THEN 5
                    ELSE 6
                END")
                ->orderBy('Proposed_Date')
                ->orderBy('RID'))
            ->when($sortBy !== 'priority', fn (Builder $query) => $query
                ->orderBy($sortBy, $sortDirection)
                ->orderBy('RID', $sortDirection))
            ->paginate(8, pageName: 'requestsPage');
    }

    public function archived(User $actor, string $search, string $status): LengthAwarePaginator
    {
        abort_unless($actor->isSuperAdmin(), 403);

        return $this->visibleTo($actor, withTrashed: true)
            ->onlyTrashed()
            ->when(
                in_array($status, ['Cancelled', 'Approved', 'Rejected', 'Ended'], true),
                fn (Builder $query) => $query->where('Status', $status),
            )
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search, includeId: true))
            ->with($this->listRelations())
            ->orderByDesc('deleted_at')
            ->paginate(8, pageName: 'archivedRequestsPage');
    }

    /** @return array{total:int,pending:int,revisions:int,approved:int,rejected:int} */
    public function stats(User $actor): array
    {
        $stats = $this->visibleTo($actor)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN Status = 'Pending' AND Review_Requested_At IS NULL THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN Status = 'Pending' AND Review_Requested_At IS NOT NULL THEN 1 ELSE 0 END) as revisions")
            ->selectRaw("SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'revisions' => (int) $stats->revisions,
            'approved' => (int) $stats->approved,
            'rejected' => (int) $stats->rejected,
        ];
    }

    private function visibleTo(User $actor, bool $withTrashed = false): Builder
    {
        $query = $withTrashed ? FacilityRequest::withTrashed() : FacilityRequest::query();

        if ($actor->isAdmin()) {
            $query->whereHas(
                'facility.assignedAdmins',
                fn (Builder $query) => $query->where('users.id', $actor->id),
            );
        }

        return $query;
    }

    private function applySearch(Builder $query, string $search, bool $includeId = false): Builder
    {
        $term = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($term, $includeId): void {
            if ($includeId) {
                $query->where('RID', 'like', $term);
            } else {
                $query->where('Guest_Name', 'like', $term);
            }

            $query
                ->orWhere('Guest_Name', 'like', $term)
                ->orWhere('Guest_Organization', 'like', $term)
                ->orWhere('Purpose', 'like', $term)
                ->orWhere('Request_Details', 'like', $term)
                ->orWhere('Status', 'like', $term)
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $term))
                ->orWhereHas('facility', fn (Builder $facilityQuery) => $facilityQuery->where('Facility_Name', 'like', $term));
        });
    }

    /** @return array<int, string> */
    private function listRelations(): array
    {
        return [
            'user:id,name,email',
            'creator:id,name',
            'event:EID,Event_Scope,Description',
            'facility:FID,Facility_Name,Price',
        ];
    }
}
