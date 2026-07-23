<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new IndexBookRequest;

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
            'keyword' => 'Laravel',
            'genre_id' => $genre->id,
            'page' => 1,
            'per_page' => 20,
        ], $overrides);
    }

    public function test_rules_accept_valid_payload(): void
    {
        $genre = Genre::factory()->create();

        $validator = $this->validator(
            $this->basePayload($genre)
        );

        $this->assertTrue($validator->passes());
    }

    public function test_rules_accept_empty_payload(): void
    {
        $validator = $this->validator([]);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_invalid_values(): void
    {
        $genre = Genre::factory()->create();

        $cases = [
            ['keyword', ['不正'], 'keyword'],
            ['keyword', str_repeat('あ', 256), 'keyword'],
            ['genre_id', '不正', 'genre_id'],
            ['genre_id', 999999, 'genre_id'],
            ['page', '不正', 'page'],
            ['page', 0, 'page'],
            ['per_page', '不正', 'per_page'],
            ['per_page', 0, 'per_page'],
        ];

        foreach ($cases as [$field, $value, $errorField]) {
            $validator = $this->validator(
                $this->basePayload($genre, [
                    $field => $value,
                ])
            );

            $this->assertTrue(
                $validator->fails(),
                "{$field}のバリデーションが成功しています。"
            );

            $this->assertTrue(
                $validator->errors()->has($errorField),
                "{$errorField}にエラーがありません。"
            );
        }
    }
}
