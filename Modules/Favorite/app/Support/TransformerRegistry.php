<?php

namespace Modules\Favorite\Support;

use League\Fractal\TransformerAbstract;
use RuntimeException;

final class TransformerRegistry
{
    public static function resolve(object $model): TransformerAbstract
    {
        $map              = config('favorite.transformer_map', []);
        $modelClass       = $model::class;
        $transformerClass = $map[$modelClass] ?? null;

        if ($transformerClass === null) {
            throw new RuntimeException(
                "No Fractal transformer registered for [{$modelClass}]. "
                . "Add an entry to config/favorite.php under 'transformer_map'.",
            );
        }

        return app($transformerClass);
    }

    public static function has(string $modelClass): bool
    {
        return array_key_exists($modelClass, config('favorite.transformer_map', []));
    }
}
