<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereIn('email', [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ])->get()->keyBy('email');

        $books = Book::all()->keyBy('isbn');

        $reviews = [
            ['email' => 'suzuki@example.com', 'isbn' => '9784101010014', 'rating' => 5, 'comment' => '独特な語り口が面白く、最後まで楽しく読めました。'],
            ['email' => 'tanaka@example.com', 'isbn' => '9784101010014', 'rating' => 4, 'comment' => '時代背景を感じながら読める作品でした。'],
            ['email' => 'sato@example.com', 'isbn' => '9784101010014', 'rating' => 4, 'comment' => '猫の視点で描かれる人間観察が印象的でした。'],

            ['email' => 'yamada@example.com', 'isbn' => '9784422100524', 'rating' => 5, 'comment' => '人間関係を見直すきっかけになる本でした。'],
            ['email' => 'tanaka@example.com', 'isbn' => '9784422100524', 'rating' => 4, 'comment' => '仕事でも日常でも使える考え方が多かったです。'],
            ['email' => 'takahashi@example.com', 'isbn' => '9784422100524', 'rating' => 5, 'comment' => '何度も読み返したい内容です。'],

            ['email' => 'yamada@example.com', 'isbn' => '9784873115658', 'rating' => 5, 'comment' => 'コードを書く上で大切な視点が学べました。'],
            ['email' => 'suzuki@example.com', 'isbn' => '9784873115658', 'rating' => 4, 'comment' => '実務でも意識したい内容が多かったです。'],
            ['email' => 'sato@example.com', 'isbn' => '9784873115658', 'rating' => 5, 'comment' => '初心者にも読みやすい技術書だと思います。'],

            ['email' => 'yamada@example.com', 'isbn' => '9784863940246', 'rating' => 4, 'comment' => '自分の行動を見直すきっかけになりました。'],
            ['email' => 'suzuki@example.com', 'isbn' => '9784863940246', 'rating' => 5, 'comment' => '考え方を整理するのに役立つ本です。'],
            ['email' => 'tanaka@example.com', 'isbn' => '9784863940246', 'rating' => 4, 'comment' => 'ビジネスだけでなく生活にも活かせそうです。'],

            ['email' => 'suzuki@example.com', 'isbn' => '9784101010021', 'rating' => 4, 'comment' => 'テンポが良く読みやすい作品でした。'],
            ['email' => 'sato@example.com', 'isbn' => '9784101010021', 'rating' => 5, 'comment' => '主人公のまっすぐさが印象に残りました。'],
            ['email' => 'takahashi@example.com', 'isbn' => '9784101010021', 'rating' => 4, 'comment' => '昔の作品ですが今読んでも面白いです。'],

            ['email' => 'yamada@example.com', 'isbn' => '9784309226712', 'rating' => 5, 'comment' => '人類史を大きな視点で理解できました。'],
            ['email' => 'tanaka@example.com', 'isbn' => '9784309226712', 'rating' => 5, 'comment' => '知的好奇心を刺激される内容でした。'],
            ['email' => 'takahashi@example.com', 'isbn' => '9784309226712', 'rating' => 4, 'comment' => '歴史と科学のつながりが面白かったです。'],

            ['email' => 'suzuki@example.com', 'isbn' => '9784048930598', 'rating' => 5, 'comment' => '良いコードを書くための基準が学べました。'],
            ['email' => 'tanaka@example.com', 'isbn' => '9784048930598', 'rating' => 4, 'comment' => '少し難しい部分もありましたが勉強になりました。'],
            ['email' => 'sato@example.com', 'isbn' => '9784048930598', 'rating' => 5, 'comment' => '開発者なら読んでおきたい一冊です。'],

            ['email' => 'yamada@example.com', 'isbn' => '9784478025819', 'rating' => 4, 'comment' => '考え方がシンプルで分かりやすかったです。'],
            ['email' => 'suzuki@example.com', 'isbn' => '9784478025819', 'rating' => 4, 'comment' => '対話形式なので読み進めやすかったです。'],
            ['email' => 'takahashi@example.com', 'isbn' => '9784478025819', 'rating' => 5, 'comment' => '前向きな気持ちになれる本でした。'],

            ['email' => 'tanaka@example.com', 'isbn' => '9784163902302', 'rating' => 4, 'comment' => '芸人の世界のリアルさが伝わってきました。'],
            ['email' => 'sato@example.com', 'isbn' => '9784163902302', 'rating' => 5, 'comment' => '繊細な描写がとても印象的でした。'],
            ['email' => 'takahashi@example.com', 'isbn' => '9784163902302', 'rating' => 4, 'comment' => '短めで読みやすく、余韻が残りました。'],

            ['email' => 'yamada@example.com', 'isbn' => '9784822289607', 'rating' => 5, 'comment' => '思い込みを見直すきっかけになりました。'],
            ['email' => 'suzuki@example.com', 'isbn' => '9784822289607', 'rating' => 5, 'comment' => 'データを見る大切さを学べる本です。'],
            ['email' => 'sato@example.com', 'isbn' => '9784822289607', 'rating' => 4, 'comment' => '世界の見方が変わる内容でした。'],

            ['email' => 'tanaka@example.com', 'isbn' => '9784822251468', 'rating' => 4, 'comment' => '物流の歴史が分かりやすくまとまっています。'],
            ['email' => 'takahashi@example.com', 'isbn' => '9784822251468', 'rating' => 4, 'comment' => 'ビジネスと歴史の両面から楽しめました。'],
        ];

        foreach ($reviews as $review) {
            Review::create([
                'user_id' => $users[$review['email']]->id,
                'book_id' => $books[$review['isbn']]->id,
                'rating' => $review['rating'],
                'comment' => $review['comment'],
            ]);
        }
    }
}
