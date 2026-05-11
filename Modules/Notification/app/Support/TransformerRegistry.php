<?php

namespace Modules\Notification\Support;

use League\Fractal\TransformerAbstract;
use RuntimeException;

final class TransformerRegistry
{
    public static function resolve(object $model): TransformerAbstract
    {
        $map              = config('notification-module.transformer_map', []);
        $modelClass       = $model::class;
        $transformerClass = $map[$modelClass] ?? null;

        if ($transformerClass === null) {
            throw new RuntimeException(
                "No Fractal transformer registered for [{$modelClass}]. "
                . "Add an entry to config/config.php under 'transformer_map'.",
            );
        }

        return app($transformerClass);
    }

    public static function has(string $modelClass): bool
    {
        return array_key_exists($modelClass, config('notification-module.transformer_map', []));
    }
}
