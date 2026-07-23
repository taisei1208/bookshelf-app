<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'regex:/^[0-9]{13}$/', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'distinct', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者IDを指定してください。',
            'user_id.integer' => '登録者IDの形式が正しくありません。',
            'user_id.exists' => '指定された登録者が存在しません。',
            'title.required' => '書籍タイトルを入力してください。',
            'title.string' => '書籍タイトルは文字列で入力してください。',
            'title.max' => '書籍タイトルは255文字以内で入力してください。',
            'author.required' => '著者名を入力してください。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.string' => 'ISBNは文字列で入力してください。',
            'isbn.regex' => 'ISBNは13桁の半角数字で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',
            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '出版日は有効な日付で入力してください。',
            'description.string' => '書籍説明は文字列で入力してください。',
            'image_url.url' => '画像URLはURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',
            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.array' => 'ジャンルの指定形式が正しくありません。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.*.integer' => 'ジャンルIDの形式が正しくありません。',
            'genres.*.distinct' => '同じジャンルが重複して指定されています。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
