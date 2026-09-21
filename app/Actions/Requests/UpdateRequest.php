<?php

namespace App\Actions\Requests;

use App\Models\FacilityRequest;
use App\Services\RequestWorkflowService;

class UpdateRequest
{
    public function __construct(private readonly RequestWorkflowService $workflow) {}

    public function handle(FacilityRequest $request, array $data): bool
    {
        return $this->workflow->update($request, $data);
    }
}
