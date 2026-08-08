<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(
            '2026-08-07 10:00:00'
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function createReadingPlan(User $user, Book $book, CarbonImmutable $targetDate, ReadingPlanStatus $status): ReadingPlan
    {
        return ReadingPlan::factory()->for($user)
            ->for($book)
            ->create([
                'target_date' => $targetDate->toDateString(),
                'status' => $status->value,
                'completed_at' => null,
            ]);
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get(
            route('notifications.index')
        );

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $userPlan = $this->createReadingPlan(
            $user,
            $book,
            CarbonImmutable::today(),
            ReadingPlanStatus::Reading
        );

        $otherPlan = $this->createReadingPlan(
            $otherUser,
            $book,
            CarbonImmutable::today(),
            ReadingPlanStatus::Reading
        );

        $user->notify(
            new ReadingPlanReminder(
                $userPlan,
                'on_due_date'
            )
        );

        $otherUser->notify(
            new ReadingPlanReminder(
                $otherPlan,
                'on_due_date'
            )
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');

        $response->assertViewHas(
            'notifications',
            function ($notifications) use ($user): bool {
                return $notifications->count() === 1
                    && $notifications->first()
                        ->notifiable_id === $user->id;
            }
        );
    }

    public function test_command_sends_reading_plan_notifications(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $today = CarbonImmutable::today();

        $beforePlan = $this->createReadingPlan(
            $user,
            $book,
            $today->addDays(3),
            ReadingPlanStatus::Reading
        );

        $duePlan = $this->createReadingPlan(
            $user,
            $book,
            $today,
            ReadingPlanStatus::Reading
        );

        $afterPlan = $this->createReadingPlan(
            $user,
            $book,
            $today->subDays(3),
            ReadingPlanStatus::Expired
        );

        $this->artisan('notify:reading-plan')
            ->assertExitCode(0);

        $notifications = $user
            ->notifications()
            ->get();

        $this->assertCount(3, $notifications);

        $timings = $notifications
            ->map(
                fn (DatabaseNotification $notification): string => $notification->data['timing']
            )
            ->all();

        $this->assertEqualsCanonicalizing([
            'three_days_before',
            'on_due_date',
            'three_days_after',
        ], $timings);

        $beforeNotification = $notifications->first(
            fn (DatabaseNotification $notification): bool => $notification->data['timing']
                    === 'three_days_before'
        );

        $this->assertSame(
            $beforePlan->id,
            $beforeNotification->data['reading_plan_id']
        );

        $this->assertSame(
            $book->id,
            $beforeNotification->data['book_id']
        );

        $this->assertSame(
            $today->addDays(3)->toDateString(),
            $beforeNotification->data['target_date']
        );

        $this->assertNotEmpty(
            $beforeNotification->data['title']
        );

        $this->assertNotEmpty(
            $beforeNotification->data['body']
        );
    }

    public function test_command_does_not_send_duplicate_notifications(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->createReadingPlan(
            $user,
            $book,
            CarbonImmutable::today()->addDays(3),
            ReadingPlanStatus::Reading
        );

        $this->artisan('notify:reading-plan')
            ->assertExitCode(0);

        $this->artisan('notify:reading-plan')
            ->assertExitCode(0);

        $this->assertSame(
            1,
            $user->notifications()->count()
        );
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $this->createReadingPlan(
            $user,
            $book,
            CarbonImmutable::today(),
            ReadingPlanStatus::Reading
        );

        $user->notify(
            new ReadingPlanReminder(
                $plan,
                'on_due_date'
            )
        );

        $notification = $user
            ->notifications()
            ->firstOrFail();

        $this->assertNull($notification->read_at);

        $response = $this
            ->actingAs($user)
            ->from(route('notifications.index'))
            ->post(
                route(
                    'notifications.read',
                    $notification->id
                )
            );

        $response->assertRedirect(
            route('notifications.index')
        );

        $response->assertSessionHas(
            'success',
            '通知を既読にしました。'
        );

        $this->assertNotNull(
            $notification->fresh()->read_at
        );
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $otherPlan = $this->createReadingPlan(
            $otherUser,
            $book,
            CarbonImmutable::today(),
            ReadingPlanStatus::Reading
        );

        $otherUser->notify(
            new ReadingPlanReminder(
                $otherPlan,
                'on_due_date'
            )
        );

        $notification = $otherUser
            ->notifications()
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'notifications.read',
                    $notification->id
                )
            );

        $response->assertNotFound();

        $this->assertNull(
            $notification->fresh()->read_at
        );
    }
}
