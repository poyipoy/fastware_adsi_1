<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\AddKmInsightRequest;
use App\Http\Requests\KnowledgeManagement\KmInsightActionRequest;
use App\Http\Requests\KnowledgeManagement\KmInsightListRequest;
use App\Http\Requests\KnowledgeManagement\KmMentionOptionsRequest;
use App\Models\Insight;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmInteractionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class KmInsightController extends Controller
{
    public function __construct(
        private readonly KmInteractionService $interactions,
    ) {
    }

    public function index(KmInsightListRequest $request, KmPengajuan $kmPengajuan): JsonResponse
    {
        $this->authorize('view', $kmPengajuan);

        return response()->json($this->interactions->listInsights(
            $request->user(),
            $kmPengajuan,
            $request->perPage(),
            $request->focusId(),
        ));
    }

    public function store(AddKmInsightRequest $request, KmPengajuan $kmPengajuan): JsonResponse
    {
        try {
            $insight = $this->interactions->addInsight(
                $request->user(),
                $kmPengajuan,
                $request->string('content')->toString(),
                $request->validated('parent_id'),
                array_map('intval', $request->validated('mention_ids', [])),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['content' => $exception->getMessage()]);
        }

        return response()->json([
            'message' => 'Insight berhasil ditambahkan.',
            'id' => (int) $insight->getKey(),
        ], 201);
    }

    public function update(KmInsightActionRequest $request, Insight $insight): JsonResponse
    {
        if ($request->validated('content') === null) {
            throw ValidationException::withMessages(['content' => 'Isi insight wajib diisi.']);
        }

        try {
            $updated = $this->interactions->editInsight(
                $request->user(),
                $insight,
                $request->string('content')->toString(),
                $request->has('mention_ids')
                    ? array_map('intval', $request->validated('mention_ids', []))
                    : null,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['content' => $exception->getMessage()]);
        }

        return response()->json([
            'message' => 'Insight berhasil diperbarui.',
            'id' => (int) $updated->getKey(),
        ]);
    }

    public function destroy(KmInsightActionRequest $request, Insight $insight): JsonResponse
    {
        try {
            $deleted = $this->interactions->deleteInsight(
                $request->user(),
                $insight,
                $request->validated('reason'),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['reason' => $exception->getMessage()]);
        }

        return response()->json([
            'message' => $deleted ? 'Insight berhasil dihapus.' : 'Insight sudah dihapus.',
            'deleted' => true,
        ]);
    }

    public function react(KmInsightActionRequest $request, Insight $insight): JsonResponse
    {
        $reaction = $request->validated('reaction');
        if ($reaction === null) {
            throw ValidationException::withMessages(['reaction' => 'Reaction wajib dipilih.']);
        }

        try {
            $result = $this->interactions->react($request->user(), $insight, $reaction);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['reaction' => $exception->getMessage()]);
        }

        return response()->json($result);
    }

    public function unreact(KmInsightActionRequest $request, Insight $insight): JsonResponse
    {
        return response()->json($this->interactions->unreact($request->user(), $insight));
    }

    public function feature(KmInsightActionRequest $request, Insight $insight): JsonResponse
    {
        try {
            $featured = $this->interactions->feature($request->user(), $insight);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['insight' => $exception->getMessage()]);
        }

        return response()->json([
            'message' => 'Insight ditetapkan sebagai Insight Pilihan.',
            'featured_at' => $featured->featured_at?->toIso8601String(),
        ]);
    }

    public function unfeature(KmInsightActionRequest $request, Insight $insight): JsonResponse
    {
        return response()->json([
            'message' => 'Status Insight Pilihan dibatalkan.',
            'changed' => $this->interactions->unfeature($request->user(), $insight),
        ]);
    }

    public function mentionOptions(
        KmMentionOptionsRequest $request,
        KmPengajuan $kmPengajuan,
    ): JsonResponse {
        return response()->json([
            'data' => $this->interactions
                ->mentionOptions(
                    $request->user(),
                    $kmPengajuan,
                    $request->searchQuery(),
                )
                ->map(static fn ($user): array => [
                    'id' => (int) $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->values(),
        ]);
    }
}
