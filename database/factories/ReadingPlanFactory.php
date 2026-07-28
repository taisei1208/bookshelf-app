<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'target_date' => fake()->dateTimeBetween(
                'today',
                '+1 month'
            ),
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ];
    }

    /**
     * 読了済みの状態に設定する
     */
    public function completed(): static
    {
        return $this->status(
            fn (array $attributes): array => [
                'target_date' => today()->subDays(2),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => today()->subDay(),
            ]
        );
    }

    /**
     * 期限切れの状態を設定する。
     */
    public function expired(): static
    {
        return $this->state(
            fn (array $attributes): array => [
                'target_date' => today()->subDay(),
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,
            ]
        );
    }
}
