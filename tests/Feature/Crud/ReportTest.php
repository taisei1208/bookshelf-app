<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_report(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_reviews_can_view_empty_report(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                return $stats['summary'] === [
                    'total_reviews' => 0,
                    'books_read' => 0,
                    'average_rating' => 0.0,
                ]
                    && $stats[
                        'rating_distribution'
                    ]->all() === [0, 0, 0, 0, 0]
                    && $stats[
                        'top_rated_books'
                    ]->isEmpty()
                    && $stats[
                        'genre_ratings'
                    ]->isEmpty();
            }
        );
    }

    public function test_report_displays_only_authenticated_users_statistics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $mystery = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $ratings = collect([5, 5, 4, 4, 4, 3]);

        $books = $ratings->map(
            fn (int $rating, int $index): Book => Book::factory()->create([
                'title' => "書籍{$index}",
            ])
        );

        $books[0]->genres()->attach([
            $novel->id,
            $mystery->id,
        ]);

        $books[1]->genres()->attach($mystery->id);
        $books[2]->genres()->attach($novel->id);

        $ratings->each(
            function (
                int $rating,
                int $index
            ) use ($user, $books): void {
                Review::factory()
                    ->for($user)
                    ->for($books[$index])
                    ->create([
                        'rating' => $rating,
                    ]);
            }
        );

        // 他人のレビューは集計対象外
        Review::factory()
            ->for($otherUser)
            ->create([
                'rating' => 1,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                return $stats['summary'][
                    'total_reviews'
                ] === 6
                    && $stats['summary'][
                        'books_read'
                    ] === 6
                    && round(
                        $stats['summary'][
                            'average_rating'
                        ],
                        1
                    ) === 4.2
                    && $stats[
                        'rating_distribution'
                    ]->all() === [0, 0, 1, 3, 2]
                    && $stats[
                        'top_rated_books'
                    ]->count() === 5
                    && $stats[
                        'genre_ratings'
                    ]->count() === 2;
            }
        );
    }
}
