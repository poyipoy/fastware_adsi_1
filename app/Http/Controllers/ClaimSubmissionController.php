<?php

namespace App\Http\Controllers;

use App\Enums\ProcurementMenuAccessGroup;
use App\Models\MstClaimSubmission;
use App\Models\TrsClaimSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Exports\ClaimSubmissionApprovalExport;
use Maatwebsite\Excel\Facades\Excel;
class ClaimSubmissionController extends Controller
{
    private function claimOwnerName(MstClaimSubmission $claim): string
    {
        // Legacy schema: modified_at stores username/PIC, not a timestamp.
        return trim((string) ($claim->modified_at ?? ''));
    }

    private function denyAccess(string $message, bool $json = false): void
    {
        if ($json) {
            abort(response()->json(['message' => $message], 403));
        }

        abort(403, $message);
    }

    private function hasProcurementApprovalAccess(): bool
    {
        return ProcurementMenuAccessGroup::CLAIM_SUBMISSION_APPROVAL
            ->hasAccess($this->currentUserName());
    }

    private function ensureProcurementApprovalAccess(bool $json = false): void
    {
        if ($this->hasProcurementApprovalAccess()) {
            return;
        }

        $this->denyAccess('Anda tidak memiliki akses procurement claim submission.', $json);
    }

    private function ensureClaimOwnerAccess(MstClaimSubmission $claim, bool $json = false): void
    {
        $isOwner = strcasecmp($this->claimOwnerName($claim), trim($this->currentUserName())) === 0;
        if ($isOwner) {
            return;
        }

        $this->denyAccess('Anda hanya dapat mengakses claim milik sendiri.', $json);
    }

