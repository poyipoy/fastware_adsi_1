<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Nominatif Biaya Entertainment</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
        }
        
        .page {
            page-break-after: always;
        }
        
        .page:last-child {
            page-break-after: avoid;
        }
        
        .header-section {
            margin-bottom: 10px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .logo-section {
            display: table-cell;
            width: 60px;
            vertical-align: top;
        }
        
        .company-info {
            display: table-cell;
            vertical-align: top;
            padding-left: 8px;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 2px;
        }
        
        .company-address {
            font-size: 8pt;
            line-height: 1.3;
        }
        
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 10px;
            margin-bottom: 3px;
        }
        
        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid black;
        }
        
        th {
            background-color: #f0f0f0;
            padding: 5px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }
        
        td {
            padding: 4px 3px;
            font-size: 8pt;
            vertical-align: top;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .no-col {
            width: 25px;
            text-align: center;
        }
        
        .doc-col {
            width: 70px;
        }
        
        .signature-section {
            margin-top: 30px;
        }
        
        .signature-container {
            display: table;
            width: 100%;
        }
        
        .signature-left {
            display: table-cell;
            width: 50%;
            text-align: left;
            vertical-align: top;
            padding-left: 0;
        }
        
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
            padding-right: 0;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 9pt;
        }
        
        .signature-space {
            height: 50px;
        }
        
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 3px;
            font-size: 9pt;
        }
        
        .signature-position {
            font-style: italic;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    @php
        $dataChunks = $data->chunk(10); // Bagi data per 10 baris
    @endphp
    
    @foreach($dataChunks as $chunkIndex => $chunk)
    <div class="page">
        <!-- Header -->
        <div class="header-section">
            <div class="header-content">
                <div class="logo-section">
                    <img src="{{ public_path('assets/img/logo-adasi.png') }}" alt="Logo" style="width: 55px; height: 55px;">
                </div>
                <div class="company-info">
                    <div class="company-name">PT. ASTRA DAIDO STEEL INDONESIA</div>
                    <div class="company-address">
                        Jl. Raya Kasih 1, Desa Pasir Jaya Kec. Jatiuwung<br>
                        Tangerang, 15135
                    </div>
                </div>
            </div>
            
            <div class="title">
                DAFTAR NOMINATIF BIAYA ENTERTAINMENT DAN SEJENISNYA
            </div>
            
            <div class="subtitle">
                TAHUN PAJAK {{ $tahunPajak }}
            </div>
        </div>
        
        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="no-col">No</th>
                    <th colspan="4">Pemberian Entertainment dan Sejenisnya</th>
                    <th colspan="5">Relasi Usaha yang Diberikan Entertainment dan Sejenisnya</th>
                    <th rowspan="2" class="doc-col">No. Dokumen SAP</th>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <th>Tempat</th>
                    <th>Alamat</th>
                    <th>Jenis</th>
                    <th>Jumlah (Rp)</th>
                    <th>Nama</th>
                    <th>Posisi</th>
                    <th>Nama Perusahaan</th>
                    <th>Jenis Usaha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $index => $item)
                <tr>
                    <td class="text-center">{{ ($chunkIndex * 10) + $index + 1 }}</td>
                    <td class="text-center">{{ $item->tgl ? date('d-m-Y', strtotime($item->tgl)) : '-' }}</td>
                    <td>{{ $item->tempat ?? '-' }}</td>
                    <td>{{ $item->alamat ?? '-' }}</td>
                    <td>{{ $item->jenis ?? '-' }}</td>
                    <td class="text-right">{{ $item->jumlah ?? '-' }}</td>
                    <td>{{ $item->nama ?? '-' }}</td>
                    <td>{{ $item->posisi ?? '-' }}</td>
                    <td>{{ $item->nama_perusahaan ?? '-' }}</td>
                    <td>{{ $item->jenis_usaha ?? '-' }}</td>
                    <td>{{ $item->dokumen ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Signature -->
        <div class="signature-section">
            <div class="signature-container">
                <div class="signature-left">
                    <div class="signature-title">Mengetahui,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">( _________________ )</div>
                    <div class="signature-position">Jabatan</div>
                </div>
                <div class="signature-right">
                    <div class="signature-title">Jakarta, {{ date('d F Y') }}</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">( _________________ )</div>
                    <div class="signature-position">Jabatan</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</body>
</html>
