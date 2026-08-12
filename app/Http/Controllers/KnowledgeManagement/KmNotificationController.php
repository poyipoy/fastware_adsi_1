<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\KmNotificationIndexRequest;
use App\Services\KnowledgeManagement\KmNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KmNotificationController extends Controller
{
    public function __construct(
        private readonly KmNotificationService $notifications,
    ) {
    }

    public function index(KmNotificationIndexRequest $request): JsonResponse
    {
        $paginator = $this->notifications->paginateFor(
            $request->user(),
            $request->perPage(),
        );

        return response()->json([
            'data' => collect($paginator->items())->map(static fn ($notification): array => [
                'id' => (int) $notification->getKey(),
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        abort_unless(
            $this->notifications->markRead($request->user(), $notification),
            404,
        );

        return response()->json([
            'read' => true,
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        return response()->json([
            'updated_count' => $this->notifications->markAllRead($request->user()),
            'unread_count' => 0,
        ]);
    }
}
