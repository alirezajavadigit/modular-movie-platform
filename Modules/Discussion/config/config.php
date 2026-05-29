<?php

return [
    'name' => 'Discussion',

    'per_page' => 15,

    'auto_approve' => false,

    'discussionable_types' => [
        'movie'   => \Modules\Movie\Models\Movie::class,
        'episode' => \Modules\Movie\Models\Episode::class,
        'article' => \Modules\Article\Models\Article::class,
    ],

    'body' => [
        'min' => 3,
        'max' => 5000,
    ],
];
