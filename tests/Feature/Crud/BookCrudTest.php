<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(
        Genre $genre,
        array $overrides = []
    ): array {
        return array_merge([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-25',
            'description' => 'テスト書籍の説明です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_book_index_displays_books(): void
    {
        $firstBook = Book::factory()->create([
            'title' => '最初の書籍',
        ]);

        $secondBook = Book::factory()->create([
            'title' => '次の書籍',
        ]);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
        $response->assertViewHas('books');
        $response->assertSee($firstBook->title);
        $response->assertSee($secondBook->title);
    }

    public function test_book_detail_displays_book(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $book = Book::factory()->create([
            'title' => '詳細表示する書籍',
            'author' => '詳細表示する著者',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();
        $response->assertViewIs('books.show');

        $response->assertViewHas('book');

        $response->assertSee('詳細表示する書籍');
        $response->assertSee('詳細表示する著者');
        $response->assertSee('ファンタジー');
    }

    public function test_authenticated_user_can_view_book_create_screen(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('books.create'));

        $response->assertOk();
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres');
        $response->assertSee($genre->name);
    }

    public function test_authenticated_user_can_store_book(): void
    {
        $user = User::factory()->create();

        $genres = Genre::factory()
            ->count(2)
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route('books.store'),
                [
                    'title' => '登録する書籍',
                    'author' => '登録する著者',
                    'isbn' => '1234567890123',
                    'published_date' => '2026-07-25',
                    'description' => '登録する書籍の説明',
                    'image_url' => null,
                    'genres' => $genres->pluck('id')->all(),
                ]
            );

        $book = Book::where(
            'isbn',
            '1234567890123'
        )->firstOrFail();

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '登録する書籍',
            'author' => '登録する著者',
            'isbn' => '1234567890123',
        ]);

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_store_rejects_invalid_data(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('books.create'))
            ->post(
                route('books.store'),
                $this->validPayload($genre, [
                    'title' => null,
                ])
            );

        $response->assertRedirect(
            route('books.create')
        );

        $response->assertSessionHasErrors('title');

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_owner_can_update_book(): void
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
                'title' => '更新前のタイトル',
                'isbn' => '1234567890123',
            ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this
            ->actingAs($owner)
            ->put(
                route('books.update', $book),
                $this->validPayload($newGenre, [
                    'title' => '更新後のタイトル',
                    'author' => '更新後の著者',
                    'isbn' => $book->isbn,
                ])
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            '書籍情報を更新しました。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
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

    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(
            route('books.index')
        );

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }
}
