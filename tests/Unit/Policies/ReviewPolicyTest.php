<?php

namespace Tests\Unit\Policies;

use App\Models\Review;
use App\Models\User;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ReviewPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ReviewPolicy;
    }

    public function test_owner_can_update_review(): void
    {
        $owner = User::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->create();

        $this->assertTrue(
            $this->policy->update($owner, $review)
        );
    }

    public function test_non_owner_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->create();

        $this->assertFalse(
            $this->policy->update($otherUser, $review)
        );
    }

    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->create();

        $this->assertTrue(
            $this->policy->delete($owner, $review)
        );
    }

    public function test_non_owner_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()
            ->for($owner)
            ->create();

        $this->assertFalse(
            $this->policy->delete($otherUser, $review)
        );
    }
}
