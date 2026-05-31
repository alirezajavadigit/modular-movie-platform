<?php

namespace Modules\User\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'email'    => ['nullable', 'required_without:phone', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'required_without:email', 'string', 'max:255', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'roles'    => ['sometimes', 'array'],
            'roles.*'  => ['string', 'exists:roles,name'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('user::messages.validation_failed'),
            ),
        );
    }
}
