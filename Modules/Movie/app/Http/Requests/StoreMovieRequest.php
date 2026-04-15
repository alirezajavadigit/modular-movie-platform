<?php

namespace Modules\Movie\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;

class StoreMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'poster'          => ['nullable', 'string', 'max:2048'],
            'trailer_url'     => ['nullable', 'string', 'url', 'max:2048'],
            'download_links'  => ['nullable', 'array'],
            'download_links.*' => ['string', 'url', 'max:2048'],
            'release_year'    => ['required', 'integer', 'min:1888', 'max:' . (date('Y') + 5)],
            'country'         => ['nullable', 'string', 'max:100'],
            'language'        => ['nullable', 'string', 'max:100'],
            'imdb_score'      => ['nullable', 'numeric', 'min:0', 'max:10'],
            'badge'           => ['required', 'string', 'in:' . implode(',', array_column(BadgeType::cases(), 'value'))],
            'type'            => ['required', 'string', 'in:' . implode(',', array_column(MovieType::cases(), 'value'))],
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
