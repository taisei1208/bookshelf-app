<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GenreRequestTest extends TestCase
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

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '新しいジャンル',
        ], $overrides);
    }

    private function updateRequest(Genre $genre): UpdateGenreRequest
    {
        return new class($genre) extends UpdateGenreRequest
        {
            public function __construct(
                private Genre $boundGenre
            ) {
                parent::__construct();
            }

            public function route($param = null, $default = null)
            {
                if ($param === 'genre') {
                    return $this->boundGenre;
                }

                return $default;
            }
        };
    }

    private function requests(Genre $genre)
    {
        return [
            new StoreGenreRequest,
            $this->updateRequest($genre),
        ];
    }

    private function assertAllRequestsFail(Genre $genre, array $data, string $field)
    {
        foreach ($this->requests($genre) as $request) {
            $validator = $this->validator($request, $data);

            $this->assertTrue($validator->fails());

            $this->assertArrayHasKey(
                $field,
                $validator->errors()->messages()
            );
        }
    }

    public function test_rules_accept_valid_payload()
    {
        $genre = Genre::factory()->create([
            'name' => '既存のジャンル',
        ]);

        foreach ($this->requests($genre) as $request) {
            $validator = $this->validator(
                $request,
                $this->basePayload()
            );

            $this->assertTrue($validator->passes());
        }
    }

    public function test_rules_reject_missing_name(): void
    {
        $genre = Genre::factory()->create([
            'name' => '既存ジャンル',
        ]);

        $payload = $this->basePayload();
        unset($payload['name']);

        $this->assertAllRequestsFail(
            $genre,
            $payload,
            'name'
        );
    }

    public function test_rules_reject_non_string_name(): void
    {
        $genre = Genre::factory()->create([
            'name' => '既存ジャンル',
        ]);

        $this->assertAllRequestsFail(
            $genre,
            $this->basePayload([
                'name' => ['不正な形式'],
            ]),
            'name'
        );
    }

    public function test_rules_reject_name_longer_than_50_characters(): void
    {
        $genre = Genre::factory()->create([
            'name' => '既存ジャンル',
        ]);

        $this->assertAllRequestsFail(
            $genre,
            $this->basePayload([
                'name' => str_repeat('あ', 51),
            ]),
            'name'
        );
    }

    public function test_store_rules_reject_duplicate_name(): void
    {
        Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $validator = $this->validator(
            new StoreGenreRequest,
            $this->basePayload([
                'name' => 'ミステリー',
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'name',
            $validator->errors()->messages()
        );
    }

    public function test_update_rules_accept_current_genre_name(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $validator = $this->validator(
            $this->updateRequest($genre),
            $this->basePayload([
                'name' => 'ミステリー',
            ])
        );

        $this->assertTrue($validator->passes());
    }

    public function test_update_rules_reject_another_genre_name(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $validator = $this->validator(
            $this->updateRequest($genre),
            $this->basePayload([
                'name' => 'ファンタジー',
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'name',
            $validator->errors()->messages()
        );
    }
}
