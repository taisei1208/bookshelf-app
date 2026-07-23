<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_books(): void
    {
        $user = User::factory()->create();
        Book::factory()->count(2)->for($user)->create();

        $this->assertInstanceOf(HasMany::class, $user->books());
        $this->assertCount(2, $user->fresh()->books);
        $this->assertInstanceOf(Book::class, $user->books->first());
    }

    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();
        Review::factory()->count(2)->for($user)->create();

        $this->assertInstanceOf(HasMany::class, $user->reviews());
        $this->assertCount(2, $user->fresh()->reviews);
        $this->assertInstanceOf(Review::class, $user->reviews->first());
    }

    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();

        $user->favoriteBooks()->attach($books->pluck('id'));

        $this->assertInstanceOf(
            BelongsToMany::class,
            $user->favoriteBooks()
        );
        $this->assertCount(2, $user->favoriteBooks);
        $this->assertTrue($user->favoriteBooks->pluck('id')->contains($books->first()->id));
    }

    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create();

        $user->likedReviews()->attach($reviews->pluck('id'));

        $this->assertInstanceOf(
            BelongsToMany::class,
            $user->likedReviews()
        );
        $this->assertCount(2, $user->likedReviews);
        $this->assertTrue($user->likedReviews->pluck('id')->contains($reviews->first()->id));
    }
}
