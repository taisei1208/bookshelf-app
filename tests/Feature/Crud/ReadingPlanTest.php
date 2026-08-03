<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reading_plan_features(): void
    {
        $plan = ReadingPlan::factory()->create();
        $book = Book::factory()->create();

        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));

        $this->get(route('reading-plans.create'))
            ->assertRedirect(route('login'));

        $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => today()
                ->addWeek()
                ->format('Y-m-d'),
        ])->assertRedirect(route('login'));

        $this->get(route('reading-plans.edit', $plan))
            ->assertRedirect(route('login'));

        $this->put(route('reading-plans.update', $plan), [
            'target_date' => today()
                ->addWeek()
                ->format('Y-m-d'),
        ])->assertRedirect(route('login'));

        $this->post(route('reading-plans.complete', $plan))
            ->assertRedirect(route('login'));

        $this->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_own_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPlan = ReadingPlan::factory()
            ->for($user)
            ->create();

        $otherPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');

        $response->assertViewHas(
            'readingPlans',
            function (
                LengthAwarePaginator $readingPlans
            ) use ($ownPlan, $otherPlan): bool {
                $plans = $readingPlans->getCollection();

                return $plans->contains(
                    fn (ReadingPlan $plan): bool => $plan->is($ownPlan)
                ) && ! $plans->contains(
                    fn (ReadingPlan $plan): bool => $plan->is($otherPlan)
                );
            }
        );
    }

    public function test_user_can_filter_reading_plans_by_status(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create();

        $expiredPlan = ReadingPlan::factory()
            ->for($user)
            ->expired()
            ->create();

        $completedPlan = ReadingPlan::factory()
            ->for($user)
            ->completed()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Expired->value,
            ]));

        $response->assertOk();

        $response->assertViewHas(
            'currentStatus',
            ReadingPlanStatus::Expired->value
        );

        $response->assertViewHas(
            'readingPlans',
            function (
                LengthAwarePaginator $readingPlans
            ) use (
                $readingPlan,
                $expiredPlan,
                $completedPlan
            ): bool {
                $plans = $readingPlans->getCollection();

                return $plans->contains(
                    fn (ReadingPlan $plan): bool => $plan->is($expiredPlan)
                ) && ! $plans->contains(
                    fn (ReadingPlan $plan): bool => $plan->is($readingPlan)
                ) && ! $plans->contains(
                    fn (ReadingPlan $plan): bool => $plan->is($completedPlan)
                );
            }
        );
    }

    public function test_authenticated_user_can_view_create_screen(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.create'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.create');

        $response->assertViewHas(
            'books',
            function (Collection $viewBooks) use ($books): bool {
                return $books->every(
                    fn (Book $book): bool => $viewBooks->contains(
                        fn (Book $viewBook): bool => $viewBook->is($book)
                    )
                );
            }
        );
    }

    public function test_user_can_store_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $targetDate = today()
            ->addDays(7)
            ->format('Y-m-d');

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(
            route('reading-plans.index')
        );

        $response->assertSessionHas(
            'success',
            '読書計画を登録しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading->value,
            'completed_at' => null,
        ]);
    }

    public function test_user_can_create_multiple_plans_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => today()
                    ->addMonth()
                    ->format('Y-m-d'),
            ]);

        $response->assertRedirect(
            route('reading-plans.index')
        );

        $planCount = ReadingPlan::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->count();

        $this->assertSame(2, $planCount);
    }

    public function test_store_rejects_invalid_input(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('reading-plans.create'))
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => today()
                    ->subDay()
                    ->format('Y-m-d'),
            ]);

        $response->assertRedirect(
            route('reading-plans.create')
        );

        $response->assertSessionHasErrors(
            'target_date'
        );

        $this->assertDatabaseCount(
            'reading_plans',
            0
        );
    }

    public function test_owner_can_view_edit_screen(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->get(route('reading-plans.edit', $plan));

        $response->assertOk();
        $response->assertViewIs('reading-plans.edit');

        $response->assertViewHas(
            'readingPlan',
            fn (ReadingPlan $viewPlan): bool => $viewPlan->is($plan)
        );
    }

    public function test_non_owner_cannot_operate_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $newTargetDate = today()
            ->addMonth()
            ->format('Y-m-d');

        $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newTargetDate,
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $plan))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::Reading->value,
        ]);
    }

    public function test_owner_can_update_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $newTargetDate = today()
            ->addMonth()
            ->format('Y-m-d');

        $response = $this
            ->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(
            route('reading-plans.index')
        );

        $response->assertSessionHas(
            'success',
            '読書計画を更新しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Reading->value,
            'completed_at' => null,
        ]);
    }

    public function test_updating_expired_plan_returns_it_to_reading(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->expired()
            ->create();

        $newTargetDate = today()
            ->addDays(10)
            ->format('Y-m-d');

        $response = $this
            ->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(
            route('reading-plans.index')
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Reading->value,
            'completed_at' => null,
        ]);
    }

    public function test_owner_cannot_edit_or_update_completed_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->completed()
            ->create();

        $this->actingAs($owner)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()
                    ->addMonth()
                    ->format('Y-m-d'),
            ])
            ->assertForbidden();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertNotNull($plan->completed_at);
    }

    public function test_owner_can_complete_reading_plan(): void
    {
        $owner = User::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->post(route('reading-plans.complete', $plan));

        $response->assertRedirect(
            route('reading-plans.index')
        );

        $response->assertSessionHas(
            'success',
            '読了しました。'
        );

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertNotNull(
            $plan->completed_at
        );
    }

    public function test_owner_can_delete_reading_plan(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::factory()
            ->for($owner)
            ->for($book)
            ->create();

        $response = $this
            ->actingAs($owner)
            ->delete(
                route(
                    'reading-plans.destroy',
                    $plan
                )
            );

        $response->assertRedirect(
            route('reading-plans.index')
        );

        $response->assertSessionHas(
            'success',
            '読書計画を削除しました。'
        );

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }
}
