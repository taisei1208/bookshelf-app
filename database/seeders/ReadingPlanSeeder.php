<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', [
                'yamada@example.com',
                'suzuki@example.com',
                'tanaka@example.com',
            ])
            ->get()
            ->keyBy('email');

        $books = Book::query()->get()->keyBy('isbn');

        $readingPlans = [
            // Three-days-before reminder target.
            [
                'user_id' => $users['yamada@example.com']->id,
                'book_id' => $books['9784101010014']->id,
                'target_date' => Carbon::today()->addDays(3),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],
            // Due-date reminder target.
            [
                'user_id' => $users['yamada@example.com']->id,
                'book_id' => $books['9784422100524']->id,
                'target_date' => Carbon::today(),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],
            // Automatic-expiration target: intentionally past due while still reading.
            [
                'user_id' => $users['yamada@example.com']->id,
                'book_id' => $books['9784873115658']->id,
                'target_date' => Carbon::today()->subDay(),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],
            // Three-days-after reminder target.
            [
                'user_id' => $users['yamada@example.com']->id,
                'book_id' => $books['9784863940246']->id,
                'target_date' => Carbon::today()->subDays(3),
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,
            ],
            // Completed plan: excluded from expiration and reminders.
            [
                'user_id' => $users['yamada@example.com']->id,
                'book_id' => $books['9784101010021']->id,
                'target_date' => Carbon::today()->subDays(10),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => Carbon::today()->subDays(8),
            ],
            // A second plan for the same user and book verifies rereading support.
            [
                'user_id' => $users['yamada@example.com']->id,
                'book_id' => $books['9784101010021']->id,
                'target_date' => Carbon::today()->addDays(14),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],
            // Other users' plans verify authorization boundaries.
            [
                'user_id' => $users['suzuki@example.com']->id,
                'book_id' => $books['9784101010014']->id,
                'target_date' => Carbon::today()->addDays(5),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],
            [
                'user_id' => $users['tanaka@example.com']->id,
                'book_id' => $books['9784422100524']->id,
                'target_date' => Carbon::today()->subDays(7),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => Carbon::today()->subDays(5),
            ],
        ];

        collect($readingPlans)->each(
            fn (array $readingPlan): ReadingPlan => ReadingPlan::query()->create($readingPlan)
        );
    }
}
