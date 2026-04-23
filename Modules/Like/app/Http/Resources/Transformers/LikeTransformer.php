<?php

declare(strict_types=1);

namespace Modules\Like\Http\Resources\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Like\Models\Like;
use Modules\Like\Support\TransformerRegistry;
use Throwable;

class LikeTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'likeable',
    ];

    public function transform(Like $like): array
    {
        return [
            'id'            => $like->id,
            'user_id'       => $like->user_id,
            'likeable_type' => class_basename($like->likeable_type),
            'likeable_id'   => $like->likeable_id,
            'created_at'    => $like->created_at?->toIso8601String(),
            'updated_at'    => $like->updated_at?->toIso8601String(),
        ];
    }

    public function includeLikeable(Like $like): Item|NullResource
    {
        $model = $like->likeable;

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
