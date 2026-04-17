<?php

namespace Modules\Discussion\Http\Requests;

use App\Facades\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreDiscussionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = array_keys(config('discussion-module.discussionable_types', []));

        $min = (int) config('discussion-module.body.min', 3);
        $max = (int) config('discussion-module.body.max', 5000);

        return [
            'discussionable_id'   => ['required', 'integer', 'min:1'],
            'discussionable_type' => ['required', 'string', Rule::in($types)],
            'body'                => ['required', 'string', "min:{$min}", "max:{$max}"],
            'parent_id'           => ['nullable', 'integer', 'exists:discussions,id'],
        ];
    }

    public function getMorphClass(): string
    {
        $map = config('discussion-module.discussionable_types', []);
        $alias = $this->input('discussionable_type');

        if (! array_key_exists($alias, $map)) {
            abort(422, __('discussion-module::messages.invalid_discussionable_type'));
        }

        return $map[$alias];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors(),
                __('discussion-module::messages.validation_failed')
            )
        );
    }
}
