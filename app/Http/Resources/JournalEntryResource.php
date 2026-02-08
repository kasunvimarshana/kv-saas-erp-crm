<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'entry_number' => $this->entry_number,
            'entry_date' => $this->entry_date->toDateString(),
            'reference' => $this->reference,
            'description' => $this->description,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'posted_at' => $this->posted_at?->toDateTimeString(),
            'posted_by' => $this->posted_by,
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
