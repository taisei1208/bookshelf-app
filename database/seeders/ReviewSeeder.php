<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * 各書籍に2〜4件のレビューを登録する。
     */
    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', [
                'yamada@example.com',
                'suzuki@example.com',
                'tanaka@example.com',
                'sato@example.com',
                'takahashi@example.com',
            ])
            ->get();

        /** @var array<int, string> $commentTemplates */
        $commentTemplates = [
            1 => '期待していた内容とは異なりました。',
            2 => '少し物足りなさを感じました。',
            3 => '読みやすく、参考になる内容でした。',
            4 => 'とても面白く、学びの多い一冊でした。',
            5 => '非常に素晴らしく、何度も読み返したい一冊です。',
        ];

        $ratings = collect(range(1, 5))->shuffle();
        $reviewIndex = 0;

        Book::query()
            ->get()
            ->each(function (Book $book) use (
                $users,
                $commentTemplates,
                $ratings,
                &$reviewIndex
            ): void {
                $reviewerCount = random_int(2, 4);

                $users
                    ->random($reviewerCount)
                    ->each(function (User $user) use (
                        $book,
                        $commentTemplates,
                        $ratings,
                        &$reviewIndex
                    ): void {
                        $rating = $ratings[
                            $reviewIndex % $ratings->count()
                        ];

                        $reviewIndex++;

                        Review::query()->create([
                            'user_id' => $user->id,
                            'book_id' => $book->id,
                            'rating' => $rating,
                            'comment' => $commentTemplates[$rating],
                        ]);
                    });
            });
    }
}
