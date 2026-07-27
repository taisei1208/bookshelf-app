<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    public function toggle(Review $review): RedirectResponse
    {
        $user = auth()->user();

        $isReviewLike = $user->likedReviews()
            ->whereKey($review->id)
            ->exists();

        if ($isReviewLike) {
            $user->likedReviews()->detach($review->id);

            return back()->with('success', 'レビューのいいねを解除しました。');
        }

        $user->likedReviews()->syncWithoutDetaching([$review->id]);

        return back()->with('success', 'レビューのいいねを追加しました。');
    }
}
