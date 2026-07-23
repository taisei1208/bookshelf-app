<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ReviewRequestTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    private function requestClasses(): array
    {
        return [
            StoreReviewRequest::class,
            UpdateReviewRequest::class,
        ];
    }

    private function validator(string $requestClass, array $data)
    {
        $request = new $requestClass;

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages(),
        );
    }

    private function basePayload(array $overrides = [])
    {
        return array_merge([
            'rating' => 5,
            'comment' => '面白い本でした。',
        ], $overrides);
    }

    private function assertAllRequestsPass(array $data)
    {
        foreach ($this->requestClasses() as $requestClass) {
            $validator = $this->validator($requestClass, $data);

            $this->assertTrue(
                $validator->passes(),
                "{$requestClass}のバリデーションに失敗しました。"
            );
        }
    }

    private function assertAllRequestsFail(array $data, string $field)
    {
        foreach ($this->requestClasses() as $requestClass) {
            $validator = $this->validator($requestClass, $data);

            $this->assertTrue(
                $validator->fails(),
                "{$requestClass}のバリデーションに成功していました。"
            );

            $this->assertArrayHasKey(
                $field,
                $validator->errors()->messages()
            );
        }
    }

    public function test_rules_accept_valid_payload(): void
    {
        $this->assertAllRequestsPass(
            $this->basePayload()
        );
    }

    public function test_rules_accept_null_comment(): void
    {
        $this->assertAllRequestsPass(
            $this->basePayload([
                'comment' => null,
            ])
        );
    }

    public function test_rules_reject_missing_rating(): void
    {
        $payload = $this->basePayload();

        unset($payload['rating']);

        $this->assertAllRequestsFail($payload, 'rating');
    }

    public function test_rules_reject_non_integer_rating(): void
    {
        $this->assertAllRequestsFail(
            $this->basePayload([
                'rating' => '高評価',
            ]),
            'rating'
        );
    }

    public function test_rules_reject_rating_outside_valid_range(): void
    {
        foreach ([0, 6] as $rating) {
            $this->assertAllRequestsFail(
                $this->basePayload([
                    'rating' => $rating,
                ]),
                'rating'
            );
        }
    }

    public function test_rules_reject_non_string_comment(): void
    {
        $this->assertAllRequestsFail(
            $this->basePayload([
                'comment' => ['不正な形式'],
            ]),
            'comment'
        );
    }
}
