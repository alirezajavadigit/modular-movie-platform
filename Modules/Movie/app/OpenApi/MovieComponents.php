<?php

declare(strict_types=1);

namespace Modules\Movie\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Movie', description: 'Movies, serials, and their episodes')]
#[OA\Schema(
    schema: 'Movie',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', description: 'Resolved for the current locale', example: 'Inception'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'poster', type: 'string', nullable: true, example: 'uploads/movies/posters/inception.webp'),
        new OA\Property(property: 'trailer_url', type: 'string', nullable: true, format: 'uri'),
        new OA\Property(property: 'download_links', type: 'array', nullable: true, items: new OA\Items(type: 'string', format: 'uri')),
        new OA\Property(property: 'release_year', type: 'integer', example: 2010),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'USA'),
        new OA\Property(property: 'language', type: 'string', nullable: true, example: 'English'),
        new OA\Property(property: 'imdb_score', type: 'string', nullable: true, example: '8.8'),
        new OA\Property(property: 'badge', type: 'string', nullable: true, enum: ['dubbed', 'subtitled', 'animation']),
        new OA\Property(property: 'type', type: 'string', nullable: true, enum: ['movie', 'serial']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'Episode',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'movie_id', type: 'integer', example: 1),
        new OA\Property(property: 'season_number', type: 'integer', example: 1),
        new OA\Property(property: 'episode_number', type: 'integer', example: 3),
        new OA\Property(property: 'title', type: 'string', description: 'Resolved for the current locale'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'poster', type: 'string', nullable: true),
        new OA\Property(property: 'trailer_url', type: 'string', nullable: true, format: 'uri'),
        new OA\Property(property: 'download_links', type: 'array', nullable: true, items: new OA\Items(type: 'string', format: 'uri')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'StoreMoviePayload',
    type: 'object',
    required: ['title', 'release_year', 'badge', 'type'],
    properties: [
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'poster', type: 'string', nullable: true, maxLength: 2048),
        new OA\Property(property: 'poster_file', type: 'string', format: 'binary', nullable: true, description: 'jpeg, png, jpg, or webp up to 5 MB; multipart/form-data only'),
        new OA\Property(property: 'trailer_url', type: 'string', format: 'uri', nullable: true, maxLength: 2048),
        new OA\Property(property: 'download_links', type: 'array', nullable: true, items: new OA\Items(type: 'string', format: 'uri', maxLength: 2048)),
        new OA\Property(property: 'release_year', type: 'integer', minimum: 1888),
        new OA\Property(property: 'country', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'language', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'imdb_score', type: 'number', nullable: true, minimum: 0, maximum: 10),
        new OA\Property(property: 'badge', type: 'string', enum: ['dubbed', 'subtitled', 'animation']),
        new OA\Property(property: 'type', type: 'string', enum: ['movie', 'serial']),
    ],
)]
#[OA\Schema(
    schema: 'UpdateMoviePayload',
    type: 'object',
    required: ['title', 'release_year', 'badge'],
    properties: [
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'poster', type: 'string', nullable: true, maxLength: 2048),
        new OA\Property(property: 'poster_file', type: 'string', format: 'binary', nullable: true, description: 'jpeg, png, jpg, or webp up to 5 MB; multipart/form-data only'),
        new OA\Property(property: 'trailer_url', type: 'string', format: 'uri', nullable: true, maxLength: 2048),
        new OA\Property(property: 'download_links', type: 'array', nullable: true, items: new OA\Items(type: 'string', format: 'uri', maxLength: 2048)),
        new OA\Property(property: 'release_year', type: 'integer', minimum: 1888),
        new OA\Property(property: 'country', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'language', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'imdb_score', type: 'number', nullable: true, minimum: 0, maximum: 10),
        new OA\Property(property: 'badge', type: 'string', enum: ['dubbed', 'subtitled', 'animation']),
    ],
)]
#[OA\Schema(
    schema: 'StoreEpisodePayload',
    type: 'object',
    required: ['season_number', 'episode_number', 'title'],
    properties: [
        new OA\Property(property: 'season_number', type: 'integer', minimum: 1),
        new OA\Property(property: 'episode_number', type: 'integer', minimum: 1),
        new OA\Property(property: 'title', ref: '#/components/schemas/TranslationMap'),
        new OA\Property(property: 'description', ref: '#/components/schemas/NullableTranslationMap'),
        new OA\Property(property: 'poster', type: 'string', nullable: true, maxLength: 2048),
        new OA\Property(property: 'poster_file', type: 'string', format: 'binary', nullable: true, description: 'jpeg, png, jpg, or webp up to 5 MB; multipart/form-data only'),
        new OA\Property(property: 'trailer_url', type: 'string', format: 'uri', nullable: true, maxLength: 2048),
        new OA\Property(property: 'download_links', type: 'array', nullable: true, items: new OA\Items(type: 'string', format: 'uri', maxLength: 2048)),
    ],
)]
#[OA\RequestBody(
    request: 'StoreMovieRequest',
    required: true,
    content: [
        new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/StoreMoviePayload')),
        new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/StoreMoviePayload')),
    ],
)]
#[OA\RequestBody(
    request: 'UpdateMovieRequest',
    required: true,
    content: [
        new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/UpdateMoviePayload')),
        new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/UpdateMoviePayload')),
    ],
)]
#[OA\RequestBody(
    request: 'StoreEpisodeRequest',
    required: true,
    content: [
        new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/StoreEpisodePayload')),
        new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/StoreEpisodePayload')),
    ],
)]
#[OA\RequestBody(
    request: 'UpdateEpisodeRequest',
    required: true,
    content: [
        new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/StoreEpisodePayload')),
        new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/StoreEpisodePayload')),
    ],
)]
#[OA\Response(
    response: 'MovieItem',
    description: 'Single movie wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Movie')]),
        ],
    ),
)]
#[OA\Response(
    response: 'MovieCreated',
    description: 'Movie created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Movie')]),
        ],
    ),
)]
#[OA\Response(
    response: 'MovieCollection',
    description: 'List of movies wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Movie'))]),
        ],
    ),
)]
#[OA\Response(
    response: 'MoviePage',
    description: 'Paginated list of movies',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Movie')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]),
        ],
    ),
)]
#[OA\Response(
    response: 'EpisodeItem',
    description: 'Single episode wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Episode')]),
        ],
    ),
)]
#[OA\Response(
    response: 'EpisodeCreated',
    description: 'Episode created',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Episode')]),
        ],
    ),
)]
#[OA\Response(
    response: 'EpisodeCollection',
    description: 'List of episodes wrapped in the success envelope',
    content: new OA\JsonContent(
        allOf: [
            new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
            new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Episode'))]),
        ],
    ),
)]
final class MovieComponents
{
}
