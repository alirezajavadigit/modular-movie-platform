<?php

declare(strict_types=1);

namespace Modules\Tag\Http\Resources\Transformers;

use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Tag\Models\Tag;

class TagTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'articles',
    ];

    public function transform(Tag $tag): array
    {
        return [
            'id'             => $tag->id,
            'name'           => $tag->getTranslations('name'),
            'slug'           => $tag->getTranslations('slug'),
            'description'    => $tag->getTranslations('description'),
            'color'          => $tag->color,
            'is_active'      => (bool) $tag->is_active,
            'articles_count' => $tag->articles_count ?? null,
            'created_at'     => $tag->created_at?->toIso8601String(),
            'updated_at'     => $tag->updated_at?->toIso8601String(),
            'deleted_at'     => $tag->deleted_at?->toIso8601String(),
        ];
    }

    public function includeArticles(Tag $tag): FractalCollection|NullResource
    {
        if (!$tag->relationLoaded('articles') && !$tag->articles()->exists()) {
            return $this->null();
        }
        return $this->collection($tag->articles, function ($article) {
            return [
                'id'    => $article->id,
                'title' => $article->getTranslations('title'),
                'slug'  => $article->getTranslations('slug'),
            ];
        });
    }
}
