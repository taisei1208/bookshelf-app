<?php

namespace Tests\Feature\Authorization;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function updatePayload(Book $book, Genre $genre, array $overrides = [])
    {
        return array_merge([
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-1-1',
            'description' => '更新後の説明',
            'image_url' => null,
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_owner_can_view_book_edit_screen(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertViewIs('books.edit');
    }

    public function test_non_owner_cannot_view_book_edit_screen(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->put(
                route('books.update', $book),
                $this->updatePayload($book, $genre)
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
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
                'title' => '更新前のタイトル',
            ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(
                route('books.update', $book),
                $this->updatePayload($book, $genre)
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前のタイトル',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
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

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
