<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BookRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(FormRequest $request, array $data)
    {
        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    private function basePayload(
        Genre $genre,
        array $overrides = []
    ): array {
        return array_merge([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-07-24',
            'description' => '書籍の説明です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    private function updateRequest(Book $book): UpdateBookRequest
    {
        return new class($book) extends UpdateBookRequest
        {
            public function __construct(
                private Book $boundBook
            ) {
                parent::__construct();
            }

            public function route($param = null, $default = null)
            {
                if ($param === 'book') {
                    return $this->boundBook;
                }

                return $default;
            }
        };
    }

    private function requests(Book $book): array
    {
        return [
            new StoreBookRequest,
            $this->updateRequest($book),
        ];
    }

    private function assertAllRequestsFail(
        Book $book,
        array $data,
        string $field
    ): void {
        foreach ($this->requests($book) as $request) {
            $validator = $this->validator($request, $data);

            $this->assertTrue(
                $validator->fails(),
                get_class($request)
            );

            $this->assertTrue(
                $validator->errors()->has($field),
                "{$field}にエラーがありません。"
            );
        }
    }

    public function test_rules_accept_valid_payload(): void
    {
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ($this->requests($book) as $request) {
            $validator = $this->validator(
                $request,
                $this->basePayload($genre)
            );

            $this->assertTrue(
                $validator->passes(),
                get_class($request)
            );
        }
    }

    public function test_rules_reject_invalid_title(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ([
            null,
            ['不正な形式'],
            str_repeat('あ', 256),
        ] as $title) {
            $this->assertAllRequestsFail(
                $book,
                $this->basePayload($genre, [
                    'title' => $title,
                ]),
                'title'
            );
        }
    }

    public function test_rules_reject_invalid_author(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ([
            null,
            ['不正な形式'],
            str_repeat('あ', 256),
        ] as $author) {
            $this->assertAllRequestsFail(
                $book,
                $this->basePayload($genre, [
                    'author' => $author,
                ]),
                'author'
            );
        }
    }

    public function test_rules_reject_invalid_isbn(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ([
            null,
            1234567890123,
            '123456789012',
            '12345678901234',
            'ABCDEFGHIJKLM',
        ] as $isbn) {
            $this->assertAllRequestsFail(
                $book,
                $this->basePayload($genre, [
                    'isbn' => $isbn,
                ]),
                'isbn'
            );
        }
    }

    public function test_rules_reject_invalid_published_date(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ([null, '日付ではありません'] as $date) {
            $this->assertAllRequestsFail(
                $book,
                $this->basePayload($genre, [
                    'published_date' => $date,
                ]),
                'published_date'
            );
        }
    }

    public function test_description_can_be_null(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ($this->requests($book) as $request) {
            $validator = $this->validator(
                $request,
                $this->basePayload($genre, [
                    'description' => null,
                ])
            );

            $this->assertTrue($validator->passes());
        }
    }

    public function test_rules_reject_non_string_description(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $this->assertAllRequestsFail(
            $book,
            $this->basePayload($genre, [
                'description' => ['不正な形式'],
            ]),
            'description'
        );
    }

    public function test_image_url_can_be_null(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ($this->requests($book) as $request) {
            $validator = $this->validator(
                $request,
                $this->basePayload($genre, [
                    'image_url' => null,
                ])
            );

            $this->assertTrue($validator->passes());
        }
    }

    public function test_rules_reject_invalid_image_url(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        foreach ([
            'URLではありません',
            'https://example.com/'.str_repeat('a', 240),
        ] as $imageUrl) {
            $this->assertAllRequestsFail(
                $book,
                $this->basePayload($genre, [
                    'image_url' => $imageUrl,
                ]),
                'image_url'
            );
        }
    }

    public function test_rules_reject_invalid_genres(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $cases = [
            [null, 'genres'],
            ['不正な形式', 'genres'],
            [[], 'genres'],
            [['abc'], 'genres.0'],
            [[$genre->id, $genre->id], 'genres.*'],
            [[999999], 'genres.0'],
        ];

        foreach ($cases as [$genres, $errorField]) {
            $this->assertAllRequestsFail(
                $book,
                $this->basePayload($genre, [
                    'genres' => $genres,
                ]),
                $errorField
            );
        }
    }

    public function test_store_rules_reject_duplicate_isbn(): void
    {
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $validator = $this->validator(
            new StoreBookRequest,
            $this->basePayload($genre, [
                'isbn' => '1111111111111',
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
        );
    }

    public function test_update_rules_accept_current_isbn(): void
    {
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        $validator = $this->validator(
            $this->updateRequest($book),
            $this->basePayload($genre, [
                'isbn' => '1111111111111',
            ])
        );

        $this->assertTrue($validator->passes());
    }

    public function test_update_rules_reject_another_book_isbn(): void
    {
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'isbn' => '1111111111111',
        ]);

        Book::factory()->create([
            'isbn' => '3333333333333',
        ]);

        $validator = $this->validator(
            $this->updateRequest($book),
            $this->basePayload($genre, [
                'isbn' => '3333333333333',
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
        );
    }
}
