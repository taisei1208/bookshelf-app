<?php

namespace Tests\Unit\Policies;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Policies\ReadingPlanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanPolicyTest extends TestCase
{
    use RefreshDatabase;
    private ReadingPlanPolicy $policy;
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ReadingPlanPolicy();
    }
    public function test_owner_can_update_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $result = $this->policy->update(
            $owner,
            $plan
        );

        $this->assertTrue($result);
    }

    public function test_owner_can_update_expired_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->expired()
            ->create();

        $result = $this->policy->update(
            $owner,
            $plan
        );

        $this->assertTrue($result);
    }

    public function test_owner_cannot_update_completed_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->completed()
            ->create();

        $result = $this->policy->update(
            $owner,
            $plan
        );

        $this->assertFalse($result);
    }

    public function test_non_owner_cannot_update_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $result = $this->policy->update(
            $otherUser,
            $plan
        );

        $this->assertFalse($result);
    }

    public function test_owner_can_delete_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $result = $this->policy->delete(
            $owner,
            $plan
        );

        $this->assertTrue($result);
    }

    public function test_owner_can_delete_completed_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->completed()
            ->create();

        $result = $this->policy->delete(
            $owner,
            $plan
        );

        $this->assertTrue($result);
    }

    public function test_non_owner_cannot_delete_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $result = $this->policy->delete(
            $otherUser,
            $plan
        );

        $this->assertFalse($result);
    }

    public function test_owner_can_complete_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $result = $this->policy->complete(
            $owner,
            $plan
        );

        $this->assertTrue($result);
    }

    public function test_owner_can_complete_expired_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->expired()
            ->create();

        $result = $this->policy->complete(
            $owner,
            $plan
        );

        $this->assertTrue($result);
    }

    public function test_non_owner_cannot_complete_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $result = $this->policy->complete(
            $otherUser,
            $plan
        );

        $this->assertFalse($result);
    }
}
