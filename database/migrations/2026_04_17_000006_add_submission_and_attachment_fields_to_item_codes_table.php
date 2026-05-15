<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addNomorPengajuan = !Schema::hasColumn('item_codes', 'nomor_pengajuan');
        $addSupplier = !Schema::hasColumn('item_codes', 'supplier');
        $addReason = !Schema::hasColumn('item_codes', 'reason_new_price');
        $addAttachment = !Schema::hasColumn('item_codes', 'attachment');

        Schema::table('item_codes', function (Blueprint $table) use ($addNomorPengajuan, $addSupplier, $addReason, $addAttachment) {
            if ($addNomorPengajuan) {
                $table->string('nomor_pengajuan')->nullable()->after('id');
            }

            if ($addSupplier) {
                $table->string('supplier')->nullable()->after('category');
            }

            if ($addReason) {
                $table->string('reason_new_price')->nullable()->after('harga_baru');
            }

            if ($addAttachment) {
                $table->string('attachment')->nullable()->after('reason_new_price');
            }
        });

        $this->backfillSupplier();
        $this->backfillNomorPengajuan();
        $this->createNomorPengajuanUniqueIndex();
    }

    public function down(): void
    {
        try {
            Schema::table('item_codes', function (Blueprint $table) {
                $table->dropUnique('item_codes_nomor_pengajuan_unique');
            });
        } catch (\Throwable $exception) {
            // Ignore when index does not exist.
        }

        $dropNomorPengajuan = Schema::hasColumn('item_codes', 'nomor_pengajuan');
        $dropSupplier = Schema::hasColumn('item_codes', 'supplier');
        $dropReason = Schema::hasColumn('item_codes', 'reason_new_price');
        $dropAttachment = Schema::hasColumn('item_codes', 'attachment');

        Schema::table('item_codes', function (Blueprint $table) use ($dropNomorPengajuan, $dropSupplier, $dropReason, $dropAttachment) {
            if ($dropAttachment) {
                $table->dropColumn('attachment');
            }

            if ($dropReason) {
                $table->dropColumn('reason_new_price');
            }

            if ($dropSupplier) {
                $table->dropColumn('supplier');
            }

            if ($dropNomorPengajuan) {
                $table->dropColumn('nomor_pengajuan');
            }
        });
    }

    private function backfillSupplier(): void
    {
        DB::table('item_codes')
            ->where(function ($query): void {
                $query->whereNull('supplier')
                    ->orWhere('supplier', '');
            })
            ->update(['supplier' => '-']);
    }

    private function backfillNomorPengajuan(): void
    {
        $rows = DB::table('item_codes')
            ->select('id', 'type', 'created_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $usedNomor = [];
        $counterByPeriod = [];

        foreach ($rows as $row) {
            $createdAt = $row->created_at ? Carbon::parse((string) $row->created_at) : now();
            $typeCode = $row->type === 'update_price' ? 'NP' : 'IC';
            $month = $createdAt->format('m');
            $year = $createdAt->format('y');
            $periodKey = $typeCode . '-' . $month . '-' . $year;

            $sequence = ($counterByPeriod[$periodKey] ?? 0) + 1;
            $nomorPengajuan = $this->buildNomorPengajuan($sequence, $typeCode, $month, $year);

            while (isset($usedNomor[$nomorPengajuan])) {
                $sequence++;
                $nomorPengajuan = $this->buildNomorPengajuan($sequence, $typeCode, $month, $year);
            }

            $counterByPeriod[$periodKey] = $sequence;
            $usedNomor[$nomorPengajuan] = true;

            DB::table('item_codes')
                ->where('id', $row->id)
                ->update(['nomor_pengajuan' => $nomorPengajuan]);
        }
    }

    private function createNomorPengajuanUniqueIndex(): void
    {
        try {
            Schema::table('item_codes', function (Blueprint $table) {
                $table->unique('nomor_pengajuan', 'item_codes_nomor_pengajuan_unique');
            });
        } catch (\Throwable $exception) {
            // Ignore when index already exists.
        }
    }

    private function buildNomorPengajuan(int $sequence, string $typeCode, string $month, string $year): string
    {
        return sprintf('%04d/%s/PROC/%s/%s', $sequence, $typeCode, $month, $year);
    }
};
