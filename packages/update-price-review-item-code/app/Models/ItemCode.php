<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_NEW_PRODUCT = 'new_product';
    public const TYPE_UPDATE_PRICE = 'update_price';

    public const STATUS_DRAFT                = 'draft';
    public const STATUS_PENDING_PRICE_REVIEW = 'pending_price_review';
    public const STATUS_SUBMITTED            = 'submitted';
    public const STATUS_APPROVED_1           = 'approved_1';
    public const STATUS_APPROVED_2           = 'approved_2';
    public const STATUS_FINISHED             = 'finished';
    public const STATUS_CANCELLED            = 'cancelled';

    private const CURRENCIES = [
        'IDR',
        'CNY',
        'USD',
        'JPY',
    ];

    /**
     * Alur transisi status:
     *  Draft → Submitted → Approved 1 → Approved 2 → Finished
     *
     * Reject dari Submitted  → kembali ke Draft
     * Reject dari Approved 1 → kembali ke Draft
     * Reject dari Approved 2 → kembali ke Draft
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT                => [self::STATUS_SUBMITTED, self::STATUS_PENDING_PRICE_REVIEW],
        self::STATUS_PENDING_PRICE_REVIEW => [self::STATUS_SUBMITTED, self::STATUS_DRAFT],
        self::STATUS_SUBMITTED            => [self::STATUS_APPROVED_1, self::STATUS_DRAFT],
        self::STATUS_APPROVED_1           => [self::STATUS_APPROVED_2, self::STATUS_DRAFT, self::STATUS_CANCELLED],
        self::STATUS_APPROVED_2           => [self::STATUS_FINISHED, self::STATUS_DRAFT, self::STATUS_CANCELLED],
        self::STATUS_FINISHED             => [],
        self::STATUS_CANCELLED            => [],
    ];

    protected $fillable = [
        'nomor_pengajuan',
        'type',
        'category',
        'supplier',
        'product_code',
        'description',
        'qty',
        'unit',
        'amount',
        'price_per_pcs',
        'currency',
        'tanggal',
        'tanggal_lama',
        'harga_baru',
        'reason_new_price',
        'attachment',
        'selisih',
        'tanggal_harga_baru',
        'status',
        'created_by',
        'price_reviewed_by',
        'price_reviewed_at',
        'approved_by',   // Approver 1 (Jessica)
        'approved2_by',  // Approver 2 (Martinus)
        'finished_by',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'qty'              => 'decimal:2',
        'amount'           => 'decimal:2',
        'price_per_pcs'    => 'decimal:2',
        'harga_baru'       => 'decimal:2',
        'selisih'          => 'decimal:2',
        'tanggal'          => 'date',
        'tanggal_lama'     => 'date',
        'tanggal_harga_baru' => 'date',
        'price_reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approver2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved2_by');
    }

    public function finisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finished_by');
    }

    public function priceReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'price_reviewed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TrsItemCodeHistory::class, 'item_code_id');
    }

    public static function statusList(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_PRICE_REVIEW,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED_1,
            self::STATUS_APPROVED_2,
            self::STATUS_FINISHED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function typeList(): array
    {
        return [
            self::TYPE_NEW_PRODUCT,
            self::TYPE_UPDATE_PRICE,
        ];
    }

    public static function currencyList(): array
    {
        return self::CURRENCIES;
    }

    public static function currencyValidationRule(): string
    {
        return 'in:' . implode(',', self::currencyList());
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($targetStatus, $allowedTransitions, true);
    }

    public function requiresAttachment(): bool
    {
        return $this->type === self::TYPE_UPDATE_PRICE;
    }

    public function hasAttachment(): bool
    {
        return $this->attachment !== null && trim((string) $this->attachment) !== '';
    }
}
