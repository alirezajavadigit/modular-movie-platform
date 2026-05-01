<?php

declare(strict_types=1);

namespace Modules\Tag\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'array'],
            'name.*'        => ['required', 'string', 'min:2', 'max:100'],
            'slug'          => ['sometimes', 'array'],
            'slug.*'        => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description'   => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:500'],
            'color'         => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('tag::messages.validation_failed'),
            ),
        );
    }
}
