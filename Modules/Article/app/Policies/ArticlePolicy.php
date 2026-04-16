<?php

namespace Modules\Article\Policies;

use Modules\Auth\Models\User;
use Modules\Article\Models\Article;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        try {
            return $user->hasPermissionTo('articles.viewAny');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function view(User $user, Article $article): bool
    {
        try {
            if ($user->hasPermissionTo('articles.view')) {
                return true;
            }

            return $user->id === $article->user_id && $user->hasPermissionTo('articles.viewOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $user->hasPermissionTo('articles.create');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function update(User $user, Article $article): bool
    {
        try {
            if ($user->hasPermissionTo('articles.update')) {
                return true;
            }

            return $user->id === $article->user_id && $user->hasPermissionTo('articles.updateOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function delete(User $user, Article $article): bool
    {
        try {
            if ($user->hasPermissionTo('articles.delete')) {
                return true;
            }

            return $user->id === $article->user_id && $user->hasPermissionTo('articles.deleteOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function restore(User $user, Article $article): bool
    {
        try {
            return $user->hasPermissionTo('articles.restore');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function forceDelete(User $user, Article $article): bool
    {
        try {
            return $user->hasPermissionTo('articles.forceDelete');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function publish(User $user, Article $article): bool
    {
        try {
            if ($user->hasPermissionTo('articles.publish')) {
                return true;
            }

            return $user->id === $article->user_id && $user->hasPermissionTo('articles.publishOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function archive(User $user, Article $article): bool
    {
        try {
            if ($user->hasPermissionTo('articles.archive')) {
                return true;
            }

            return $user->id === $article->user_id && $user->hasPermissionTo('articles.archiveOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function markAsDraft(User $user, Article $article): bool
    {
        try {
            if ($user->hasPermissionTo('articles.markAsDraft')) {
                return true;
            }

            return $user->id === $article->user_id && $user->hasPermissionTo('articles.markAsDraftOwn');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewTrashed(User $user): bool
    {
        try {
            return $user->hasPermissionTo('articles.viewTrashed');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
