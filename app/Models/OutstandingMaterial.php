<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class OutstandingMaterial extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ON_SHIPMENT = 'On Shipment';

    public const STATUS_ON_PRODUCTION = 'On Production';

    public const STATUS_RECEIVED = 'Received';

    public const KETERANGAN_ON_SCHEDULE = 'On Schedule';

    public const KETERANGAN_DELAY = 'Delay';

    public const KETERANGAN_CLOSED = 'Closed';

    public const PORT_TANJUNG_PRIOK = 'Tanjung Priok';

    public const PORT_TANJUNG_PERAK = 'Tanjung Perak';

    protected $fillable = [
        'supplier',
        'invoice_id',
        'type',
        'thickness',
        'width',
        'diameter',
        'length',
        'qty_pcs',
        'est_qty_kg',
        'number_invoice',
        'invoice_identity_key',
        'status',
        'estimasi_eta_port',
        'estimasi_eta_warehouse',
        'estimasi_bulan_eta',
        'keterangan',
        'estimasi_delay_eta_port',
        'estimasi_delay_eta_warehouse',
        'port',
        'number_po',
        'month_order',
        'remarks',
        'attachment_path',
        'packing_list_path',
        'mtc_path',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'thickness' => 'decimal:2',
        'width' => 'decimal:2',
        'diameter' => 'decimal:2',
        'qty_pcs' => 'decimal:2',
        'est_qty_kg' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ON_SHIPMENT,
            self::STATUS_ON_PRODUCTION,
            self::STATUS_RECEIVED,
        ];
    }

    public static function keteranganOptions(): array
    {
        return [
            self::KETERANGAN_ON_SCHEDULE,
            self::KETERANGAN_DELAY,
            self::KETERANGAN_CLOSED,
        ];
    }

    public static function portOptions(): array
    {
        return [
            self::PORT_TANJUNG_PRIOK,
            self::PORT_TANJUNG_PERAK,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function invoiceHeader(): BelongsTo
    {
        return $this->belongsTo(OutstandingMaterialInvoice::class, 'invoice_id');
    }

    public function scopeUnarchived(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query->whereNull("{$table}.archived_at")
            ->where(function (Builder $builder) use ($table): void {
                $builder->where(function (Builder $q) use ($table): void {
                    $q->whereNull("{$table}.invoice_id")
                        ->orWhereDoesntHave('invoiceHeader', function (Builder $headerQuery): void {
                            $headerQuery->whereNotNull('archived_at');
                        });
                })->where(function (Builder $q) use ($table): void {
                    $q->whereNull("{$table}.invoice_identity_key")
                        ->orWhereNotExists(function ($subQuery) use ($table): void {
                            $subQuery->select(DB::raw(1))
                                ->from('outstanding_material_invoices')
                                ->whereColumn('outstanding_material_invoices.invoice_identity_key', "{$table}.invoice_identity_key")
                                ->whereNotNull('outstanding_material_invoices.archived_at');
                        });
                });
            });
    }

    public function scopeArchived(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query->where(function (Builder $builder) use ($table): void {
            $builder->whereNotNull("{$table}.archived_at")
                ->orWhereHas('invoiceHeader', function (Builder $headerQuery): void {
                    $headerQuery->whereNotNull('archived_at');
                })->orWhere(function (Builder $fallbackQuery) use ($table): void {
                    $fallbackQuery->whereNull("{$table}.invoice_id")
                        ->whereNotNull("{$table}.invoice_identity_key")
                        ->whereExists(function ($subQuery) use ($table): void {
                            $subQuery->select(DB::raw(1))
                                ->from('outstanding_material_invoices')
                                ->whereColumn('outstanding_material_invoices.invoice_identity_key', "{$table}.invoice_identity_key")
                                ->whereNotNull('outstanding_material_invoices.archived_at');
                        });
                });
        });
    }
}
