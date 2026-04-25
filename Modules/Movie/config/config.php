<?php

return [
    'upload' => [
        'disk' => env('MOVIE_UPLOAD_DISK', 'public'),

        'directories' => [
            'movie_posters'  => 'movies/posters',
            'episode_posters' => 'episodes/posters',
        ],
    ],
];
