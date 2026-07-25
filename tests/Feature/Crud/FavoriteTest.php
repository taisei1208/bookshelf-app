<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'お気に入りを追加しました。'
        );

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertTrue(
            $user->fresh()
                ->favoriteBooks
                ->contains($book)
        );
    }

    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'お気に入りを解除しました。'
        );

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertFalse(
            $user->fresh()
                ->favoriteBooks
                ->contains($book)
        );
    }

    public function test_favorites_index_displays_only_current_user_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $favoriteBook = Book::factory()->create();
        $nonFavoriteBook = Book::factory()->create();
        $otherUserFavoriteBook = Book::factory()->create();

        $user->favoriteBooks()->attach($favoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherUserFavoriteBook);

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');

        $response->assertViewHas('books');
        $response->assertSee($favoriteBook->title);
        $response->assertDontSee($nonFavoriteBook->title);
        $response->assertDontSee($otherUserFavoriteBook->title);
    }

    public function test_guest_cannot_add_book_to_favorites(): void
    {
        $book = Book::factory()->create();

        $response = $this
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }

    public function test_guest_cannot_view_favorites_index(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }
}
