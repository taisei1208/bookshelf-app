<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(
            '2026-08-08 10:00:00'
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function createReadingPlan(
        CarbonImmutable $targetDate,
        ReadingPlanStatus $status,
        ?CarbonImmutable $completedAt = null
    ): ReadingPlan {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        return ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => $targetDate->toDateString(),
                'status' => $status->value,
                'completed_at' => $completedAt,
            ]);
    }

    public function test_command_expires_overdue_reading_plan(): void
    {
        $plan = $this->createReadingPlan(
            CarbonImmutable::today()->subDay(),
            ReadingPlanStatus::Reading
        );

        $this->artisan('update:reading-plan-status')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Expired->value,
            'completed_at' => null,
        ]);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );
    }

    public function test_command_does_not_expire_future_plan(): void
    {
        $plan = $this->createReadingPlan(
            CarbonImmutable::today()->addDay(),
            ReadingPlanStatus::Reading
        );

        $this->artisan('update:reading-plan-status')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Reading->value,
        ]);
    }

    public function test_command_does_not_change_completed_plan(): void
    {
        $completedAt = CarbonImmutable::today()
            ->subDays(2)
            ->setTime(15, 30);

        $plan = $this->createReadingPlan(
            CarbonImmutable::today()->subDays(3),
            ReadingPlanStatus::Completed,
            $completedAt
        );

        $this->artisan('update:reading-plan-status')
            ->assertExitCode(0);

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertTrue(
            $plan->completed_at->equalTo($completedAt)
        );
    }
}
