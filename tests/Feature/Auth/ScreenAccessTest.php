<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_public_screens(): void
    {
        $book = Book::factory()->create();

        $screens = [
            route('books.index') => 'books.index',
            route('books.show', $book) => 'books.show',
            route('ranking.index') => 'ranking.index',
        ];

        foreach ($screens as $url => $view) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertViewIs($view);
        }
    }

    public function test_guest_cannot_view_authenticated_screens(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->for($book)->create();

        $protectedUrls = [
            route('books.create'),
            route('books.edit', $book),
            route('reviews.edit', $review),
            route('genres.index'),
            route('genres.create'),
            route('genres.edit', $genre),
            route('genres.show', $genre),
            route('favorites.index'),
        ];

        foreach ($protectedUrls as $url) {
            $response = $this->get($url);

            $response->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_user_can_view_authenticated_screens(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($user)->create();
        $review = Review::factory()->for($user)->for($book)->create();

        $screens = [
            route('books.create') => 'books.create',
            route('books.edit', $book) => 'books.edit',
            route('reviews.edit', $review) => 'reviews.edit',
            route('genres.index') => 'genres.index',
            route('genres.create') => 'genres.create',
            route('genres.show', $genre) => 'genres.show',
            route('genres.edit', $genre) => 'genres.edit',
            route('favorites.index') => 'favorites.index',
        ];

        foreach ($screens as $url => $view) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
            $response->assertViewIs($view);
        }
    }
}
