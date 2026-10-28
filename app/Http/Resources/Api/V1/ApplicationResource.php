<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'clock_in' => $this->clock_in ?? null,
            'clock_out' => $this->clock_out ?? null,
            'comment' => $this->comment ?? null,
            'status' => $this->status ?? null,
        ];
    }
}
