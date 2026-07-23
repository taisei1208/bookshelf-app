<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
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
        User $user,
        Genre $genre,
        array $overrides = []
    ): array {
        return array_merge([
            'user_id' => $user->id,
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
        string $errorField
    ): void {
        foreach ($this->requests($book) as $request) {
            $validator = $this->validator($request, $data);

            $this->assertTrue(
                $validator->fails(),
                get_class($request)
            );

            $this->assertTrue(
                $validator->errors()->has($errorField),
                "{$errorField}にエラーがありません。"
            );
        }
    }

    public function test_rules_accept_valid_payload(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '1111111111111',
            ]);

        foreach ($this->requests($book) as $request) {
            $validator = $this->validator(
                $request,
                $this->basePayload($user, $genre)
            );

            $this->assertTrue(
                $validator->passes(),
                get_class($request)
            );
        }
    }

    public function test_rules_accept_null_optional_fields(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '1111111111111',
            ]);

        $payload = $this->basePayload($user, $genre, [
            'description' => null,
            'image_url' => null,
        ]);

        foreach ($this->requests($book) as $request) {
            $validator = $this->validator($request, $payload);

            $this->assertTrue($validator->passes());
        }
    }

    public function test_rules_reject_invalid_values(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '1111111111111',
            ]);

        $cases = [
            ['user_id', null, 'user_id'],
            ['user_id', '不正', 'user_id'],
            ['user_id', 999999, 'user_id'],

            ['title', null, 'title'],
            ['title', ['不正'], 'title'],
            ['title', str_repeat('あ', 256), 'title'],

            ['author', null, 'author'],
            ['author', ['不正'], 'author'],
            ['author', str_repeat('あ', 256), 'author'],

            ['isbn', null, 'isbn'],
            ['isbn', 123, 'isbn'],
            ['isbn', '123456789012', 'isbn'],
            ['isbn', 'ABCDEFGHIJKLM', 'isbn'],

            ['published_date', null, 'published_date'],
            ['published_date', '日付ではありません', 'published_date'],

            ['description', ['不正'], 'description'],

            ['image_url', 'URLではありません', 'image_url'],
            [
                'image_url',
                'https://example.com/'.str_repeat('a', 240),
                'image_url',
            ],

            ['genres', null, 'genres'],
            ['genres', '不正', 'genres'],
            ['genres', [], 'genres'],
            ['genres', ['不正'], 'genres.0'],
            ['genres', [$genre->id, $genre->id], 'genres.*'],
            ['genres', [999999], 'genres.0'],
        ];

        foreach ($cases as [$field, $value, $errorField]) {
            $payload = $this->basePayload(
                $user,
                $genre,
                [$field => $value]
            );

            $this->assertAllRequestsFail(
                $book,
                $payload,
                $errorField
            );
        }
    }

    public function test_store_rules_reject_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()
            ->for($user)
            ->create([
                'isbn' => '1111111111111',
            ]);

        $validator = $this->validator(
            new StoreBookRequest,
            $this->basePayload($user, $genre, [
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
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '1111111111111',
            ]);

        $validator = $this->validator(
            $this->updateRequest($book),
            $this->basePayload($user, $genre, [
                'isbn' => '1111111111111',
            ])
        );

        $this->assertTrue($validator->passes());
    }

    public function test_update_rules_reject_another_book_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create([
                'isbn' => '1111111111111',
            ]);

        Book::factory()
            ->for($user)
            ->create([
                'isbn' => '3333333333333',
            ]);

        $validator = $this->validator(
            $this->updateRequest($book),
            $this->basePayload($user, $genre, [
                'isbn' => '3333333333333',
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
        );
    }
}