    private function ensureClaimOwnerOrProcurementAccess(MstClaimSubmission $claim, bool $json = false): void
    {
        $isOwner = strcasecmp($this->claimOwnerName($claim), trim($this->currentUserName())) === 0;
        if ($isOwner || $this->hasProcurementApprovalAccess()) {
            return;
        }

        $this->denyAccess('Anda tidak memiliki akses ke claim ini.', $json);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'Open',
            'on_progress' => 'On Progress',
            'finished' => 'Finished',
            default => 'Unknown',
        };
    }

    private function currentUserName(): string
    {
        return (string) Auth::user()->getAttribute('name');
    }

    private function uploadClaimFile(Request $request): array
    {
        $result = [];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $destinationPath = public_path('assets/claim_submission');

            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $uuidFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $uuidFileName);

            $result['file'] = 'assets/claim_submission/' . $uuidFileName;
            $result['file_name'] = $file->getClientOriginalName();
        }

        return $result;
    }

    private function buildProcurementHistory(string $title, ?string $oldSupplier, ?string $newSupplier, ?string $oldCatatan, ?string $newCatatan): string
    {
        $changes = [$title];

        if ($oldSupplier !== $newSupplier) {
            $changes[] = 'Supplier: ' . ($oldSupplier ?: '-') . ' → ' . ($newSupplier ?: '-');
        }

        if ($oldCatatan !== $newCatatan) {
            $changes[] = 'Catatan procurement: ' . ($oldCatatan ?: '-') . ' → ' . ($newCatatan ?: '-');
        }

        if (count($changes) === 1) {
            $changes[] = 'Tidak ada perubahan data';
        }

        return implode('; ', $changes);
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $date = date_create((string) $value);

        return $date ? $date->format('Y-m-d') : null;
    }

    private function formatHistoryValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return str_replace([';', "\r", "\n"], [',', ' ', ' '], $value);
    }

    private function formatHistoryDate(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $date = date_create($value);

        return $date ? $date->format('d-m-Y') : $this->formatHistoryValue($value);
    }

    private function buildUserUpdateHistory(array $oldData, array $newData): string
    {
        $changes = ['[Open] Claim submission diperbarui'];

        $fieldLabels = [
            'no_pr' => 'No. PR',
            'nama_produk' => 'Nama Produk',
            'submission_date' => 'Submission Date',
            'category' => 'Category',
            'description_of_issue' => 'Description of Issue',
            'proposed_solution' => 'Proposed Solution',
            'file_name' => 'File',
        ];

        foreach ($fieldLabels as $field => $label) {
            $oldValue = $oldData[$field] ?? null;
            $newValue = $newData[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $oldDisplay = $field === 'submission_date'
                ? $this->formatHistoryDate($oldValue)
                : $this->formatHistoryValue($oldValue);

            $newDisplay = $field === 'submission_date'
                ? $this->formatHistoryDate($newValue)
                : $this->formatHistoryValue($newValue);

            $changes[] = $label . ': ' . $oldDisplay . ' → ' . $newDisplay;
        }

        if (count($changes) === 1) {
            $changes[] = 'Tidak ada perubahan data';
        }

        return implode('; ', $changes);
    }

    /**
     * Halaman index Claim Submission untuk User
     */
    public function indexUser()
    {
        $userName = $this->currentUserName();
        $query = MstClaimSubmission::where('modified_at', $userName);

        // Filter by status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // Filter by date range
        if (request('date_from')) {
            $query->whereDate('submission_date', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('submission_date', '<=', request('date_to'));
        }

        // Gunakan pagination agar query tetap ringan saat data bertambah.
        $perPage = in_array((int) request('per_page'), [10, 25, 50]) ? (int) request('per_page') : 10;
        $claims = $query->orderByDesc('id')->paginate($perPage);
        $claims->appends(request()->query());

        return view('claim_submission.index_user', compact('claims'));
    }

    /**
     * Halaman index Persetujuan Claim Submission untuk Procurement
     */
    public function indexProc()
    {
        $this->ensureProcurementApprovalAccess();

        $query = MstClaimSubmission::query();

        // Filter by status
        if (request('status')) {
            $query->where('status', request('status'));
        } else {
            $query->whereIn('status', ['open', 'on_progress', 'finished']);
        }

        // Filter by PIC
        if (request('pic')) {
            $query->where('modified_at', 'like', '%' . trim(request('pic')) . '%');
        }

        // Filter by category
        if (request('category')) {
            $query->where('category', request('category'));
        }

        // Filter by supplier
        if (request('supplier')) {
            $query->where('supplier', 'like', '%' . trim(request('supplier')) . '%');
        }

        // Filter by date range
        if (request('date_from')) {
            $query->whereDate('submission_date', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('submission_date', '<=', request('date_to'));
        }

        // Gunakan pagination agar query tetap ringan saat data bertambah.
        $perPage = in_array((int) request('per_page'), [10, 25, 50]) ? (int) request('per_page') : 10;
        $claims = $query->orderByDesc('created_at')->paginate($perPage);
        $claims->appends(request()->query());

        return view('claim_submission.index_proc', compact('claims'));
    }

    /**
     * Form tambah Claim Submission (User)
     */
    public function create()
    {
        return view('claim_submission.create');
    }

    /**
     * Simpan Claim Submission baru (User)
     */
    public function store(Request $request)
    {
        $request->validate([
            'no_pr' => 'required|string|max:100',
            'nama_produk' => 'nullable|string|max:255',
            'submission_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'description_of_issue' => 'required|string',
            'proposed_solution' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // max 10MB
        ]);

        $data = array_merge($request->all(), $this->uploadClaimFile($request));

        $claim = DB::transaction(function () use ($data) {
            $claim = MstClaimSubmission::create([
                'no_pr' => $data['no_pr'],
                'nama_produk' => $data['nama_produk'] ?? null,
                'submission_date' => $data['submission_date'],
                'category' => $data['category'] ?? null,
                'description_of_issue' => $data['description_of_issue'],
                'proposed_solution' => $data['proposed_solution'] ?? null,
                'status' => 'open',
                'file' => $data['file'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'modified_at' => $this->currentUserName(),
            ]);

            // Record history
            TrsClaimSubmission::create([
                'id_claim' => $claim->id,
                'keterangan' => 'Claim submission dibuat',
                'status' => 'open',
                'modified_at' => $this->currentUserName(),
            ]);

            return $claim;
        });

        return redirect()->route('claim.indexUser')->with('success', 'Claim submission berhasil ditambahkan');
    }

    /**
     * Form edit Claim Submission (User) - hanya bisa edit saat status open
     */
    public function edit($id)
    {
        $claim = MstClaimSubmission::findOrFail($id);
        $this->ensureClaimOwnerAccess($claim);

        // Hanya bisa edit saat status open
        if ($claim->status !== 'open') {
            return redirect()->route('claim.indexUser')->with('error', 'Claim tidak bisa diedit karena sudah diproses');
        }

        return view('claim_submission.edit', compact('claim'));
    }

    /**
     * Update Claim Submission (User)
     */
    public function update(Request $request, $id)
    {
        $claim = MstClaimSubmission::findOrFail($id);
        $this->ensureClaimOwnerAccess($claim);

        if ($claim->status !== 'open') {
            return redirect()->route('claim.indexUser')->with('error', 'Claim tidak bisa diedit karena sudah diproses');
        }

        $request->validate([
            'no_pr' => 'required|string|max:100',
            'nama_produk' => 'nullable|string|max:255',
            'submission_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'description_of_issue' => 'required|string',
            'proposed_solution' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $oldFilePath = $claim->file;
        $newFileData = $this->uploadClaimFile($request);
        $data = array_merge($request->all(), $newFileData);

        $oldData = [
            'no_pr' => $this->normalizeNullableString($claim->no_pr),
            'nama_produk' => $this->normalizeNullableString($claim->nama_produk),
            'submission_date' => $this->normalizeDateValue($claim->submission_date),
            'category' => $this->normalizeNullableString($claim->category),
            'description_of_issue' => $this->normalizeNullableString($claim->description_of_issue),
            'proposed_solution' => $this->normalizeNullableString($claim->proposed_solution),
            'file_name' => $this->normalizeNullableString($claim->file_name),
        ];

        $newData = [
            'no_pr' => $this->normalizeNullableString($data['no_pr']),
            'nama_produk' => $this->normalizeNullableString($data['nama_produk'] ?? null),
            'submission_date' => $this->normalizeDateValue($data['submission_date']),
            'category' => $this->normalizeNullableString($data['category'] ?? null),
            'description_of_issue' => $this->normalizeNullableString($data['description_of_issue']),
            'proposed_solution' => $this->normalizeNullableString($data['proposed_solution'] ?? null),
            'file_name' => $this->normalizeNullableString($data['file_name'] ?? $claim->file_name),
        ];

        $historyText = $this->buildUserUpdateHistory($oldData, $newData);

        try {
            DB::transaction(function () use ($claim, $data, $historyText) {
                $claim->update([
                    'no_pr' => $data['no_pr'],
                    'nama_produk' => $data['nama_produk'] ?? null,
                    'submission_date' => $data['submission_date'],
                    'category' => $data['category'] ?? null,
                    'description_of_issue' => $data['description_of_issue'],
                    'proposed_solution' => $data['proposed_solution'] ?? null,
                    'file' => $data['file'] ?? $claim->file,
                    'file_name' => $data['file_name'] ?? $claim->file_name,
                    'modified_at' => $this->currentUserName(),
                ]);

                // Record history saat claim diupdate
                TrsClaimSubmission::create([
                    'id_claim' => $claim->id,
                    'keterangan' => $historyText,
                    'status' => $claim->status,
                    'modified_at' => $this->currentUserName(),
                ]);
            });
        } catch (\Throwable $e) {
            // Rollback DB gagal: hapus file baru agar tidak orphan.
            if (!empty($newFileData['file']) && File::exists(public_path($newFileData['file']))) {
                File::delete(public_path($newFileData['file']));
            }

            throw $e;
        }

        // Hapus file lama hanya setelah transaksi DB sukses.
        if (!empty($newFileData['file']) && $oldFilePath && $oldFilePath !== $newFileData['file'] && File::exists(public_path($oldFilePath))) {
            File::delete(public_path($oldFilePath));
        }

        return redirect()->route('claim.indexUser')->with('success', 'Claim submission berhasil diperbarui');
    }

    /**
     * Delete Claim Submission (User) - hanya bisa delete saat status open
     */
    public function delete($id)
    {
        $claim = MstClaimSubmission::findOrFail($id);
        $this->ensureClaimOwnerAccess($claim, true);

        if ($claim->status !== 'open') {
            return response()->json(['message' => 'Claim tidak bisa dihapus karena sudah diproses'], 422);
        }

        // Hapus file jika ada
        if ($claim->file && File::exists(public_path($claim->file))) {
            File::delete(public_path($claim->file));
        }

        $claim->delete();

        return response()->json(['message' => 'Claim berhasil dihapus'], 200);
    }

    /**
     * View detail Claim Submission (User - read only)
     */
    public function viewClaim($id)
    {
        $claim = MstClaimSubmission::findOrFail($id);
        $this->ensureClaimOwnerOrProcurementAccess($claim);

        return view('claim_submission.view', compact('claim'));
    }
    

    /**
     * View detail Claim untuk Procurement (bisa proses)
     */
    public function editProc($id)
    {
        $this->ensureProcurementApprovalAccess();

        $claim = MstClaimSubmission::findOrFail($id);

        return view('claim_submission.trs_claim', compact('claim'));
    }

    /**
     * Get history claim submission (JSON)
     */
    public function getHistory($id)
    {
        $claim = MstClaimSubmission::with([
            'trsClaimSubmission' => function ($query) {
                $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
            },
        ])->findOrFail($id);
        $this->ensureClaimOwnerOrProcurementAccess($claim, true);

        $historyData = $claim->trsClaimSubmission->map(function ($item) use ($claim) {
            return [
                'no_pr' => $claim->no_pr,
                'keterangan' => $item->keterangan,
                'status' => $this->statusLabel($item->status),
                'modified_at' => $item->modified_at,
                'created_at' => $item->created_at ? $item->created_at->format('d-m-Y H:i:s') : '',
            ];
        });

        return response()->json($historyData);
    }

    /**
     * Procurement: Ubah status menjadi On Progress
     */
    public function prosesProc(Request $request, $id)
    {
        $this->ensureProcurementApprovalAccess(true);

        $claim = MstClaimSubmission::findOrFail($id);

        if ($claim->status !== 'open') {
            return response()->json(['message' => 'Claim sudah diproses sebelumnya.'], 422);
        }

        $oldSupplier = $claim->supplier;
        $oldCatatan = $claim->catatan_procurement;
        $newSupplier = $request->input('supplier');
        $newCatatan = $request->input('catatan_procurement');

        $finalSupplier = $newSupplier !== null ? $newSupplier : $claim->supplier;
        $finalCatatan = $newCatatan !== null ? $newCatatan : $claim->catatan_procurement;

        DB::transaction(function () use ($claim, $finalSupplier, $finalCatatan, $oldSupplier, $oldCatatan) {
            $claim->update([
                'status' => 'on_progress',
                'supplier' => $finalSupplier,
                'catatan_procurement' => $finalCatatan,
            ]);

            TrsClaimSubmission::create([
                'id_claim' => $claim->id,
                'status' => 'on_progress',
                'keterangan' => $this->buildProcurementHistory('[On Progress] Claim sedang diproses oleh Procurement', $oldSupplier, $finalSupplier, $oldCatatan, $finalCatatan),
                'modified_at' => $this->currentUserName(),
            ]);
        });

        return response()->json(['message' => 'Status berhasil diubah menjadi On Progress'], 200);
    }

    /**
     * Procurement: Submit catatan dan ubah status
     */
    public function submitProc(Request $request, $id)
    {
        $this->ensureProcurementApprovalAccess(true);

        $claim = MstClaimSubmission::findOrFail($id);

        $oldSupplier = $claim->supplier;
        $oldCatatan = $claim->catatan_procurement;

        $lockSupplier = $claim->status === 'finished';
        $requestedSupplier = $request->input('supplier');
        $newSupplier = $lockSupplier
            ? $oldSupplier
            : (($requestedSupplier !== null && trim((string) $requestedSupplier) !== '') ? $requestedSupplier : $oldSupplier);
        $newCatatan = $request->input('catatan_procurement');

        $normalizedOldSupplier = $this->normalizeNullableString($oldSupplier);
        $normalizedOldCatatan = $this->normalizeNullableString($oldCatatan);
        $normalizedNewSupplier = $this->normalizeNullableString($newSupplier);
        $normalizedNewCatatan = $this->normalizeNullableString($newCatatan);

        if ($normalizedOldSupplier === $normalizedNewSupplier && $normalizedOldCatatan === $normalizedNewCatatan) {
            return response()->json(['message' => 'belum ada data yang berubah'], 422);
        }

        DB::transaction(function () use ($claim, $normalizedNewCatatan, $normalizedNewSupplier, $normalizedOldSupplier, $normalizedOldCatatan) {
            $claim->update([
                'catatan_procurement' => $normalizedNewCatatan,
                'supplier' => $normalizedNewSupplier,
            ]);

            $statusLabel = $this->statusLabel($claim->status);

            TrsClaimSubmission::create([
                'id_claim' => $claim->id,
                'status' => $claim->status,
                'keterangan' => $this->buildProcurementHistory("[{$statusLabel}] Perubahan data procurement", $normalizedOldSupplier, $normalizedNewSupplier, $normalizedOldCatatan, $normalizedNewCatatan),
                'modified_at' => $this->currentUserName(),
            ]);
        });

        return response()->json(['message' => 'Data berhasil disimpan'], 200);
    }

    /**
     * Procurement: Selesaikan claim (status -> finished)
     */
    public function finishProc(Request $request, $id)
    {
        $this->ensureProcurementApprovalAccess(true);

        $claim = MstClaimSubmission::findOrFail($id);

        if ($claim->status !== 'on_progress') {
            return response()->json(['message' => 'Hanya claim dengan status On Progress yang bisa diselesaikan.'], 422);
        }

        $oldSupplier = $claim->supplier;
        $oldCatatan = $claim->catatan_procurement;

        $requestSupplier = $request->input('supplier');
        $requestCatatan = $request->input('catatan_procurement');

        $finalSupplier = $requestSupplier !== null && trim($requestSupplier) !== ''
            ? $requestSupplier
            : $claim->supplier;

        $finalCatatan = $requestCatatan !== null
            ? $requestCatatan
            : $claim->catatan_procurement;

        // Wajib ada nama supplier saat menyelesaikan claim
        if ($finalSupplier === null || trim($finalSupplier) === '') {
            return response()->json([
                'message' => 'Nama supplier wajib diisi sebelum menyelesaikan claim.'
            ], 422);
        }

        DB::transaction(function () use ($claim, $finalSupplier, $finalCatatan, $oldSupplier, $oldCatatan) {
            $claim->update([
                'status' => 'finished',
                'supplier' => $finalSupplier,
                'catatan_procurement' => $finalCatatan,
            ]);

            TrsClaimSubmission::create([
                'id_claim' => $claim->id,
                'status' => 'finished',
                'keterangan' => $this->buildProcurementHistory('[Finished] Claim telah diselesaikan', $oldSupplier, $finalSupplier, $oldCatatan, $finalCatatan),
                'modified_at' => $this->currentUserName(),
            ]);
        });

        return response()->json(['message' => 'Claim berhasil diselesaikan'], 200);
    }

    /**
     * Export approval claim submission (procurement) ke Excel
     */
    public function exportExcelProc()
    {
        $this->ensureProcurementApprovalAccess();

        $filters = request()->only(['status', 'pic', 'category', 'supplier', 'date_from', 'date_to']);

        $fileName = 'claim_submission_approval_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new ClaimSubmissionApprovalExport($filters), $fileName);
    }
}
