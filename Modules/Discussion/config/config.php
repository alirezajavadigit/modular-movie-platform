<?php

return [
    'name' => 'Discussion',

    'pagination' => [
        'per_page' => 15,
    ],

    'discussionable_types' => [
        'movie'   => \Modules\Movie\Models\Movie::class,
        'episode' => \Modules\Movie\Models\Episode::class,
        'article' => \Modules\Article\Models\Article::class,
    ],

    'auto_approve' => false,

    'body' => [
        'min' => 3,
        'max' => 5000,
    ],
];
