<?php

return [
    'name' => 'Person',

    'defaults' => [
        'per_page'   => 15,
        'max_credits_per_person' => 10000,
    ],

    'morph_map' => [
        'person'  => \Modules\Person\Models\Person::class,
        'movie'   => \Modules\Movie\Models\Movie::class,
        'episode' => \Modules\Episode\Models\Episode::class,
        'article' => \Modules\Article\Models\Article::class,
    ],
];
