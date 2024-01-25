<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class APIResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'],
            'message' => $this->resource['message'] ?? null,
            'http_code' => $this->resource['http_code'] ?? null,
            'data' => $this->resource['data'] ?? null,
        ];
    }
}
