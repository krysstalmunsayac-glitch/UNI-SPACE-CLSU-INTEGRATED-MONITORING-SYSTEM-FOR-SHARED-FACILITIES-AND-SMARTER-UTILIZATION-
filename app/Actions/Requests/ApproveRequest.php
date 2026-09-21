<?php

namespace App\Actions\Requests;

use App\Models\FacilityRequest;
use App\Services\RequestWorkflowService;

class ApproveRequest
{
    public function __construct(private readonly RequestWorkflowService $workflow) {}

    public function handle(FacilityRequest $request): ?array
    {
        return $this->workflow->approve($request);
    }
}
