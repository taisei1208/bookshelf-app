<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        $this->assertInstanceOf(
            BelongsTo::class,
            $review->user()
        );
        $this->assertTrue($review->user->is($user));
    }

    public function test_review_belongs_to_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->for($book)->create();

        $this->assertInstanceOf(
            BelongsTo::class,
            $review->book()
        );
        $this->assertTrue($review->book->is($book));
    }

    public function test_review_belongs_to_many_liked_users(): void
    {
        $review = Review::factory()->create();
        $users = User::factory()->count(2)->create();

        $review->likedByUsers()->attach($users->pluck('id'));

        $this->assertInstanceOf(
            BelongsToMany::class,
            $review->likedByUsers()
        );
        $this->assertCount(2, $review->likedByUsers);
        $this->assertTrue($review->likedByUsers->pluck('id')->contains($users->first()->id));
    }
}
