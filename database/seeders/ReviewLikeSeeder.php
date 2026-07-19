<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $index => $review) {
            $likeCount = $index % 4;

            if ($likeCount === 0) {
                continue;
            }

            $likeUserIds = $users
                ->where('id', '!=', $review->user_id)
                ->take($likeCount)
                ->pluck('id')
                ->toArray();

            $review->likedUsers()->syncWithoutDetaching($likeUserIds);
        }
    }
}
