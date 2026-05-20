<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry PDF</title>
    <style>
        @page {
            margin: 1cm;

            /* Margin 1cm di semua sisi */
        }

        body {
            font-family: 'Cambria', serif;
            padding-right: 2rem;
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
            margin-bottom: 20px;
            font-size: 0.85rem;
            /* Memperkecil ukuran font tabel */
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 2px;
            /* Mengurangi padding untuk memperkecil tabel */
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        h2 {
            margin-top: 20px;
            /* Jarak atas untuk Signature Details */
        }

        .table-n {
            border: none;
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
        <!-- Header using Table for DomPDF compatibility -->
        <table style="width: 100%; border: none; margin-bottom: 20px;">
            <tr style="border: none;">
                <td style="width: 25%; border: none; text-align: left; vertical-align: middle;">
                    @php
                        $imagePath = public_path('assets/img/AdasiLogo.png');
                        if (file_exists($imagePath)) {
                            $imageData = base64_encode(file_get_contents($imagePath));
                            $src = 'data:image/png;base64,' . $imageData;
                        } else {
                            $src = '';
                        }
                    @endphp
                    @if($src)
                        <img src="{{ $src }}" alt="Adasi Logo" style="width: 200px; max-width: 100%;">
                    @endif
                </td>
                <td style="width: 50%; border: none; text-align: center; vertical-align: middle;">
                    <h1 style="margin: 0; font-size: 24px;">Detail Inquiry</h1>
                </td>
                <td style="width: 25%; border: none;"></td>
            </tr>
        </table>

        @if($inquiry) <!-- Check if $inquiry data exists -->
            <table class="table-n" style="margin-bottom: 30px; width: 100%;">
                <thead class="table-n">
                    <tr class="table-n" style="background-color: #f2f2f2;">
                        <th style="width: 15%; padding: 5px;" class="table-n">Create By</th>
                        <th style="width: 10%; padding: 5px;" class="table-n">Category</th>
                        <th style="width: 20%; padding: 5px;" class="table-n">Reference</th>
                        <th style="width: 20%; padding: 5px;" class="table-n">Customer</th>
                        <th style="width: 15%; padding: 5px;" class="table-n">Supplier</th>
                        <th style="width: 20%; padding: 5px;" class="table-n">Date Create</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-n">
                        <td class="table-n" style="padding: 5px;">{{ $inquiry->create_by ?? '-' }}</td>
                        <td class="table-n" style="padding: 5px;">{{ $inquiry->loc_imp ?? '-' }}</td>
                        <td class="table-n" style="padding: 5px;">{{ $inquiry->kode_inquiry ?? '-' }}</td>
                        <td class="table-n" style="padding: 5px;">{{ $inquiry->customer->name_customer ?? '-' }}</td>
                        <td class="table-n" style="padding: 5px;">{{ $inquiry->supplier ?? '-' }}</td>
                        <td class="table-n" style="padding: 5px;">{{ $inquiry->created_at ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if($materials->isNotEmpty()) <!-- Check if there are materials data -->
            @php
                $firstMaterial = $materials->first();
                $hasType = !empty($firstMaterial->type_materials);
                $hasJenis = !empty($firstMaterial->jenis);
                $hasThickness = !empty($firstMaterial->thickness);
                $hasWeight = !empty($firstMaterial->weight);
                $hasInnerDia = !empty($firstMaterial->inner_diameter);
                $hasOuterDia = !empty($firstMaterial->outer_diameter);
                $hasLength = !empty($firstMaterial->length);
                $hasQty = !empty($firstMaterial->qty);
                $hasM1 = !empty($firstMaterial->m1);
                $hasM2 = !empty($firstMaterial->m2);
                $hasM3 = !empty($firstMaterial->m3);
                $hasShip = !empty($firstMaterial->ship);
                $hasSo = !empty($firstMaterial->so);
            @endphp
            <table style="width: 100%;">
                <thead>
                    <tr>
                        @if($hasType) <th>Raw Material</th> @endif
                        @if($hasJenis) <th>Shapes</th> @endif
                        @if($hasThickness) <th style="text-align:center;">Thickness</th> @endif
                        @if($hasWeight) <th style="text-align:center;">Width</th> @endif
                        @if($hasInnerDia) <th style="text-align:center;">Inner Dia</th> @endif
                        @if($hasOuterDia) <th style="text-align:center;">Outer Dia</th> @endif
                        @if($hasLength) <th style="text-align:center;">Length</th> @endif
                        @if($hasQty) <th style="text-align:center;">Qty</th> @endif
                        @if($hasM1) <th style="text-align:center;">Forecast M1</th> @endif
                        @if($hasM2) <th style="text-align:center;">Forecast M2</th> @endif
                        @if($hasM3) <th style="text-align:center;">Forecast M3</th> @endif
                        @if($hasShip) <th style="text-align:center;">Ship-to</th> @endif
                        @if($hasSo) <th style="text-align:center;">Sales Order</th> @endif
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @foreach ($materials as $index => $material)
                        <tr>
                            @if($hasType) <td>{{ $material->type_materials->type_name ?? '-' }}</td> @endif
                            @if($hasJenis) <td>{{ $material['jenis'] ?? '-' }}</td> @endif
                            @if($hasThickness) <td style="text-align:center;">{{ $material['thickness'] ?? '-' }}</td> @endif
                            @if($hasWeight) <td style="text-align:center;">{{ $material['weight'] ?? '-' }}</td> @endif
                            @if($hasInnerDia) <td style="text-align:center;">{{ $material['inner_diameter'] ?? '-' }}</td> @endif
                            @if($hasOuterDia) <td style="text-align:center;">{{ $material['outer_diameter'] ?? '-' }}</td> @endif
                            @if($hasLength) <td style="text-align:center;">{{ $material['length'] ?? '-' }}</td> @endif
                            @if($hasQty) <td style="text-align:center;">{{ $material['qty'] ?? '-' }}</td> @endif
                            @if($hasM1) <td style="text-align:center;">{{ $material['m1'] ?? '-' }}</td> @endif
                            @if($hasM2) <td style="text-align:center;">{{ $material['m2'] ?? '-' }}</td> @endif
                            @if($hasM3) <td style="text-align:center;">{{ $material['m3'] ?? '-' }}</td> @endif
                            @if($hasShip) <td style="text-align:center;">{{ $material['ship'] ?? '-' }}</td> @endif
                            @if($hasSo) <td style="text-align:center;">{{ $material['so'] ?? '-' }}</td> @endif
                            <td>{{ $material['note'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No materials data found.</p> <!-- Display message if no materials data -->
        @endif

        @if($signatures) <!-- Check if $signatures data exists -->
        <h4>Signature Details :</h4>
        <table class="table-n1">
            <thead class="table-n1">
                <tr class="table-n1">
                    <th style="width: 20%;" class="table-n1">Submitted</th>
                    <th style="width: 20%;" class="table-n1">Ka. Sie</th>
                    <th style="width: 20%;" class="table-n1">Ka. Dept</th>
                    <th style="width: 20%;" class="table-n1">Inventory</th>
                    <th style="width: 20%;" class="table-n1">Purchasing</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-n1">
                    <td class="table-n1">
                        <p style="color: crimson;">Proposed</p>
                        <p style="font-size: 8pt;">{{ $signatures['submitted'] }}</p>
                        <small>Date : {{ \Carbon\Carbon::parse($inquiry->created_at)->format('d/m/Y H:i') }}</small>
                    </td>
                    <td class="table-n1">
                        <p style="color: crimson;">{{ $inquiry->kasie ? 'Approved' : 'Waiting Approval' }}</p>
                        <p style="font-size: 8pt;">{{ $signatures['approved_kasie'] }}</p>
                        <small>
                            Date : {{ $inquiry->approved_kasie_at ? \Carbon\Carbon::parse($inquiry->approved_kasie_at)->format('d/m/Y H:i') : 'N/A' }}
                        </small>
                    </td>
                    <td class="table-n1">
                        <p style="color: crimson;">{{ $inquiry->kadept ? 'Approved' : 'Waiting Approval' }}</p>
                        <p style="font-size: 8pt;">{{ $signatures['approved_kadept'] }}</p>
                        <small>
                            Date : {{ $inquiry->approved_kadept_at ? \Carbon\Carbon::parse($inquiry->approved_kadept_at)->format('d/m/Y H:i') : 'N/A' }}
                        </small>
                    </td>
                    <td class="table-n1">
                        <p style="color: crimson;">{{ $inquiry->inventory ? 'Approved' : 'Waiting Approval' }}</p>
                        <p style="font-size: 8pt;">{{ $signatures['approved_inventory'] }}</p>
                        <small>
                            Date : {{ $inquiry->approved_inventory_at ? \Carbon\Carbon::parse($inquiry->approved_inventory_at)->format('d/m/Y H:i') : 'N/A' }}
                        </small>
                    </td>
                    <td class="table-n1">
                        <p style="color: crimson;">{{ $inquiry->purchasing ? 'Confirmed' : 'Waiting Confirmation' }}</p>
                        <p style="font-size: 8pt;">{{ $signatures['confirmed_purchasing'] }}</p>
                        <small>
                            Date : {{ $inquiry->confirmed_purchasing_at ? \Carbon\Carbon::parse($inquiry->confirmed_purchasing_at)->format('d/m/Y H:i') : 'N/A' }}
                        </small>
                    </td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>
</body>

</html>
