<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'attachment_path',
        'packing_list_path',
        'mtc_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'thickness' => 'decimal:2',
        'width' => 'decimal:2',
        'diameter' => 'decimal:2',
        'qty_pcs' => 'decimal:2',
        'est_qty_kg' => 'decimal:2',
    ];

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
}
