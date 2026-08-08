<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

class ReadingReportService
{
    /**
     * ログインユーザーの読書統計を作成する。
     *
     * @return array{
     *     summary: array{
     *         total_reviews: int,
     *         books_read: int,
     *         average_rating: float
     *     },
     *     rating_distribution: Collection<int, int>,
     *     top_rated_books: Collection<int, array{
     *         id: int,
     *         title: string,
     *         author: string,
     *         rating: int
     *     }>,
     *     genre_ratings: Collection<int, array{
     *         id: int,
     *         name: string,
     *         count: int,
     *         average_rating: float
     *     }>
     * }
     */
    public function generate(User $user): array
    {
        $reviews = $user->reviews()
            ->with('book.genres')
            ->get();

        return [
            'summary' => $this->makeSummary($reviews),
            'rating_distribution' => $this->makeRatingDistribution($reviews),
            'top_rated_books' => $this->makeTopRatedBooks($reviews),
            'genre_ratings' => $this->makeGenreRatings($reviews),
        ];
    }

    /**
     * 基本統計を作成する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array{
     *     total_reviews: int,
     *     books_read: int,
     *     average_rating: float
     * }
     */
    private function makeSummary(Collection $reviews): array
    {
        return [
            'total_reviews' => $reviews->count(),

            'books_read' => $reviews
                ->pluck('book_id')
                ->unique()
                ->count(),

            'average_rating' => (float) (
                $reviews->avg('rating') ?? 0
            ),
        ];
    }

    /**
     * 星1から星5までの評価件数を作成する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, int>
     */
    private function makeRatingDistribution(
        Collection $reviews
    ): Collection {
        return collect(range(1, 5))
            ->map(
                fn (int $rating): int => $reviews
                    ->where('rating', $rating)
                    ->count()
            );
    }

    /**
     * 評価4以上の書籍を評価順に最大5件取得する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array{
     *     id: int,
     *     title: string,
     *     author: string,
     *     rating: int
     * }>
     */
    private function makeTopRatedBooks(Collection $reviews): Collection
    {
        return $reviews
            ->groupBy('book_id')
            ->filter(
                fn (Collection $bookReviews): bool => (float) $bookReviews->avg('rating') >= 4
            )
            ->sortByDesc(
                fn (Collection $bookReviews): float => (float) $bookReviews->avg('rating')
            )
            ->take(5)
            ->map(function (Collection $bookReviews): array {
                $review = $bookReviews->first();

                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => (int) round(
                        (float) $bookReviews->avg('rating')
                    ),
                ];
            })
            ->values();
    }

    /**
     * ジャンルごとの平均評価とレビュー件数を作成する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     count: int,
     *     average_rating: float
     * }>
     */
    private function makeGenreRatings(Collection $reviews): Collection
    {
        return $reviews
            ->flatMap(
                fn (Review $review): Collection => $review->book->genres->map(
                    fn ($genre): array => [
                        'genre' => $genre,
                        'rating' => $review->rating,
                    ]
                )
            )
            ->groupBy('genre.id')
            ->map(function (Collection $items): array {
                $genre = $items->first()['genre'];

                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'count' => $items->count(),
                    'average_rating' => round(
                        (float) $items->avg('rating'),
                        1
                    ),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();
    }
}
