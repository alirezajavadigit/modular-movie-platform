<?php

namespace Modules\Movie\Http\Resources\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Movie\Models\Episode;

class EpisodeTransformer extends TransformerAbstract
{
    public function transform(Episode $episode): array
    {
        return [
            'id'             => $episode->id,
            'movie_id'       => $episode->movie_id,
            'season_number'  => $episode->season_number,
            'episode_number' => $episode->episode_number,
            'title'          => $episode->title,
            'description'    => $episode->description,
            'poster'         => $episode->poster,
            'trailer_url'    => $episode->trailer_url,
            'download_links' => $episode->download_links,
            'created_at'     => $episode->created_at?->toISOString(),
            'updated_at'     => $episode->updated_at?->toISOString(),
        ];
    }
}
