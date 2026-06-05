<?php

namespace Modules\Notification\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Notification\Enums\NotificationChannel;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $registeredTypes    = array_keys(config('notification-module.notification_types', []));
        $validChannels      = array_column(NotificationChannel::cases(), 'value');
        $registeredAliases  = array_keys(config('notification-module.morph_map', []));

        return [
            'notifiable_type' => ['required', 'string', 'in:' . implode(',', $registeredAliases)],
            'notifiable_id'   => ['required', 'integer', 'min:1'],
            'type'            => ['required', 'string', 'in:' . implode(',', $registeredTypes)],
            'channel'         => ['required', 'string', 'in:' . implode(',', $validChannels)],
            'data'            => ['nullable', 'array'],
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
