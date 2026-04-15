<?php

namespace Modules\Movie\Http\Resources\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;
use Modules\Movie\Models\Movie;

class MovieTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'episodes',
    ];

    public function transform(Movie $movie): array
    {
        return [
            'id'             => $movie->id,
            'title'          => $movie->title,
            'description'    => $movie->description,
            'poster'         => $movie->poster,
            'trailer_url'    => $movie->trailer_url,
            'download_links' => $movie->download_links,
            'release_year'   => $movie->release_year,
            'country'        => $movie->country,
            'language'       => $movie->language,
            'imdb_score'     => $movie->imdb_score,
            'badge'          => $movie->badge?->value,
            'type'           => $movie->type?->value,
            'created_at'     => $movie->created_at?->toISOString(),
            'updated_at'     => $movie->updated_at?->toISOString(),
        ];
    }

    public function includeEpisodes(Movie $movie): Collection
    {
        return $this->collection($movie->episodes, new EpisodeTransformer());
    }
}
