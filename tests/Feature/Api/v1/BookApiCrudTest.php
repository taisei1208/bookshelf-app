<?php

namespace Tests\Feature\Api\v1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(
        Genre $genre,
        array $overrides = []
    ) {
        return array_merge([
            'title' => 'APIテスト書籍',
            'author' => 'APIテスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-25',
            'description' => 'APIテスト書籍の説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_index_returns_paginated_books(): void
    {
        $this->withoutExceptionHandling();
        $genre = Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $book = Book::factory()->create([
            'title' => '一覧取得する書籍',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()
            ->for($book)
            ->create(['rating' => 4]);

        Review::factory()
            ->for($book)
            ->create(['rating' => 2]);

        $response = $this->getJson(
            '/api/v1/books?per_page=1'
        );

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_url',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'average_rating',
                    'reviews_count',
                ],
            ],
            'links',
            'meta',
        ]);

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath(
            'data.0.reviews_count',
            2
        );
    }

    public function test_index_filters_books_by_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'プログラミング入門',
            'author' => 'Laravel花子',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '佐藤一郎',
        ]);

        $response = $this->getJson(
            '/api/v1/books?keyword=Laravel'
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $response->assertJsonFragment([
            'title' => 'Laravel入門',
        ]);

        $response->assertJsonFragment([
            'author' => 'Laravel花子',
        ]);

        $response->assertJsonMissing([
            'title' => 'PHP入門',
        ]);
    }

    public function test_index_filters_books_by_genre(): void
    {
        $fantasy = Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $mystery = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $fantasyBook = Book::factory()->create([
            'title' => 'ファンタジー書籍',
        ]);

        $mysteryBook = Book::factory()->create([
            'title' => 'ミステリー書籍',
        ]);

        $fantasyBook->genres()->attach($fantasy->id);
        $mysteryBook->genres()->attach($mystery->id);

        $response = $this->getJson(
            "/api/v1/books?genre_id={$fantasy->id}"
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        $response->assertJsonPath(
            'data.0.id',
            $fantasyBook->id
        );

        $response->assertJsonMissing([
            'id' => $mysteryBook->id,
            'title' => $mysteryBook->title,
        ]);
    }

    public function test_show_returns_book_details(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $book = Book::factory()->create([
            'title' => '詳細取得する書籍',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()
            ->count(2)
            ->for($book)
            ->create();

        $response = $this->getJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertOk();

        $response->assertJsonPath(
            'data.id',
            $book->id
        );

        $response->assertJsonPath(
            'data.title',
            '詳細取得する書籍'
        );

        $response->assertJsonPath(
            'data.genres.0.id',
            $genre->id
        );

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres',
                'reviews',
            ],
        ]);

        $response->assertJsonCount(2, 'data.reviews');
    }

    public function test_authenticated_user_can_store_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genres = Genre::factory()
            ->count(2)
            ->create();

        $response = $this->postJson(
            '/api/v1/books',
            [
                'title' => 'APIから登録する書籍',
                'author' => 'API登録著者',
                'isbn' => '1234567890123',
                'published_date' => '2026-07-25',
                'description' => 'APIから登録しました。',
                'image_url' => null,
                'genres' => $genres->pluck('id')->all(),
            ]
        );

        $response->assertCreated();

        $book = Book::where(
            'isbn',
            '1234567890123'
        )->firstOrFail();

        $response->assertJsonPath(
            'data.id',
            $book->id
        );

        $response->assertJsonPath(
            'data.title',
            'APIから登録する書籍'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => 'APIから登録する書籍',
            'isbn' => '1234567890123',
        ]);

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_guest_cannot_store_book(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->postJson(
            '/api/v1/books',
            $this->validPayload($genre)
        );

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('books', [
            'title' => 'APIテスト書籍',
        ]);
    }

    public function test_store_rejects_invalid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/books',
            [
                'title' => null,
                'author' => null,
                'isbn' => '123',
                'published_date' => '不正な日付',
                'genres' => [],
            ]
        );

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '123',
        ]);
    }

    public function test_owner_can_update_book_and_genres(): void
    {
        $owner = User::factory()->create();

        $oldGenre = Genre::factory()->create([
            'name' => '変更前ジャンル',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => '変更後ジャンル',
        ]);

        $book = Book::factory()
            ->for($owner)
            ->create([
                'title' => '更新前タイトル',
                'isbn' => '1234567890123',
            ]);

        $book->genres()->attach($oldGenre->id);

        Sanctum::actingAs($owner);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $this->validPayload($newGenre, [
                'title' => '更新後タイトル',
                'isbn' => $book->isbn,
            ])
        );

        $response->assertOk();

        $response->assertJsonPath(
            'data.id',
            $book->id
        );

        $response->assertJsonPath(
            'data.title',
            '更新後タイトル'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新後タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create([
                'title' => '更新前タイトル',
            ]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $this->validPayload($genre, [
                'title' => '不正な更新タイトル',
                'isbn' => $book->isbn,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正な更新タイトル',
        ]);
    }

    public function test_guest_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create([
                'title' => '更新前タイトル',
            ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $this->validPayload($genre, [
                'title' => '未認証更新タイトル',
                'isbn' => $book->isbn,
            ])
        );

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        Sanctum::actingAs($owner);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_show_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson(
            '/api/v1/books/999999'
        );

        $response->assertNotFound();

        $response->assertJson([
            'error' => '書籍情報が見つかりませんでした。',
        ]);
    }
}
