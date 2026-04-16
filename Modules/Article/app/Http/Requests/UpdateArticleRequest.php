<?php

declare(strict_types=1);

namespace Modules\Article\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'array'],
            'title.*' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['sometimes', 'array'],
            'slug.*' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'summary' => ['nullable', 'array'],
            'summary.*' => ['nullable', 'string', 'max:500'],
            'body' => ['sometimes', 'array'],
            'body.*' => ['required', 'string', 'min:10'],
            'status' => ['sometimes', 'string', 'in:draft,published,archived'],
            'read_time' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_featured' => ['sometimes', 'boolean'],
            'allow_comments' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['required', 'integer', 'exists:categories,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['required', 'integer', 'exists:tags,id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('article-module::messages.validation_failed'),
            ),
        );
    }
}
