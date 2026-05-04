<?php

declare(strict_types=1);

namespace Modules\Favorite\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'favoriteable_type' => [
                'required',
                'string',
                Rule::in(array_keys(config('favorite.favoritable_models', []))),
            ],
            'favoriteable_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'favoriteable_type.in' => 'The provided model type is not supported.',
        ];
    }

    public function resolvedType(): string
    {
        return config('favorite.favoritable_models')[$this->string('favoriteable_type')->value()];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('favorite::messages.validation_failed'),
            ),
        );
    }
}
