<?php

use App\Enums\Warehouse\WarehouseStockInStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    private const LEGACY_RESERVING_STATUSES = ['WAITING_VALIDATION', 'DISCREPANCY'];

    public function up(): void
    {
        if (! Schema::hasTable('trs_wh_location_shipments') || ! Schema::hasTable('trs_wh_stock_ins')) {
            return;
        }

        $hasMapping = Schema::hasColumn('trs_wh_location_shipments', 'migrated_stock_in_id');
        $hasOriginalStatus = Schema::hasColumn('trs_wh_location_shipments', 'migration_original_status');
        if (! $hasMapping || ! $hasOriginalStatus) {
            Schema::table('trs_wh_location_shipments', function (Blueprint $table) use ($hasMapping, $hasOriginalStatus): void {
                if (! $hasMapping) {
                    $table->unsignedBigInteger('migrated_stock_in_id')->nullable()->after('stock_transaction_id');
                    $table->foreign('migrated_stock_in_id', 'wh_ship_migrated_stock_in_fk')
                        ->references('id')
                        ->on('trs_wh_stock_ins')
                        ->nullOnDelete();
                    $table->unique('migrated_stock_in_id', 'wh_ship_migrated_stock_in_unique');
                }

                if (! $hasOriginalStatus) {
                    $table->string('migration_original_status', 32)->nullable()->after('migrated_stock_in_id');
                }
            });
        }

        DB::transaction(function (): void {
            $shipments = DB::table('trs_wh_location_shipments')
                ->whereIn('status', self::LEGACY_RESERVING_STATUSES)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($shipments as $shipment) {
                $originalStatus = (string) $shipment->status;
                $stockInNumber = 'WH-IN-MIG-'.$shipment->id;
                $creationKey = Uuid::uuid5(
                    Uuid::NAMESPACE_URL,
                    'warehouse-location-shipment-to-stock-in:'.$shipment->id,
                )->toString();

                $stockIn = DB::table('trs_wh_stock_ins')
                    ->where('creation_idempotency_key', $creationKey)
                    ->lockForUpdate()
                    ->first();

                if ($stockIn === null && $shipment->migrated_stock_in_id !== null) {
                    $stockIn = DB::table('trs_wh_stock_ins')
                        ->where('id', $shipment->migrated_stock_in_id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($stockIn === null) {
                    $now = now();
                    DB::table('trs_wh_stock_ins')->insert([
                        'stock_in_number' => $stockInNumber,
                        'creation_idempotency_key' => $creationKey,
                        'validation_idempotency_key' => null,
                        'cancellation_idempotency_key' => null,
                        'status' => WarehouseStockInStatus::WAITING_VALIDATION->value,
                        'validation_result' => null,
                        'consumable_id' => $shipment->consumable_id,
                        'item_condition' => $shipment->item_condition,
                        'quantity_expected' => $shipment->quantity_sent,
                        'quantity_received' => null,
                        'received_consumable_id' => null,
                        'received_condition' => null,
                        'destination_location' => $shipment->to_location,
                        'source_location' => $shipment->from_location,
                        'notes' => $this->notes($shipment, $originalStatus),
                        'validation_notes' => null,
                        'cancellation_reason' => null,
                        'created_by' => $shipment->sent_by_user_id,
                        'creator_npk_snapshot' => $shipment->sender_npk_snapshot,
                        'creator_name_snapshot' => $shipment->sender_name_snapshot,
                        'validated_at' => null,
                        'validator_user_id' => null,
                        'validator_npk_snapshot' => null,
                        'validator_name_snapshot' => null,
                        'cancelled_by_user_id' => null,
                        'cancelled_at' => null,
                        'stock_transaction_id' => null,
                        'created_at' => $shipment->sent_at,
                        'updated_at' => $shipment->sent_at ?? $now,
                    ]);

                    $stockIn = DB::table('trs_wh_stock_ins')
                        ->where('creation_idempotency_key', $creationKey)
                        ->lockForUpdate()
                        ->first();
                }

                if ($stockIn === null) {
                    throw new RuntimeException('Konversi Pengiriman Antar Lokasi gagal membuat Stock In migrasi.');
                }

                DB::table('trs_wh_location_shipments')
                    ->where('id', $shipment->id)
                    ->update([
                        'status' => 'CANCELLED',
                        'migrated_stock_in_id' => $stockIn->id,
                        'migration_original_status' => $shipment->migration_original_status ?: $originalStatus,
                        'cancelled_by_user_id' => null,
                        'cancelled_at' => now(),
                        'cancellation_reason' => $this->cancellationReason($stockInNumber),
                        'updated_at' => now(),
                    ]);
            }
        }, 3);
    }

    public function down(): void
    {
        if (! Schema::hasTable('trs_wh_location_shipments')
            || ! Schema::hasColumn('trs_wh_location_shipments', 'migrated_stock_in_id')) {
            return;
        }

        DB::transaction(function (): void {
            $shipments = DB::table('trs_wh_location_shipments')
                ->whereNotNull('migrated_stock_in_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($shipments as $shipment) {
                $stockIn = DB::table('trs_wh_stock_ins')
                    ->where('id', $shipment->migrated_stock_in_id)
                    ->lockForUpdate()
                    ->first();

                if ($stockIn === null
                    || $stockIn->status !== WarehouseStockInStatus::WAITING_VALIDATION->value
                    || $stockIn->stock_transaction_id !== null) {
                    throw new RuntimeException(
                        'Rollback konversi Pengiriman Antar Lokasi dihentikan karena Stock In migrasi sudah tidak pending tanpa ledger.',
                    );
                }

                DB::table('trs_wh_stock_ins')->where('id', $stockIn->id)->delete();

                DB::table('trs_wh_location_shipments')
                    ->where('id', $shipment->id)
                    ->update([
                        'status' => $shipment->migration_original_status ?: 'WAITING_VALIDATION',
                        'migrated_stock_in_id' => null,
                        'migration_original_status' => null,
                        'cancelled_by_user_id' => null,
                        'cancelled_at' => null,
                        'cancellation_reason' => null,
                        'updated_at' => now(),
                    ]);
            }
        }, 3);

        Schema::table('trs_wh_location_shipments', function (Blueprint $table): void {
            $table->dropForeign('wh_ship_migrated_stock_in_fk');
            $table->dropUnique('wh_ship_migrated_stock_in_unique');
            $table->dropColumn(['migrated_stock_in_id', 'migration_original_status']);
        });
    }

    private function notes(object $shipment, string $originalStatus): string
    {
        $legacyNotes = trim((string) ($shipment->sender_notes ?? ''));
        $migrationNotes = 'Migrasi arsip Pengiriman Antar Lokasi '.$shipment->shipment_number
            .' (status asal '.$originalStatus.').';

        return mb_substr(
            $legacyNotes === '' ? $migrationNotes : $legacyNotes."\n\n".$migrationNotes,
            0,
            65535,
        );
    }

    private function cancellationReason(string $stockInNumber): string
    {
        return 'Dikonversi ke Stock In internal '.$stockInNumber
            .' saat modul Pengiriman Antar Lokasi dipensiunkan.';
    }
};
