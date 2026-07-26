<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\KmCoAuthorOptionsRequest;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class KmCoAuthorOptionsController extends Controller
{
    public function __invoke(KmCoAuthorOptionsRequest $request): JsonResponse
    {
        $document = $request->validated('document_id') === null
            ? null
            : KmPengajuan::query()->findOrFail((int) $request->validated('document_id'));

        if ($document === null) {
            $this->authorize('create', KmPengajuan::class);
            $ownerId = (int) $request->user()->getKey();
        } else {
            $this->authorize('update', $document);
            $ownerId = (int) $document->id_user;
        }

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->where('is_active', true)
            ->whereKeyNot($ownerId)
            ->when($request->validated('q'), function ($query, string $value): void {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
                $query->where(function ($search) use ($pattern): void {
                    $search
                        ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("email LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("npk LIKE ? ESCAPE '!'", [$pattern]);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json(['data' => $users]);
    }
}
