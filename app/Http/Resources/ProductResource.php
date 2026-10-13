<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'price' => $this->price_cents / 100,
            'price_cents' => $this->price_cents,
            'in_stock' => $this->stock > 0,
            'store' => $this->store?->name,
            'category' => $this->category?->name,
        ];
    }
}
