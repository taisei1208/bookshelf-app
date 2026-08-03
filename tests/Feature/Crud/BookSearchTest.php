<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;
    private function bookIds(
        LengthAwarePaginator $books
    ): array {
        return $books
            ->getCollection()
            ->pluck('id')
            ->values()
            ->all();
    }

    public function test_keyword_matches_book_title(): void
    {
        $matchingBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(
            route('books.index', [
                'keyword' => 'Laravel',
            ])
        );

        $response->assertOk();
        $response->assertViewIs('books.index');

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use ($matchingBook): bool {
                return $this->bookIds($books)
                    === [$matchingBook->id];
            }
        );
    }

    public function test_keyword_matches_book_author(): void
    {
        $matchingBook = Book::factory()->create([
            'title' => 'PHP入門',
            'author' => 'Laravel研究会',
        ]);

        Book::factory()->create([
            'title' => 'Java入門',
            'author' => '山田太郎',
        ]);

        $response = $this->get(
            route('books.index', [
                'keyword' => 'Laravel',
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use ($matchingBook): bool {
                return $this->bookIds($books)
                    === [$matchingBook->id];
            }
        );
    }

    public function test_keyword_uses_partial_matching(): void
    {
        $matchingBook = Book::factory()->create([
            'title' => 'はじめてのLaravel開発',
        ]);

        Book::factory()->create([
            'title' => 'Java開発入門',
        ]);

        $response = $this->get(
            route('books.index', [
                'keyword' => 'Laravel',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use ($matchingBook): bool {
                return $this->bookIds($books)
                    === [$matchingBook->id];
            }
        );
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $technical = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelBook = Book::factory()->create();
        $technicalBook = Book::factory()->create();

        $novelBook->genres()->attach($novel->id);
        $technicalBook->genres()->attach(
            $technical->id
        );

        $response = $this->get(
            route('books.index', [
                'genre' => $novel->id,
            ])
        );

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use ($novelBook): bool {
                return $this->bookIds($books)
                    === [$novelBook->id];
            }
        );
    }

    public function test_keyword_and_genre_can_be_combined(): void
    {
        $novel = Genre::factory()->create();
        $technical = Genre::factory()->create();

        $matchingBook = Book::factory()->create([
            'title' => 'Laravelの物語',
        ]);

        $wrongGenreBook = Book::factory()->create([
            'title' => 'Laravel実践',
        ]);

        $wrongKeywordBook = Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        $matchingBook->genres()->attach($novel->id);
        $wrongGenreBook->genres()->attach(
            $technical->id
        );
        $wrongKeywordBook->genres()->attach(
            $novel->id
        );

        $response = $this->get(
            route('books.index', [
                'keyword' => 'Laravel',
                'genre' => $novel->id,
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use ($matchingBook): bool {
                return $this->bookIds($books)
                    === [$matchingBook->id];
            }
        );
    }

    public function test_default_sort_is_latest(): void
    {
        $oldestBook = Book::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $middleBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $latestBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get(
            route('books.index')
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use (
                $latestBook,
                $middleBook,
                $oldestBook
            ): bool {
                return $this->bookIds($books) === [
                    $latestBook->id,
                    $middleBook->id,
                    $oldestBook->id,
                ];
            }
        );
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        $oldestBook = Book::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $middleBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $latestBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get(
            route('books.index', [
                'sort' => 'oldest',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use (
                $oldestBook,
                $middleBook,
                $latestBook
            ): bool {
                return $this->bookIds($books) === [
                    $oldestBook->id,
                    $middleBook->id,
                    $latestBook->id,
                ];
            }
        );
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        $bookC = Book::factory()->create([
            'title' => 'C Book',
        ]);

        $bookA = Book::factory()->create([
            'title' => 'A Book',
        ]);

        $bookB = Book::factory()->create([
            'title' => 'B Book',
        ]);

        $response = $this->get(
            route('books.index', [
                'sort' => 'title',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use (
                $bookA,
                $bookB,
                $bookC
            ): bool {
                return $this->bookIds($books) === [
                    $bookA->id,
                    $bookB->id,
                    $bookC->id,
                ];
            }
        );
    }

    public function test_books_can_be_sorted_by_rating(): void
    {
        $highRatedBook = Book::factory()->create();
        $middleRatedBook = Book::factory()->create();
        $lowRatedBook = Book::factory()->create();

        Review::factory()
            ->for($highRatedBook)
            ->create([
                'rating' => 5,
            ]);

        Review::factory()
            ->for($middleRatedBook)
            ->create([
                'rating' => 4,
            ]);

        Review::factory()
            ->for($lowRatedBook)
            ->create([
                'rating' => 2,
            ]);

        $response = $this->get(
            route('books.index', [
                'sort' => 'rating',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use (
                $highRatedBook,
                $middleRatedBook,
                $lowRatedBook
            ): bool {
                return $this->bookIds($books) === [
                    $highRatedBook->id,
                    $middleRatedBook->id,
                    $lowRatedBook->id,
                ];
            }
        );
    }

    public function test_rating_sort_uses_average_rating(): void
    {
        $averageFourBook = Book::factory()->create();
        $ratingThreeBook = Book::factory()->create();

        Review::factory()
            ->for($averageFourBook)
            ->create([
                'rating' => 5,
            ]);

        Review::factory()
            ->for($averageFourBook)
            ->create([
                'rating' => 3,
            ]);

        Review::factory()
            ->for($ratingThreeBook)
            ->create([
                'rating' => 3,
            ]);

        $response = $this->get(
            route('books.index', [
                'sort' => 'rating',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use (
                $averageFourBook,
                $ratingThreeBook
            ): bool {
                return $this->bookIds($books) === [
                    $averageFourBook->id,
                    $ratingThreeBook->id,
                ];
            }
        );
    }

    public function test_rating_sort_places_unreviewed_books_last(): void
    {
        $reviewedBook = Book::factory()->create();
        $unreviewedBook = Book::factory()->create();

        Review::factory()
            ->for($reviewedBook)
            ->create([
                'rating' => 1,
            ]);

        $response = $this->get(
            route('books.index', [
                'sort' => 'rating',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use (
                $reviewedBook,
                $unreviewedBook
            ): bool {
                return $this->bookIds($books) === [
                    $reviewedBook->id,
                    $unreviewedBook->id,
                ];
            }
        );
    }

    public function test_pagination_keeps_search_conditions(): void
    {
        $genre = Genre::factory()->create();

        $books = Book::factory()
            ->count(11)
            ->create([
                'title' => 'Laravel Book',
            ]);

        $books->each(
            fn (Book $book) =>
                $book->genres()->attach($genre->id)
        );

        $response = $this->get(
            route('books.index', [
                'keyword' => 'Laravel',
                'genre' => $genre->id,
                'sort' => 'title',
            ])
        );

        $response->assertViewHas(
            'books',
            function (
                LengthAwarePaginator $books
            ) use ($genre): bool {
                $nextPageUrl = $books->nextPageUrl();

                if ($nextPageUrl === null) {
                    return false;
                }

                parse_str(
                    (string) parse_url(
                        $nextPageUrl,
                        PHP_URL_QUERY
                    ),
                    $query
                );

                return $query['keyword'] === 'Laravel'
                    && $query['genre']
                        === (string) $genre->id
                    && $query['sort'] === 'title'
                    && $query['page'] === '2';
            }
        );
    }

    public function test_invalid_sort_returns_validation_error(): void
    {
        $response = $this
            ->from(route('books.index'))
            ->get(
                route('books.index', [
                    'sort' => 'invalid',
                ])
            );

        $response->assertRedirect(
            route('books.index')
        );

        $response->assertSessionHasErrors('sort');
    }
}
