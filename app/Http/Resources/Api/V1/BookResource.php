<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
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
            'user_id' => $this->user_id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'image_url' => $this->image_url,

            'genres' => GenreResource::collection(
                $this->whenLoaded('genres')
            ),

            'average_rating' => $this->whenAggregated(
                'reviews',
                'rating',
                'avg'
            ),

            'reviews_count' => $this->whenCounted('reviews'),

            'reviews' => ReviewResource::collection(
                $this->whenLoaded('reviews')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
