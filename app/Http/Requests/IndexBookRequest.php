<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'integer', 'exists:genres,id'],
            'sort' => ['nullable', 'string', 'in:latest,oldest,title,rating'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * バリデーションメッセージを返す。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',

            'genre.integer' => 'ジャンルの形式が正しくありません。',
            'genre.exists' => '選択されたジャンルが存在しません。',

            'sort.string' => '並び順の形式が正しくありません。',
            'sort.in' => '選択された並び順が正しくありません。',

            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
        ];
    }
}
