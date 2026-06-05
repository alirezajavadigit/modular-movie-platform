<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Article\Models\Article;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

return [
    'name' => 'Like',

    'likeable_models' => [
        'movie'   => Relation::getMorphAlias(Movie::class),
        'episode' => Relation::getMorphAlias(Episode::class),
        'article' => Relation::getMorphAlias(Article::class),
        'person'  => Relation::getMorphAlias(Person::class),
    ],

    'transformer_map' => [
        \Modules\Movie\Models\Movie::class     => \Modules\Movie\Http\Resources\Transformers\MovieTransformer::class,
        \Modules\Movie\Models\Episode::class   => \Modules\Movie\Http\Resources\Transformers\EpisodeTransformer::class,
        \Modules\Article\Models\Article::class => \Modules\Article\Http\Resources\Transformers\ArticleTransformer::class,
    ],

    'per_page' => 15,

    'morph_map' => [
        'movie'   => Movie::class,
        'episode' => Episode::class,
        'article' => Article::class,
        'person'  => Person::class,
    ],
];
