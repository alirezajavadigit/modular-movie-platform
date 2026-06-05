<?php

namespace Modules\Payment\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Payment\Enums\PaymentStatus;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'status'         => ['nullable', 'string', 'in:' . implode(',', array_column(PaymentStatus::cases(), 'value'))],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('payment::messages.validation_failed'),
            ),
        );
    }
}
