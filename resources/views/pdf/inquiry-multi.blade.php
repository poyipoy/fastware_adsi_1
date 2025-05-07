<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry PDF</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;

            /* Margin 1cm di semua sisi */
        }

        body {
            font-family: 'Cambria', serif;
            padding: 0;
            font-size: 10px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            /* Menggunakan flexbox untuk layout */
            align-items: center;
            /* Menyelaraskan item secara vertikal di tengah */
            justify-content: flex-start;
            /* Mengatur agar gambar dan teks dimulai dari kiri */
            margin-bottom: 30px;
            /* Margin bawah untuk jarak */
        }

        .header img {
            width: 20%;
            /* Lebar gambar yang diinginkan */
            height: auto;
            /* Memastikan aspek rasio terjaga */
            margin-right: 20px;
            /* Jarak antara gambar dan teks */
        }

        .header h1 {
            margin: 0;
            /* Menghapus margin default */
            text-align: center;
            /* Menyelaraskan teks ke tengah */
            flex-grow: 1;
            /* Memungkinkan teks untuk mengambil ruang yang tersisa */
        }

        .header-info {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            /* Membagi menjadi 5 kolom */
            gap: 10px;
            /* Jarak antar kolom */
            margin-bottom: 20px;
            /* Jarak antara header dan tabel */
        }

        .header-info div {
            text-align: left;
            /* Rata kiri untuk teks */
            margin-top: 3px;
        }

        .header-info label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px; /* Sesuaikan ukuran font agar tabel tidak terlalu besar */
            table-layout: fixed; /* Pastikan tabel tidak melebihi batas halaman */
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 2px; /* Kurangi padding agar lebih hemat ruang */
            text-align: center; /* Buat rata tengah agar lebih rapi */
            word-wrap: break-word; /* Memastikan teks panjang tidak keluar */
            overflow: hidden;
        }
        

        table th {
            background-color: #f2f2f2;
        }

        h2 {
            margin-top: 20px;
            /* Jarak atas untuk Signature Details */
        }

        .table-n,
