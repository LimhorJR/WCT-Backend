<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
                'name' => $this->name,
                'category' => $this->category,
                'images' => $this->images,
                'brand' => $this->brand,
                'price' => $this->price,                
                'description' => $this->description
        ];
    }
}
