<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Models\KmBookmark;
use App\Models\KmPengajuan;
use Illuminate\Http\JsonResponse;

class KmBookmarkController extends Controller
{
    /**
     * Tambah bookmark untuk dokumen tertentu (idempotent).
     * POST /km/documents/{kmPengajuan}/bookmarks
     */
    public function store(KmPengajuan $kmPengajuan): JsonResponse
    {
        $this->authorize('view', $kmPengajuan);

        $user = auth()->user();

        $bookmark = KmBookmark::firstOrCreate([
            'user_id' => $user->getKey(),
            'km_pengajuan_id' => $kmPengajuan->getKey(),
        ]);

        return response()->json([
            'bookmarked' => true,
            'message' => $bookmark->wasRecentlyCreated
                ? 'Dokumen berhasil disimpan ke daftar bacaan.'
                : 'Dokumen sudah ada di daftar bacaan.',
        ], $bookmark->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Hapus bookmark untuk dokumen tertentu (idempotent).
     * DELETE /km/documents/{kmPengajuan}/bookmarks
     */
    public function destroy(KmPengajuan $kmPengajuan): JsonResponse
    {
        $this->authorize('view', $kmPengajuan);

        $user = auth()->user();

        KmBookmark::query()
            ->where('user_id', $user->getKey())
            ->where('km_pengajuan_id', $kmPengajuan->getKey())
            ->delete();

        // Selalu 204 setelah authorization terpenuhi, bahkan jika row tidak ada
        return response()->json(null, 204);
    }
}
