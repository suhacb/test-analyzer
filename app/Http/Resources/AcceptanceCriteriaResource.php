<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcceptanceCriteriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'user_story_id'        => $this->user_story_id,
            'code'                 => $this->code,
            'title'                => $this->title,
            'test_scenarios_count' => $this->whenCounted('testScenarios'),
            'is_accepted'          => $this->when(
                $this->relationLoaded('testScenarios'),
                fn() => $this->isAccepted()
            ),
            'user_story'           => new UserStoryResource($this->whenLoaded('userStory')),
            'test_scenarios'       => TestScenarioResource::collection(
                $this->whenLoaded('testScenarios')
            ),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
