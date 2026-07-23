<?php

namespace Tests\Unit\Policies;

use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BookPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new BookPolicy;
    }

    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $this->assertTrue(
            $this->policy->update($owner, $book)
        );
    }

    public function test_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $this->assertFalse(
            $this->policy->update($otherUser, $book)
        );
    }

    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $this->assertTrue(
            $this->policy->delete($owner, $book)
        );
    }

    public function test_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $this->assertFalse(
            $this->policy->delete($otherUser, $book)
        );
    }
}
