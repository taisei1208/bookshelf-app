<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ReadingPlanExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:reading-plan-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期限超過のステータスを更新する。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', '<', CarbonImmutable::today())
            ->update(['status' => ReadingPlanStatus::Expired]);

        $this->info('読書計画を期限切れに更新しました。');

        return self::SUCCESS;
    }
}
