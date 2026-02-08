<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TrsCurrency extends Model
{
    use HasFactory;

    protected $table = 'trs_cur';

    protected $fillable = [
        'kurs',
        'currency',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'kurs' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope untuk filter berdasarkan bulan dan tahun
     */
    public function scopeByMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->whereYear('created_at', $year)
                     ->whereMonth('created_at', $month);
    }

    /**
     * Scope untuk filter berdasarkan currency
     */
    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    /**
     * Get list of currencies grouped by month with order
     */
    public static function getCurrencyListByMonth(): array
    {
        $currencies = self::selectRaw('
                id,
                currency,
                kurs,
                DATE_FORMAT(created_at, "%Y-%m") as month_year,
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                created_at
            ')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('month_year');

        $result = [];
        foreach ($currencies as $monthYear => $items) {
            $date = Carbon::parse($monthYear . '-01');
            $result[] = [
                'month_year' => $monthYear,
                'month_name' => $date->format('F Y'),
                'count' => $items->count(),
                'items' => $items->map(function ($item, $index) {
                    return [
                        'id' => $item->id,
                        'currency' => $item->currency,
                        'kurs' => $item->kurs,
                        'order' => $index + 1,
                        'label' => 'Urutan ' . ($index + 1) . ' - ' . $item->currency . ' (Kurs: ' . number_format($item->kurs, 2) . ')',
                        'created_at' => $item->created_at->format('d M Y H:i'),
                    ];
                })->values()->toArray(),
            ];
        }

        return $result;
    }

    /**
     * Get latest kurs by currency
     */
    public static function getLatestKurs(string $currency = 'IDR'): ?float
    {
        $latest = self::where('currency', $currency)
            ->orderBy('created_at', 'desc')
            ->first();

        return $latest ? $latest->kurs : null;
    }

    /**
     * Get kurs by ID
     */
    public static function getKursById(int $id): ?float
    {
        $currency = self::find($id);
        return $currency ? $currency->kurs : null;
    }
}