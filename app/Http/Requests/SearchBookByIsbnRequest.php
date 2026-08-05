<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchBookByIsbnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * URLのISBNをバリデーション対象へ追加する。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => $this->route('isbn'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'regex:/^[0-9]{13}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.string' => 'ISBNは文字列で入力してください。',
            'isbn.regex' => 'ISBNは13桁の半角数字で入力してください。',
        ];
    }
}
