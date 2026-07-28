<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Reading = 'reading';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * 画面表示用の日本語表示
     */
    public function label(): string
    {
        return match ($this) {
            self::Reading => '読書中',
            self::Completed => '読了',
            self::Expired => '期限切れ',
        };
    }
}
