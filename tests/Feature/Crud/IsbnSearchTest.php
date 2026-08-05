<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用のGoogle Books API設定を準備する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google_books.url' => 'https://www.googleapis.com/books/v1/volumes',

            'services.google_books.key' => 'test-google-books-api-key',
        ]);
    }

    /**
     * 未認証ユーザーはISBN検索を利用できない。
     */
    public function test_guest_cannot_search_book_by_isbn(): void
    {
        Http::fake();

        $response = $this->getJson(
            route('books.isbn', [
                'isbn' => '9784101010014',
            ])
        );

        $response->assertUnauthorized();

        Http::assertNothingSent();
    }

    /**
     * ISBNから書籍情報を取得できる。
     */
    public function test_authenticated_user_can_search_book_by_isbn(): void
    {
        $user = User::factory()->create();
        $isbn = '9784101010014';

        Http::fake([
            '*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '吾輩は猫である',
                            'authors' => [
                                '夏目漱石',
                            ],
                            'publishedDate' => '2003-06-01',
                            'description' => '猫の視点から描かれた小説です。',
                            'imageLinks' => [
                                'thumbnail' => 'http://example.com/book.jpg',
                            ],
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => $isbn,
                                ],
                                [
                                    'type' => 'ISBN_10',
                                    'identifier' => '4101010013',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(
                route('books.isbn', [
                    'isbn' => $isbn,
                ])
            );

        $response
            ->assertOk()
            ->assertJson([
                'message' => '書籍情報を取得しました。',
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'published_date' => '2003-06-01',
                'description' => '猫の視点から描かれた小説です。',
                'image_url' => 'http://example.com/book.jpg',
            ]);

        Http::assertSent(
            function (Request $request) use ($isbn): bool {
                $queryString = parse_url(
                    $request->url(),
                    PHP_URL_QUERY
                );

                parse_str(
                    is_string($queryString) ? $queryString : '',
                    $query
                );

                return $request['q' ?? null] === "isbn:{$isbn}" && (int) ($query['maxResults'] ?? 0) === 1;
            }
        );
    }

    /**
     * ISBNが空の場合はバリデーションエラーになる。
     */
    public function test_isbn_is_required(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn'));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isbn')
            ->assertJsonPath(
                'errors.isbn.0',
                'ISBNを入力してください。'
            );

        Http::assertNothingSent();
    }

    /**
     * ISBNが13桁の数字でない場合はエラーになる。
     */
    public function test_isbn_must_be_thirteen_digits(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this
            ->actingAs($user)
            ->getJson(
                route('books.isbn', [
                    'isbn' => '1234567',
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isbn')
            ->assertJsonPath(
                'errors.isbn.0',
                'ISBNは13桁の半角数字で入力してください。'
            );

        Http::assertNothingSent();
    }

    /**
     * タイトルが取得できない場合は404を返す。
     */
    public function test_it_returns_not_found_when_title_is_missing(): void
    {
        $user = User::factory()->create();
        $isbn = '9784101010014';

        Http::fake([
            '*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'authors' => [
                                '夏目漱石',
                            ],
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => $isbn,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(
                route('books.isbn', [
                    'isbn' => $isbn,
                ])
            );

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => '該当する書籍が見つかりませんでした。',
            ]);
    }

    /**
     * Google Books APIでエラーが発生した場合は502を返す。
     */
    public function test_it_returns_bad_gateway_when_google_api_fails(): void
    {
        $user = User::factory()->create();

        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Google Books API error',
                ],
            ], 500),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(
                route('books.isbn', [
                    'isbn' => '9784101010014',
                ])
            );

        $response
            ->assertStatus(502)
            ->assertJson([
                'message' =>
                    '書籍情報の取得に失敗しました。',
            ]);
    }
}
