<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserStoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'code'                     => $this->code,
            'title'                    => $this->title,
            'acceptance_criteria_count' => $this->whenCounted('acceptanceCriteria'),
            'is_accepted'              => $this->when(
                $this->relationLoaded('acceptanceCriteria'),
                fn() => $this->isAccepted()
            ),
            'acceptance_criteria'      => AcceptanceCriteriaResource::collection(
                $this->whenLoaded('acceptanceCriteria')
            ),
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
