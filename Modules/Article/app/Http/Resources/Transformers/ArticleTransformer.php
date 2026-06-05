<?php

declare(strict_types=1);

namespace Modules\Article\Http\Resources\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Article\Models\Article;

class ArticleTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'user',
    ];

    public function transform(Article $article): array
    {
        return [
            'id' => $article->id,
            'user_id' => $article->user_id,
            'title' => $article->getTranslations('title'),
            'slug' => $article->getTranslations('slug'),
            'summary' => $article->getTranslations('summary'),
            'body' => $article->getTranslations('body'),
            'status' => $article->status,
            'read_time' => $article->read_time,
            'is_featured' => (bool) $article->is_featured,
            'allow_comments' => (bool) $article->allow_comments,
            'published_at' => $article->published_at?->toIso8601String(),
            'created_at' => $article->created_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
            'deleted_at' => $article->deleted_at?->toIso8601String(),
        ];
    }

    public function includeUser(Article $article): \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
    {
        $user = $article->user;

        if (!$user) {
            return $this->null();
        }

        return $this->item($user, function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        });
    }
}
