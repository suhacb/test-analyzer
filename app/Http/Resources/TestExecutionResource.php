<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'test_scenario_id' => $this->test_scenario_id,
            'side'             => $this->side,
            'outcome'          => $this->outcome,
            'outcome_raw'      => $this->outcome_raw,
            'comments'         => $this->comments,
            'tester_name'      => $this->tester_name,
            'browser'          => $this->browser,
            'tested_at'        => $this->tested_at,
            'source_file'      => $this->source_file,
            'review_notes'     => $this->review_notes,
            'reviewed_at'      => $this->reviewed_at,
            'test_scenario'    => new TestScenarioResource($this->whenLoaded('testScenario')),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
