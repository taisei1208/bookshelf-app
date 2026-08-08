<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendReadingPlanNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:reading-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画のリマインダー通知を実行する。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = CarbonImmutable::today();

        $conditions = collect([
            [
                'target_date' => $today->addDays(3),
                'status' => ReadingPlanStatus::Reading,
                'timing' => 'three_days_before',
            ],
            [
                'target_date' => $today,
                'status' => ReadingPlanStatus::Reading,
                'timing' => 'on_due_date',
            ],
            [
                'target_date' => $today->subDays(3),
                'status' => ReadingPlanStatus::Expired,
                'timing' => 'three_days_after',
            ],
        ]);

        $conditions->each(function ($condition): void {
            ReadingPlan::query()
                ->with(['user', 'book'])
                ->whereDate('target_date', $condition['target_date'])
                ->where('status', $condition['status'])
                ->get()
                ->each(function (ReadingPlan $plan) use ($condition) {
                    $targetDate = $plan->target_date->toDateString();

                    $alreadySent = $plan->user
                        ->notifications()
                        ->where('data->reading_plan_id', $plan->id)
                        ->where('data->timing', $condition['timing'])
                        ->where('data->target_date', $targetDate)
                        ->exists();

                    if ($alreadySent) {
                        return;
                    }

                    $plan->user->notify(new ReadingPlanReminder($plan, $condition['timing']));
                });
        });

        $this->info('読書計画のリマインダー通知を送信しました。');

        return self::SUCCESS;
    }
}
