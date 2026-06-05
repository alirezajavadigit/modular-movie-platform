<?php

namespace Modules\Discussion\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;
use Modules\Discussion\Enums\DiscussionStatus;

class UpdateDiscussionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $min = (int) config('discussion-module.body.min', 3);
        $max = (int) config('discussion-module.body.max', 5000);

        return [
            'body'   => ['sometimes', 'required', 'string', "min:{$min}", "max:{$max}"],
            'status' => ['sometimes', 'required', new Enum(DiscussionStatus::class)],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('discussion::messages.validation_failed'),
            ),
        );
    }
}
