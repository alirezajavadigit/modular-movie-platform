<?php

declare(strict_types=1);

namespace Modules\Person\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Modules\Person\Enums\CreditRole;

class StoreCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $morphTypes = array_keys(Relation::morphMap());

        return [
            'person_id'       => ['required', 'integer', 'exists:persons,id'],
            'creditable_type' => ['required', 'string', Rule::in($morphTypes)],
            'creditable_id'   => ['required', 'integer', 'min:1'],
            'role'            => ['required', 'string', Rule::in(CreditRole::values())],
            'character_name'  => ['nullable', 'string', 'max:255'],
            'credited_as'     => ['nullable', 'string', 'max:255'],
            'department'      => ['nullable', 'string', 'max:100'],
            'order'           => ['sometimes', 'integer', 'min:0', 'max:99999'],
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
