<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IndexBookRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexBookRequestTest extends TestCase
{
    use RefreshDatabase;
    private function validator(array $data)
    {
        $request = new IndexBookRequest();

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    public function test_rules_accept_empty_conditions(): void
    {
        $validator = $this->validator([]);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_accept_valid_conditions(): void
    {
        $genre = Genre::factory()->create();

        foreach ([
            'latest',
            'oldest',
            'title',
            'rating',
        ] as $sort) {
            $validator = $this->validator([
                'keyword' => 'Laravel',
                'genre' => $genre->id,
                'sort' => $sort,
                'page' => 1,
            ]);

            $this->assertTrue(
                $validator->passes(),
                "{$sort}がバリデーションエラーになりました。"
            );
        }
    }

    public function test_rules_reject_invalid_keyword(): void
    {
        foreach ([
            ['不正な配列'],
            str_repeat('あ', 256),
        ] as $keyword) {
            $validator = $this->validator([
                'keyword' => $keyword,
            ]);

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('keyword')
            );
        }
    }

    public function test_rules_reject_invalid_genre(): void
    {
        foreach ([
            '整数ではない値',
            999999,
        ] as $genre) {
            $validator = $this->validator([
                'genre' => $genre,
            ]);

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('genre')
            );
        }
    }

    public function test_rules_reject_invalid_sort(): void
    {
        $validator = $this->validator([
            'sort' => 'invalid',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('sort')
        );
    }

    public function test_rules_reject_invalid_page(): void
    {
        foreach ([
            '整数ではない値',
            0,
            -1,
        ] as $page) {
            $validator = $this->validator([
                'page' => $page,
            ]);

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('page')
            );
        }
    }
}