.table-n th,
.table-n td {
    text-align: left !important;
    border: none !important;
    background-color: transparent !important;
    font-size: 9pt;
}


        .table-n1 {
            border: none;
            background-color: transparent;
            
        }

        p {
            font-family: 'Cambria', serif;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1%;

        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('assets/img/AdasiLogo.png') }}" alt="">
            <h1>Detail Inquiry</h1>
        </div>

        @if($LatestInquiry) <!-- Check if $inquiry data exists -->
            <table class="table-n">
                <thead class="table-n">
                    <tr class="table-n">
                        {{-- @if($inquiry->create_by) <th style="width: 20%;" class="table-n">Create By</th> @endif --}}
                        @if($LatestInquiry->loc_imp) <th style="width: 20%;" class="table-n">Category</th> @endif
                        {{-- @if($inquiry->kode_inquiry) <th style="width: 20%;" class="table-n">Reference</th> @endif
                        @if($inquiry->customer) <th style="width: 20%;" class="table-n">Customer</th> @endif
                        @if($inquiry->supplier) <th style="width: 20%;" class="table-n">Supplier</th> @endif
                        @if($inquiry->created_at) <th style="width: 20%;" class="table-n">Date Create</th> @endif --}}
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-n">
                        {{-- @if($inquiry->create_by) <td class="table-n">{{ $inquiry->create_by }}</td> @endif --}}
                        @if($LatestInquiry->loc_imp) <td class="table-n">{{ $LatestInquiry->loc_imp }}</td> @endif
                        {{-- @if($inquiry->kode_inquiry) <td class="table-n">{{ $inquiry->kode_inquiry }}</td> @endif
                        @if($inquiry->customer) <td class="table-n">{{ $inquiry->customer->name_customer ?? '-' }}</td> @endif
                        @if($inquiry->supplier) <td class="table-n">{{ $inquiry->supplier }}</td> @endif
                        @if($inquiry->created_at) <td class="table-n">{{ $inquiry->created_at }}</td> @endif --}}
                    </tr>
                </tbody>
            </table>
        @endif

        @if($materials->isNotEmpty()) <!-- Check if there are materials data -->
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">Klasifikasi</th>
                        <th style="width: 50px;">Region</th>
                        <th style="width: 100px;">Raw Material</th>
                        <th style="width: 50px;">Shapes</th>
                        <th style="width: 40px;text-align:center;">Thickness</th>
                        <th style="width: 40px;text-align:center;">Width</th>
                        <th style="width: 40px; text-align:center;">Inner Dia</th>
                        <th style="width: 40px; text-align:center;">Outer Dia</th>
                        <th style="width: 50px;text-align:center;">Length</th>
                        <th style="width: 50px; text-align:center;">Qty</th>
                        <th style="width: 50px; text-align:center;">Forecast Month 1</th>
                        <th style="width: 50px; text-align:center;">Forecast Month 2</th>
                        <th style="width: 50px; text-align:center;">Forecast Month 3</th>
                        <th style="width: 70px; text-align:center;">Ship-to</th>
                        <th style="width: 50px; text-align:center;">Sales Order</th>
                        <th style="width: 50px;">Remark</th>
                        <th style="width: 50px;">Customer</th>
                        <th style="width: 50px;">Partner</th>
                        <th style="width: 50px;">No PO</th>
                        <th style="width: 50px;">progress</th>
                        <th style="width: 50px;">Submited By</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @foreach ($materials as $index => $material)
                        <tr>
                            <td>{{ $material['klasifikasi'] }}</td>
                            <td>{{ $material->inquirySales1->region ?? 'N/A' }}</td>
                            <td>{{ $material->type_materials->type_name ?? 'N/A' }}</td>
                            <td>{{ $material['jenis'] }}</td>
                            <td style="text-align:center;">{{ $material['thickness'] }}</td>
                            <td style="text-align:center;">{{ $material['weight'] }}</td>
                            <td style="text-align:center;">{{ $material['inner_diameter'] }}</td>
                            <td style="text-align:center;">{{ $material['outer_diameter'] }}</td>
                            <td style="text-align:center;">{{ $material['length'] }}</td>
                            <td style="text-align:center;">{{ $material['qty'] }}</td>
                            <td style="text-align:center;">{{ $material['m1'] }}</td> 
                            <td style="text-align:center;">{{ $material['m2'] }}</td> 
                            <td style="text-align:center;">{{ $material['m3'] }}</td> 
                            <td style="text-align:center;">{{ $material['ship'] }}</td>
                            <td>{{ $material['so'] }}</td>
                            <td>{{ $material['note'] }}</td>
                            <td>
                                @php
                                    $customerNames = [];
                                    $decoded = json_decode($material->customer, true);
                            
                                    // Cek apakah hasil decode adalah array
                                    if (is_array($decoded)) {
                                        foreach ($decoded as $item) {
                                            // Jika item berupa ID (angka dan cocok di daftar customer), ambil nama dari relasi
                                            $found = false;
                                            foreach ($customers as $customer) {
                                                if ($customer->id == $item) {
                                                    $customerNames[] = $customer->name_customer;
                                                    $found = true;
                                                    break;
                                                }
                                            }
                            
                                            // Jika tidak ditemukan sebagai ID, anggap itu adalah nama langsung
                                            if (!$found) {
                                                $customerNames[] = $item;
                                            }
                                        }
                                    } else {
                                        // Bukan array → bisa ID atau nama langsung
                                        $found = false;
                                        foreach ($customers as $customer) {
                                            if ($customer->id == $material->customer) {
                                                $customerNames[] = $customer->name_customer;
                                                $found = true;
                                                break;
                                            }
                                        }
                            
                                        // Jika tidak cocok ID, anggap nama langsung
                                        if (!$found && !empty($material->customer)) {
                                            $customerNames[] = $material->customer;
                                        }
                                    }
                                @endphp
                                <span>{{ implode(', ', $customerNames) }}</span>
                            </td>
                            <td>
                                @php
                                    $partnerName = '';
                                    foreach ($users as $user) {
                                        if ($user->id == $material->create_by) {
                                            $partnerName = $user->name;
                                            break;
                                        }
                                    }
                                @endphp
                                <span>{{ $partnerName }}</span>
                            </td>
                            <td>{{ $material['nopo'] }}</td>
                            <td>{{ $material['progress'] }}</td>
                            <td>
                                @if ($material->inquirySales1->kadept_id)
                                    @php
                                        $kadeptName = '';
                                        foreach ($users as $user) {
                                            if ($user->id == $material->inquirySales1->kadept_id) {
                                                $kadeptName = $user->name;
                                                break;
                                            }
                                        }
                                    @endphp
                                    <div style="font-size: 6pt; color: green;">
                                        Approved by {{ $kadeptName }}<br>
                                        <small>
                                            Date: {{ $material->inquirySales1->approved_kadept_at ? \Carbon\Carbon::parse($material->inquirySales1->approved_kadept_at)->format('d/m/Y H:i') : 'N/A' }}
                                        </small>
                                    </div>
                                @else
                                    <div style="font-size: 6pt; color: crimson;">
                                        Waiting Approval<br>
                                        <small>Date: N/A</small>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
        @else
            <p>No materials data found.</p> <!-- Display message if no materials data -->
        @endif

        @if($signaturesList)
    <div style="page-break-inside: avoid;">
        <h4 style="margin-bottom: 6px;">Signature Details :</h4>

        <table class="table-n1">
            <thead>
                <tr>
                    <th style="width: 20%;">Inventory</th>
                    <th style="width: 20%;">Purchasing</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <p style="color: {{ $LatestInquiry->inventory ? 'green' : 'crimson' }}; font-size: 9pt;">
                            {{ $LatestInquiry->inventory ? 'Approved' : 'Waiting Approval' }}
                        </p>
                        <p style="font-size: 8pt;">
                            {{ $signaturesList[$LatestInquiry->id]['approved_inventory'] }}
                        </p>
                        <small>
                            Date: {{ $LatestInquiry->approved_inventory_at ? \Carbon\Carbon::parse($LatestInquiry->approved_inventory_at)->format('d/m/Y H:i') : 'N/A' }}
                        </small>
                    </td>
                    <td>
                        <p style="color: {{ $LatestInquiry->purchasing ? 'green' : 'crimson' }}; font-size: 9pt;">
                            {{ $LatestInquiry->purchasing ? 'Confirmed' : 'Waiting Confirmation' }}
                        </p>
                        <p style="font-size: 8pt;">
                            {{ $signaturesList[$LatestInquiry->id]['confirmed_purchasing'] }}
                        </p>
                        <small>
                            Date: {{ $LatestInquiry->confirmed_purchasing_at ? \Carbon\Carbon::parse($LatestInquiry->confirmed_purchasing_at)->format('d/m/Y H:i') : 'N/A' }}
                        </small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endif

    </div>
</body>

</html>
