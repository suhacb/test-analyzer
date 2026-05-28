<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestScenarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $executions = $this->whenLoaded('executions');

        return [
            'id'                      => $this->id,
            'acceptance_criteria_id'  => $this->acceptance_criteria_id,
            'code'                    => $this->code,
            'title'                   => $this->title,
            'user_role'               => $this->user_role,
            'preconditions'           => $this->preconditions,
            'test_steps'              => $this->test_steps,
            'expected_result'         => $this->expected_result,
            'executions_count'        => $this->whenCounted('executions'),
            'provider_outcome'        => $this->when(
                $this->relationLoaded('executions'),
                fn() => $this->executions->firstWhere('side', 'provider')?->outcome
            ),
            'client_outcome'          => $this->when(
                $this->relationLoaded('executions'),
                fn() => $this->executions->firstWhere('side', 'client')?->outcome
            ),
            'acceptance_criteria'     => new AcceptanceCriteriaResource(
                $this->whenLoaded('acceptanceCriteria')
            ),
            'executions'              => TestExecutionResource::collection($executions),
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }
}
