<?php

declare(strict_types=1);

namespace Modules\Category\Http\Resources\Transformers;

use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Category\Models\Category;

class CategoryTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'parent',
        'children',
        'articles',
    ];

    public function transform(Category $category): array
    {
        return [
            'id'          => $category->id,
            'parent_id'   => $category->parent_id,
            'name'        => $category->getTranslations('name'),
            'slug'        => $category->getTranslations('slug'),
            'description' => $category->getTranslations('description'),
            'is_active'   => (bool) $category->is_active,
            'order'       => (int) $category->order,
            'created_at'  => $category->created_at?->toIso8601String(),
            'updated_at'  => $category->updated_at?->toIso8601String(),
            'deleted_at'  => $category->deleted_at?->toIso8601String(),
        ];
    }

    public function includeParent(Category $category): \League\Fractal\Resource\Item|NullResource
    {
        if (!$category->parent) {
            return $this->null();
        }
        return $this->item($category->parent, new self());
    }

    public function includeChildren(Category $category): FractalCollection
    {
        return $this->collection($category->children, new self());
    }

    public function includeArticles(Category $category): FractalCollection|NullResource
    {
        if (!$category->relationLoaded('articles') && !$category->articles()->exists()) {
            return $this->null();
        }
        return $this->collection($category->articles, function ($article) {
            return [
                'id'     => $article->id,
                'title'  => $article->getTranslations('title'),
                'slug'   => $article->getTranslations('slug'),
                'status' => $article->status,
            ];
        });
    }
}
