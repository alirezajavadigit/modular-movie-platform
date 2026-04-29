<?php

declare(strict_types=1);

namespace Modules\Person\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Modules\Person\Enums\CreditRole;

class UpdateCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role'           => ['sometimes', 'string', Rule::in(CreditRole::values())],
            'character_name' => ['nullable', 'string', 'max:255'],
            'credited_as'    => ['nullable', 'string', 'max:255'],
            'department'     => ['nullable', 'string', 'max:100'],
            'order'          => ['sometimes', 'integer', 'min:0', 'max:99999'],
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
