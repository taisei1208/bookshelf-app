<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ReadingPlan $readingPlan, public string $timing)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'timing' => $this->timing,
            'title' => $this->makeTitle(),
            'body' => $this->makeBody(),
            'target_date' => $this->readingPlan
                ->target_date
                ->toDateString(),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    /**
     * 通知タイミングに応じたタイトルを作成する。
     */
    private function makeTitle(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書期限が近づいています',
            'on_due_date' => '本日が読書期限です',
            'three_days_after' => '読書期限を過ぎています',
            default => '読書計画のお知らせ',
        };
    }

    /**
     * 通知タイミングに応じた本文を作成する。
     */
    private function makeBody(): string
    {
        $bookTitle = $this->readingPlan->book->title;

        return match ($this->timing) {
            'three_days_before' => "「{$bookTitle}」の読書期限まであと3日です。",

            'on_due_date' => "「{$bookTitle}」は本日が読書期限です。",

            'three_days_after' => "「{$bookTitle}」の読書期限を3日過ぎています。",

            default => "「{$bookTitle}」の読書計画を確認してください。",
        };
    }
}
