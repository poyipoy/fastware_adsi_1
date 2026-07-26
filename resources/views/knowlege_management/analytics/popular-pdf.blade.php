<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Materi Populer — data operasional, bukan KPI</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta, .notice { margin-bottom: 10px; }
        .notice { padding: 8px; background: #fff3cd; border: 1px solid #ffe69c; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 5px; vertical-align: top; }
        th { background: #eee; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <h1>Materi Populer — data operasional, bukan KPI</h1>
    <div class="meta">
        Dibuat {{ $generated_at->format('d-m-Y H:i:s') }} WIB.
        Filter kategori: {{ $filters['category'] ?? 'semua' }};
        tag: {{ $filters['tag_ids'] === [] ? 'semua' : implode(', ', $filters['tag_ids']) }}.
    </div>
    <div class="notice">
        Counter historis sebelum hardening mungkin memiliki keterbatasan. Laporan ini bukan KPI dan tidak
        memuat identitas atau aktivitas pembaca individual.
        @if ($limit_reached)
            <strong>
                {{ $truncated
                    ? 'Hasil melebihi 10.000 row dan dipotong pada batas export.'
                    : 'Hasil mencapai batas export 10.000 row.' }}
            </strong>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Tag</th>
                <th>Total Lihat</th>
                <th>Pembaca Selesai</th>
                <th>Like</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['id'] }}</td>
                    <td>{{ $row['judul'] }}</td>
                    <td>{{ $row['kategori'] }}</td>
                    <td>{{ $row['tags'] }}</td>
                    <td class="number">{{ $row['total_views'] }}</td>
                    <td class="number">{{ $row['completed_readers'] }}</td>
                    <td class="number">{{ $row['likes_count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada materi untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
