<?php

namespace App\Actions\Requests;

use App\Models\FacilityRequest;
use App\Services\RequestWorkflowService;

class RejectRequest
{
    public function __construct(private readonly RequestWorkflowService $workflow) {}

    public function handle(FacilityRequest $request, string $reasons): void
    {
        $this->workflow->reject($request, $reasons);
    }
}
