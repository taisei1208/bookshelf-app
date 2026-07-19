<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = Book::all()->keyBy('isbn');

        $favorites = [
            'yamada@example.com' => [
                '9784101010014',
                '9784873115658',
                '9784309226712',
                '9784822289607',
            ],
            'suzuki@example.com' => [
                '9784422100524',
                '9784863940246',
                '9784478025819',
            ],
            'tanaka@example.com' => [
                '9784873115658',
                '9784048930598',
                '9784822251468',
                '9784822289607',
            ],
            'sato@example.com' => [
                '9784101010021',
                '9784163902302',
                '9784309226712',
                '9784048930598',
                '9784478025819',
            ],
            'takahashi@example.com' => [
                '9784101010014',
                '9784422100524',
                '9784822251468',
            ],
        ];

        foreach ($favorites as $email => $isbnList) {
            $user = User::where('email', $email)->first();

            $bookIds = collect($isbnList)
                ->map(fn ($isbn) => $books[$isbn]->id)
                ->toArray();

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
