<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    /**
     * ISBNからGoogle Books APIの書籍情報を取得する。
     *
     * @return array{
     *     title: string,
     *     author: string,
     *     published_date: string|null,
     *     description: string|null,
     *     image_url: string|null
     * }|null
     */
    public function findByIsbn(string $isbn): ?array
    {
        $response = Http::acceptJson()
            ->timeout(10)
            ->get(
                (string) config('services.google_books.url'),
                [
                    'q' => "isbn:{$isbn}",

                    'key' => (string) config('services.google_books.key'),
                    'maxResults' => 1,
                ]
            );

        $response->throw();

        $volumeInfo = $response->json('items.0.volumeInfo');

        if (! is_array($volumeInfo)) {
            return null;
        }

        $title = data_get($volumeInfo, 'title');

        if (! is_string($title) || trim($title) === '') {
            return null;
        }

        return [
            'title' => $title,

            'author' => implode('、', $volumeInfo['authors'] ?? []),

            'published_date' => $volumeInfo['publishedDate'] ?? null,

            'description' => $volumeInfo['description'] ?? null,

            'image_url' => data_get($volumeInfo, 'imageLinks.thumbnail'),
        ];
    }
}
