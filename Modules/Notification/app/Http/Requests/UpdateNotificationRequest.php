<?php

namespace Modules\Notification\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Notification\Enums\NotificationChannel;

class UpdateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $registeredTypes = array_keys(config('notification-module.notification_types', []));
        $validChannels   = array_column(NotificationChannel::cases(), 'value');

        return [
            'type'    => ['sometimes', 'string', 'in:' . implode(',', $registeredTypes)],
            'channel' => ['sometimes', 'string', 'in:' . implode(',', $validChannels)],
            'data'    => ['sometimes', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('notification::messages.validation_failed'),
            ),
        );
    }
}
