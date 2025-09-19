<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Evaluasi Supplier - {{ optional($supplier)->supplier_name }}</title>
    <style>
        @media print {
            body { 
                margin: 0; 
                background: white;
            }
            .no-print { 
                display: none; 
            }
            .document { 
                margin: 0; 
                box-shadow: none; 
                max-width: 100%;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.4;
            color: #333;
            background: #f0f0f0; /* Latar belakang abu-abu untuk mode non-print */
        }
        
        /* =================== CSS UNTUK STRUKTUR PRINT BARU =================== */
        table.document {
            width: 100%;
            max-width: 210mm;
            margin: 20px auto;
            background: white;
            border-collapse: collapse;
        }

        table.document thead {
            display: table-header-group; /* Kunci agar header terulang */
        }
        
        table.document tfoot {
            display: table-footer-group; /* Untuk footer jika ada */
        }

        table.document td {
            padding: 0;
        }

        .header-content-wrapper {
            padding: 15mm 15mm 0 15mm; /* Padding untuk konten di dalam header */
            border-bottom: 2px solid #6b7280;
        }
        /* =================== AKHIR CSS PRINT BARU =================== */
        
        .page {
            padding: 15mm;
            position: relative;
            page-break-before: always;
            break-before: page;
        }

        /* Halaman pertama tidak memerlukan page break di atasnya */
        .page:first-of-type {
            page-break-before: avoid;
            break-before: auto;
        }
        
        .header {
            display: flex;
            align-items: center;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin-right: 12px;
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .company-info h1 {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3px;
        }
        
        .company-info p {
            color: #4b5563;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .doc-title {
            text-align: center;
            margin-bottom: 12px;
        }
        
        .doc-title h2 {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3px;
        }
        
        .doc-title p {
            color: #4b5563;
            font-size: 11px;
        }
        
        .supplier-code-banner {
            background: white;
            color: #1f2937;
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 12px;
            border: 1px solid #9ca3af;
        }
        
        .code-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #4b5563;
            margin-bottom: 3px;
        }
        
        .code-value {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1.5px;
            font-family: 'Courier New', monospace;
            color: #1f2937;
        }
        
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .section-title {
            background: #f3f4f6;
            padding: 6px 10px;
            border-left: 3px solid #6b7280;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 10px;
        }
        
        .form-field {
            margin-bottom: 8px;
        }
        
        .form-field.full-width {
            grid-column: 1 / -1;
        }
        
        .form-field label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 2px;
            font-size: 11px;
        }
        
        .form-field .value {
            border: 1px solid #9ca3af;
            padding: 5px 8px;
            border-radius: 3px;
            background: white;
            min-height: 24px;
            font-size: 11px;
            line-height: 1.2;
            word-wrap: break-word;
        }
        
        /* =================== CSS FOTO YANG DIPERBAIKI =================== */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* 2 Kolom */
            gap: 10px;
            margin-top: 8px;
        }
        
        .photo-item {
            border: 1px solid #9ca3af;
            border-radius: 4px;
            padding: 8px;
            background: white;
            /* HAPUS min-height agar tinggi kontainer fleksibel */
        }
        
        .photo-item img {
            max-width: 100%;
            /* HAPUS max-height agar gambar bisa memanjang ke bawah */
            height: auto; /* Pastikan rasio aspek terjaga */
            object-fit: contain;
            display: block;
        }
        /* =================== AKHIR CSS FOTO =================== */
        
        .photo-placeholder {
            color: #6b7280;
            font-size: 9px;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .eval-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .eval-table th,
        .eval-table td {
            border: 1px solid #9ca3af;
            padding: 6px;
            text-align: center;
        }
        
        .eval-table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #1f2937;
            font-size: 10px;
        }
        
        .eval-table .criteria {
            text-align: left;
            font-weight: 500;
            font-size: 10px;
            padding-left: 5px;
        }
        
        .score-cell {
            width: 40px;
            background: #f9fafb;
            font-weight: bold;
            color: #4b5563;
            font-size: 11px;
        }
        
        .average-row {
            background: #f3f4f6;
            font-weight: bold;
        }
        
        .average-row td {
            color: #1f2937;
        }
        
        .approval-section {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .approval-result {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .status-text {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .status-text.approved { color: #059669; }
        .status-text.rejected { color: #dc2626; }

        .description {
            font-size: 11px;
            color: #4b5563;
        }
        
        .print-btn {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #4b5563;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            z-index: 100;
        }
        
        .print-btn:hover {
            background: #374151;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Print Dokumen</button>
    
    <table class="document">
        {{-- BAGIAN KOP SURAT (HEADER) YANG AKAN TERULANG DI SETIAP HALAMAN CETAK --}}
        <thead>
            <tr>
                <td>
                    <div class="header-content-wrapper">
                        <div class="header">
                            <div class="logo">
                                <img src="{{ asset('assets/img/logo-adasi.png') }}" alt="Logo PT. Astra Daido Steel Indonesia">
                            </div>
                            <div class="company-info">
                                <h1>PT. ASTRA DAIDO STEEL INDONESIA</h1>
                                <p>Kawasan Industri Delta Silicon 8</p>
                                <p>Jl. Albasia Raya K.07 No. 003, Lippo Cikarang, Desa Cicau, Cikarang Pusat - Kab. Bekasi, 17816</p>
                                <p>Telp. (+62) 21 3950 6699</p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </thead>

        {{-- BAGIAN ISI DOKUMEN --}}
        <tbody>
            <tr>
                <td>
                    <div class="page">
                        <div class="doc-title">
                            <h2>LAPORAN EVALUASI SUPPLIER</h2>
                            <p>Dokumen No: SUP-EVAL-{{ date('Y') }}-{{ $form->id }} | Tanggal: {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                        </div>
                        
                        <div class="supplier-code-banner">
                            <div class="code-label">KODE SUPPLIER</div>
                            <div class="code-value">{{ $form->supplier_kode ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Informasi Supplier</div>
                            <div class="form-grid">
                                <div class="form-field"><label>Nama Supplier</label><div class="value">{{ optional($supplier)->supplier_name ?? '-' }}</div></div>
                                <div class="form-field"><label>Kategori</label><div class="value">{{ optional($supplier)->kategori ?? '-' }}</div></div>
                                <div class="form-field full-width"><label>Alamat</label><div class="value">{{ optional($supplier)->alamat ?? '-' }}</div></div>
                                <div class="form-field"><label>NPWP</label><div class="value">{{ optional($supplier)->npwp ?? '-' }}</div></div>
                                <div class="form-field"><label>Telepon</label><div class="value">{{ optional($supplier)->telp ?? '-' }}</div></div>
                                <div class="form-field"><label>Direktur</label><div class="value">{{ optional($supplier)->director ?? '-' }}</div></div>
                                <div class="form-field"><label>PIC</label><div class="value">{{ optional($supplier)->pic ?? '-' }}</div></div>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Standar Kualitas & Sertifikasi</div>
                            <div class="form-grid">
                                <div class="form-field"><label>Memiliki Standar Kualitas</label><div class="value">{{ optional($supplier)->has_quality_standard ? '✓ Ya' : '✗ Tidak' }}</div></div>
                                <div class="form-field"><label>Sertifikat Kualitas</label><div class="value">{{ optional($supplier)->quality_certificate ?? '-' }}</div></div>
                                <div class="form-field"><label>Penerbit Sertifikat</label><div class="value">{{ optional($supplier)->quality_certificate_from ?? '-' }}</div></div>
                                <div class="form-field"><label>Penanggung Jawab Kualitas</label><div class="value">@if(optional($supplier)->has_quality_responsible) ✓ Ada - {{ optional($supplier)->quality_responsible_name }} @else ✗ Tidak Ada @endif</div></div>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Kepatuhan & Keselamatan</div>
                            <div class="form-grid">
                                <div class="form-field"><label>Material Safety Data Sheet</label><div class="value">{{ optional($supplier)->has_material_safety ? '✓ Tersedia' : '✗ Tidak' }}</div></div>
                                <div class="form-field"><label>Standar Keselamatan</label><div class="value">{{ optional($supplier)->has_safety ? '✓ Memenuhi' : '✗ Tidak' }}</div></div>
                                <div class="form-field"><label>Mempekerjakan Anak di Bawah Umur</label><div class="value">{{ optional($supplier)->employs_underage ? '✗ Ya' : '✓ Tidak' }}</div></div>
                                <div class="form-field"><label>Membayar Upah Minimum</label><div class="value">{{ optional($supplier)->pays_min_wage ? '✓ Ya' : '✗ Tidak' }}</div></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="page">
                        @if(optional($supplier)->lampiran_compro)
                            <div class="section">
                                <div class="section-title">Company Profile Supplier</div>
                                <div style="text-align: center; margin-top: 10px;">
                                    <img src="{{ asset('assets/form_supplier/visit/compro/' . $supplier->lampiran_compro) }}" 
                                        alt="Company Profile {{ optional($supplier)->supplier_name }}" 
                                        style="max-width: 80%; height: auto; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                </div>
                            </div>
                        @else
                            <div class="section">
                                <div class="section-title">Company Profile Supplier</div>
                                <div style="text-align: center; color: #6b7280; font-size: 11px; padding: 10px; border: 1px dashed #ddd; border-radius: 5px; margin-top: 10px;">
                                    Tidak ada Company Profile tersedia.
                                </div>
                            </div>
                        @endif
                        
                        @if($visit)
                            <div class="section">
                                <div class="section-title">Dokumentasi Foto Kunjungan</div>
                                <div class="form-grid">
                                    <div class="form-field"><label>Tanggal Kunjungan</label><div class="value">{{ optional($visit->tanggal_visit)->format('d F Y') ?? '-' }}</div></div>
                                    <div class="form-field"><label>Lokasi</label><div class="value">{{ $visit->lokasi ?? '-' }}</div></div>
                                </div>
                                
                                <div class="photo-grid">
                                    @forelse($visit->lampiran_foto_array as $foto)
                                        <div class="photo-item">
                                            <img src="{{ asset('assets/form_supplier/visit/photos/' . $foto) }}" alt="Foto Kunjungan">
                                        </div>
                                    @empty
                                        @for ($i = 0; $i < 2; $i++)
                                            <div class="photo-item">
                                                <div class="photo-placeholder">Tidak Ada Foto</div>
                                            </div>
                                        @endfor
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    @if($visit)
                        <div class="page">
                            <div class="section">
                                <div class="section-title">Detail Kunjungan</div>
                                <div class="form-grid">
                                    <div class="form-field"><label>Tipe Evaluasi</label><div class="value">{{ $visit->type ?? '-' }}</div></div>
                                    <div class="form-field"><label>Tanggal Kunjungan</label><div class="value">{{ optional($visit->tanggal_visit)->format('d F Y') ?? '-' }}</div></div>
                                    <div class="form-field full-width"><label>Lokasi</label><div class="value">{{ $visit->lokasi ?? '-' }}</div></div>
                                    <div class="form-field full-width"><label>Catatan Khusus</label><div class="value" style="min-height: 60px; line-height: 1.4;">{{ $visit->catatan ?? 'Tidak ada catatan khusus.' }}</div></div>
                                </div>
                            </div>
                            
                            <div class="section">
                                <div class="section-title">Tabel Penilaian</div>
                                <table class="eval-table">
                                    <thead>
                                        <tr>
                                            <th class="criteria">Kriteria Penilaian</th>
                                            <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                                            <th>Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="criteria">Kelengkapan APD</td>
                                            <td>@if($visit->kelengkapan_apd == 1)✓@endif</td><td>@if($visit->kelengkapan_apd == 2)✓@endif</td><td>@if($visit->kelengkapan_apd == 3)✓@endif</td><td>@if($visit->kelengkapan_apd == 4)✓@endif</td><td>@if($visit->kelengkapan_apd == 5)✓@endif</td>
                                            <td class="score-cell">{{ $visit->kelengkapan_apd ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="criteria">Fasilitas</td>
                                            <td>@if($visit->fasilitas == 1)✓@endif</td><td>@if($visit->fasilitas == 2)✓@endif</td><td>@if($visit->fasilitas == 3)✓@endif</td><td>@if($visit->fasilitas == 4)✓@endif</td><td>@if($visit->fasilitas == 5)✓@endif</td>
                                            <td class="score-cell">{{ $visit->fasilitas ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="criteria">Alat Ukur</td>
                                            <td>@if($visit->alat_ukur == 1)✓@endif</td><td>@if($visit->alat_ukur == 2)✓@endif</td><td>@if($visit->alat_ukur == 3)✓@endif</td><td>@if($visit->alat_ukur == 4)✓@endif</td><td>@if($visit->alat_ukur == 5)✓@endif</td>
                                            <td class="score-cell">{{ $visit->alat_ukur ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="criteria">Lisensi</td>
                                            <td>@if($visit->lisensi == 1)✓@endif</td><td>@if($visit->lisensi == 2)✓@endif</td><td>@if($visit->lisensi == 3)✓@endif</td><td>@if($visit->lisensi == 4)✓@endif</td><td>@if($visit->lisensi == 5)✓@endif</td>
                                            <td class="score-cell">{{ $visit->lisensi ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="criteria">5R (Ringkas, Rapi, Resik, Rawat, Rajin)</td>
                                            <td>@if($visit->lima_r == 1)✓@endif</td><td>@if($visit->lima_r == 2)✓@endif</td><td>@if($visit->lima_r == 3)✓@endif</td><td>@if($visit->lima_r == 4)✓@endif</td><td>@if($visit->lima_r == 5)✓@endif</td>
                                            <td class="score-cell">{{ $visit->lima_r ?? 'N/A' }}</td>
                                        </tr>
                                        <tr class="average-row">
                                            <td class="criteria"><strong>RATA-RATA NILAI</strong></td>
                                            <td colspan="5"></td>
                                            <td><strong>{{ number_format($averageScore, 1) }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="section approval-section">
                                <div class="section-title">Hasil Akhir Evaluasi</div>
                                <div class="approval-result">
                                    @if(!empty($form->supplier_kode))
                                        <div class="status-text approved">DISETUJUI</div>
                                        <p class="description">Supplier telah memenuhi kriteria dan berhasil disetujui.</p>
                                    @else
                                        <div class="status-text rejected">DITOLAK</div>
                                        <p class="description">Berdasarkan hasil evaluasi, supplier ini belum memenuhi kriteria.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>