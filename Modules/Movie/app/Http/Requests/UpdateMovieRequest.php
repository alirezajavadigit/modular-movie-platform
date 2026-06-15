<?php

namespace Modules\Movie\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Movie\Enums\BadgeType;

class UpdateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'array'],
            'title.*'          => ['required', 'string', 'min:1', 'max:255'],
            'description'      => ['nullable', 'array'],
            'description.*'    => ['nullable', 'string'],
            'poster'          => ['nullable', 'string', 'max:2048'],
            'poster_file'     => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'trailer_url'     => ['nullable', 'string', 'url', 'max:2048'],
            'download_links'  => ['nullable', 'array'],
            'download_links.*' => ['string', 'url', 'max:2048'],
            'release_year'    => ['required', 'integer', 'min:1888', 'max:' . (date('Y') + 5)],
            'country'         => ['nullable', 'string', 'max:100'],
            'language'        => ['nullable', 'string', 'max:100'],
            'imdb_score'      => ['nullable', 'numeric', 'min:0', 'max:10'],
            'badge'           => ['required', 'string', 'in:' . implode(',', array_column(BadgeType::cases(), 'value'))],
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
