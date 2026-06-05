<?php

declare(strict_types=1);

namespace Modules\Discussion\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Discussion\Contracts\DiscussionServiceInterface;
use Modules\Discussion\Http\Resources\Transformers\DiscussionTransformer;
use Modules\Discussion\Models\Discussion;

class DiscussionTrashedController extends Controller
{
    public static string $modelClass = Discussion::class;

    public function __construct(
        private readonly DiscussionServiceInterface $service,
        private readonly DiscussionTransformer $transformer,
    ) {}

    public function restore(Discussion $discussion): JsonResponse
    {
        $this->service->restore($discussion);

        return ApiResponse::fractal(
            $discussion->fresh(['user', 'approvedReplies.user']),
            $this->transformer,
            __('discussion::messages.restored'),
        );
    }

    public function forceDelete(Discussion $discussion): JsonResponse
    {

        $this->service->forceDelete($discussion);

        return ApiResponse::success(null, __('discussion::messages.force_deleted'));
    }
}
