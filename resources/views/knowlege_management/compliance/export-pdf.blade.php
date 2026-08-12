<!doctype html><html lang="id"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#172033}h1{font-size:18px;margin-bottom:4px}.note{color:#526075;margin-bottom:18px}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #ccd3de;padding:7px;text-align:left}th{background:#edf1f6}.summary{display:inline-block;border:1px solid #ccd3de;padding:10px;margin:0 8px 8px 0;min-width:105px}.summary strong{display:block;font-size:17px}
</style></head><body>
<h1>Compliance Knowledge Management — Agregat</h1>
<div class="note">Dibuat {{ $generatedAt->format('d-m-Y H:i') }} WIB. Laporan ini bukan KPI dan tidak memuat skor individu, telemetry halaman, komentar, atau reaksi.</div>
@foreach (['Penerima'=>'recipients','Selesai'=>'completed','Exempted'=>'exempted','Overdue'=>'overdue'] as $label=>$key)<div class="summary"><span>{{ $label }}</span><strong>{{ $summary[$key] }}</strong></div>@endforeach
<h2>Cohort departemen (minimal 5 pengguna)</h2><table><thead><tr><th>Departemen</th><th>Cohort</th><th>Selesai</th></tr></thead><tbody>
@forelse($cohorts as $cohort)<tr><td>{{ $cohort->department_snapshot }}</td><td>{{ $cohort->cohort_size }}</td><td>{{ $cohort->completed_count }}</td></tr>@empty<tr><td colspan="3">Belum ada cohort yang memenuhi batas privasi.</td></tr>@endforelse
</tbody></table></body></html>
