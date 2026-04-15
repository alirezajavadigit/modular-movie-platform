<?php

namespace Modules\Movie\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'season_number'   => ['required', 'integer', 'min:1'],
            'episode_number'  => ['required', 'integer', 'min:1'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'poster'          => ['nullable', 'string', 'max:2048'],
            'trailer_url'     => ['nullable', 'string', 'url', 'max:2048'],
            'download_links'  => ['nullable', 'array'],
            'download_links.*' => ['string', 'url', 'max:2048'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status'  => 'error',
                'message' => __('movie::messages.validation_failed'),
                'errors'  => $validator->errors(),
            ], 422),
        );
    }
}
