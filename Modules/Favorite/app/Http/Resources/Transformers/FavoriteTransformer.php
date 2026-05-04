<?php

declare(strict_types=1);

namespace Modules\Favorite\Http\Resources\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Support\TransformerRegistry;
use Throwable;

class FavoriteTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'favoritable',
    ];

    public function transform(Favorite $favorite): array
    {
        return [
            'id'               => $favorite->id,
            'user_id'          => $favorite->user_id,
            'favoritable_type' => class_basename($favorite->favoriteable_type),
            'favoritable_id'   => $favorite->favoriteable_id,
            'created_at'       => $favorite->created_at?->toIso8601String(),
            'updated_at'       => $favorite->updated_at?->toIso8601String(),
        ];
    }

    public function includeFavoritable(Favorite $favorite): Item|NullResource
    {
        $model = $favorite->favoriteable;

        if ($model === null) {
            return $this->null();
        }

        try {
            $transformer = TransformerRegistry::resolve($model);
        } catch (Throwable) {
            return $this->null();
        }

        return $this->item($model, $transformer);
    }
}
