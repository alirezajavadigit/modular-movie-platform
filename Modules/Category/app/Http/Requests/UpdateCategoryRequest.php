<?php

declare(strict_types=1);

namespace Modules\Category\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'array'],
            'name.*'        => ['required', 'string', 'min:2', 'max:255'],
            'slug'          => ['sometimes', 'array'],
            'slug.*'        => ['required', 'string', 'min:2', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description'   => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:1000'],
            'parent_id'     => ['nullable', 'integer', 'exists:categories,id'],
            'is_active'     => ['sometimes', 'boolean'],
            'order'         => ['sometimes', 'integer', 'min:0', 'max:99999'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('category::messages.validation_failed'),
            ),
        );
    }
}
