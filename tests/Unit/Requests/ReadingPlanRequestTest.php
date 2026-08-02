<?php

namespace Tests\Unit\Requests;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ReadingPlanRequestTest extends TestCase
{
    use RefreshDatabase;
    private function validator(FormRequest $request, array $data): ValidatorContract
    {
        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    private function storePayload(Book $book, array $overrides = []): array
    {
        return array_merge([
            'book_id' => $book->id,
            'target_date' => today()
                ->addDays(7)
                ->format('Y-m-d'),
        ], $overrides);
    }

    public function test_index_rules_accept_all_reading_plan_statuses(): void
    {
        foreach (ReadingPlanStatus::cases() as $status) {
            $validator = $this->validator(
                new IndexReadingPlanRequest(),
                [
                    'status' => $status->value,
                ]
            );

            $this->assertTrue(
                $validator->passes(),
                "{$status->value}が拒否されました。"
            );
        }
    }
    public function test_index_rules_accept_missing_status(): void
    {
        $validator = $this->validator(
            new IndexReadingPlanRequest(),
            []
        );

        $this->assertTrue($validator->passes());
    }

    public function test_index_rules_reject_invalid_status(): void
    {
        $validator = $this->validator(
            new IndexReadingPlanRequest(),
            [
                'status' => 'invalid-status',
            ]
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'status',
            $validator->errors()->messages()
        );
    }

    public function test_store_rules_accept_valid_payload(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator(
            new StoreReadingPlanRequest(),
            $this->storePayload($book)
        );

        $this->assertTrue($validator->passes());
    }

    public function test_store_rules_reject_missing_book_id(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator(
            new StoreReadingPlanRequest(),
            $this->storePayload($book, [
                'book_id' => null,
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'book_id',
            $validator->errors()->messages()
        );
    }

    public function test_store_rules_reject_nonexistent_book_id(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator(
            new StoreReadingPlanRequest(),
            $this->storePayload($book, [
                'book_id' => 999999,
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'book_id',
            $validator->errors()->messages()
        );
    }

    public function test_store_rules_reject_missing_target_date(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator(
            new StoreReadingPlanRequest(),
            $this->storePayload($book, [
                'target_date' => null,
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'target_date',
            $validator->errors()->messages()
        );
    }

    public function test_store_rules_reject_past_target_date(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator(
            new StoreReadingPlanRequest(),
            $this->storePayload($book, [
                'target_date' => today()
                    ->subDay()
                    ->format('Y-m-d'),
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'target_date',
            $validator->errors()->messages()
        );
    }

    public function test_store_rules_allow_duplicate_book_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create();

        $validator = $this->validator(
            new StoreReadingPlanRequest(),
            $this->storePayload($book)
        );

        $this->assertTrue($validator->passes());
    }

    public function test_update_rules_accept_valid_target_date(): void
    {
        $validator = $this->validator(
            new UpdateReadingPlanRequest(),
            [
                'target_date' => today()
                    ->addDays(10)
                    ->format('Y-m-d'),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_update_rules_reject_missing_target_date(): void
    {
        $validator = $this->validator(
            new UpdateReadingPlanRequest(),
            [
                'target_date' => null,
            ]
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'target_date',
            $validator->errors()->messages()
        );
    }

    public function test_update_rules_reject_past_target_date(): void
    {
        $validator = $this->validator(
            new UpdateReadingPlanRequest(),
            [
                'target_date' => today()
                    ->subDay()
                    ->format('Y-m-d'),
            ]
        );

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'target_date',
            $validator->errors()->messages()
        );
    }
}
