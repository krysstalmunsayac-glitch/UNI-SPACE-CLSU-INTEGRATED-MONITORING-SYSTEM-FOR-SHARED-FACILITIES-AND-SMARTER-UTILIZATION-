<?php

namespace App\Actions\Requests;

use App\Models\FacilityRequest;
use App\Services\RequestWorkflowService;

class CancelRequest
{
    public function __construct(private readonly RequestWorkflowService $workflow) {}

    public function handle(FacilityRequest $request, string $reason, bool $notify = true): bool
    {
        return $this->workflow->cancel($request, $reason, $notify);
    }
}
