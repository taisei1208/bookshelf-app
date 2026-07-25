<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge([
            'rating' => 5,
            'comment' => '面白い書籍でした。',
        ], $overrides);
    }

    public function test_store_rejects_invalid_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(
                route('reviews.store', $book),
                $this->validPayload([
                    'rating' => 6,
                ])
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_book_detail_displays_review(): void
    {
        $user = User::factory()->create([
            'name' => 'レビュー投稿者',
        ]);

        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($user)
            ->for($book)
            ->create([
                'rating' => 4,
                'comment' => '表示確認用レビュー',
            ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();
        $response->assertViewIs('books.show');
        $response->assertSee($review->comment);
        $response->assertSee($user->name);
    }

    public function test_owner_can_view_review_edit_screen(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->for($book)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertViewIs('reviews.edit');

        $response->assertViewHas('review');
    }

    public function test_owner_can_update_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->for($book)
            ->create([
                'rating' => 3,
                'comment' => '更新前のレビュー',
            ]);

        $response = $this
            ->actingAs($owner)
            ->put(
                route('reviews.update', $review),
                $this->validPayload([
                    'rating' => 5,
                    'comment' => '更新後のレビュー',
                ])
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);
    }

    public function test_update_rejects_invalid_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->for($book)
            ->create([
                'rating' => 3,
                'comment' => '更新前のレビュー',
            ]);

        $response = $this
            ->actingAs($owner)
            ->from(route('reviews.edit', $review))
            ->put(
                route('reviews.update', $review),
                $this->validPayload([
                    'rating' => 0,
                    'comment' => '不正な更新',
                ])
            );

        $response->assertRedirect(
            route('reviews.edit', $review)
        );

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '不正な更新',
        ]);
    }

    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->for($book)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }
}
