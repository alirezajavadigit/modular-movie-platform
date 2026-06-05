<?php

declare(strict_types=1);

namespace Modules\Person\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Modules\Person\Enums\Gender;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('person');

        return [
            'first_name'           => ['sometimes', 'array'],
            'first_name.*'         => ['required', 'string', 'min:1', 'max:100'],
            'last_name'            => ['sometimes', 'array'],
            'last_name.*'          => ['required', 'string', 'min:1', 'max:100'],
            'slug'                 => ['sometimes', 'string', 'min:2', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('persons', 'slug')->ignore($id)],
            'biography'            => ['nullable', 'array'],
            'biography.*'          => ['nullable', 'string', 'max:10000'],
            'date_of_birth'        => ['nullable', 'date', 'before:today'],
            'date_of_death'        => ['nullable', 'date', 'after_or_equal:date_of_birth'],
            'place_of_birth'       => ['nullable', 'array'],
            'place_of_birth.*'     => ['nullable', 'string', 'max:255'],
            'gender'               => ['nullable', Rule::in(Gender::values())],
            'known_for_department' => ['nullable', 'string', 'max:100'],
            'popularity'           => ['sometimes', 'numeric', 'min:0', 'max:10000'],
            'is_active'            => ['sometimes', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('person::messages.validation_failed'),
            ),
        );
    }
}
