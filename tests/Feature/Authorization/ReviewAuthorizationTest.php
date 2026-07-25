<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_review_edit_screen(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->for($owner)->for($book)->create();

        $response = $this->actingAs($owner)->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertViewIs('reviews.edit');
    }

    public function test_non_owner_cannot_view_review_edit_screen(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->for($owner)->for($book)->create();

        $response = $this->actingAs($otherUser)->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    public function test_owner_can_update_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->for($owner)->for($book)->create([
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this
            ->actingAs($owner)
            ->put(
                route('reviews.update', $review), [
                    'rating' => 5,
                    'comment' => '更新後のレビュー',
                ]
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);
    }

    public function test_non_owner_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->for($owner)->for($book)->create([
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(
                route('reviews.update', $review), [
                    'rating' => 5,
                    'comment' => '更新後のレビュー',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);
    }

    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()
            ->for($book)
            ->for($owner)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_non_owner_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->create();

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}
