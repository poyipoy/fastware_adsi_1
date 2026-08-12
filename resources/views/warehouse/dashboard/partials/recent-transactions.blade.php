<div class="warehouse-table-wrap">
    <table class="table warehouse-table align-middle" aria-label="Transaksi terbaru">
        <thead>
            <tr>
                <th scope="col">Waktu</th>
                <th scope="col">Tipe</th>
                <th scope="col">Barang</th>
                <th scope="col">Jumlah</th>
                <th scope="col">Karyawan verifikator</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentTransactions as $transaction)
                @php
                    $transactionType = $transaction->transaction_type?->value;
                    $adjustmentDelta = $transactionType === 'ADJUSTMENT'
                        ? \App\Services\Warehouse\WarehouseQuantity::compare($transaction->stock_after, $transaction->stock_before)
                        : 0;
                    $adjustmentDirection = match (true) {
                        $adjustmentDelta > 0 => 'Stock In',
                        $adjustmentDelta < 0 => 'Stock Out',
                        default => null,
                    };
                @endphp
                <tr>
                    <td>{{ optional($transaction->transaction_at)->format('d/m H:i') }}</td>
                    <td>
                        <x-warehouse.status-badge :status="$transactionType" context="transaction" />
                        @if ($adjustmentDirection)
                            <small class="d-block warehouse-transaction-direction">{{ $adjustmentDirection }}</small>
                        @endif
                    </td>
                    <td>{{ $transaction->consumable?->item_name }}</td>
                    <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</td>
                    <td>{{ $transaction->verified_user_name ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-warehouse.empty-state title="Belum ada transaksi bulan ini." message="Tidak ada pergerakan pada bulan berjalan." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($recentTransactions->hasPages())
    <div class="warehouse-panel-pagination">{{ $recentTransactions->links('pagination::warehouse-bootstrap-5') }}</div>
@endif
