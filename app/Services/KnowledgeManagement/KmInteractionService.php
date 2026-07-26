<?php

namespace App\Services\KnowledgeManagement;

use App\Models\Insight;
use App\Models\KmPengajuan;
use App\Models\KmSuka;
use App\Models\User;

class KmInteractionService
{
    /**
     * @return array{created: bool, like_count: int}
     */
    public function like(User $user, KmPengajuan $document): array
    {
        $like = KmSuka::query()->firstOrCreate([
            'id_user' => $user->getKey(),
            'id_km_pengajuan' => $document->getKey(),
        ]);

        return [
            'created' => $like->wasRecentlyCreated,
            'like_count' => $this->likeCount($document),
        ];
    }

    /**
     * @return array{deleted: bool, like_count: int}
     */
    public function unlike(User $user, KmPengajuan $document): array
    {
        $deleted = KmSuka::query()
            ->where('id_user', $user->getKey())
            ->where('id_km_pengajuan', $document->getKey())
            ->delete() > 0;

        return [
            'deleted' => $deleted,
            'like_count' => $this->likeCount($document),
        ];
    }

    public function addInsight(User $user, KmPengajuan $document, string $content): Insight
    {
        return Insight::query()->create([
            'id_user' => $user->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'content' => $content,
        ]);
    }

    private function likeCount(KmPengajuan $document): int
    {
        return KmSuka::query()
            ->where('id_km_pengajuan', $document->getKey())
            ->count();
    }
}
