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
        <div class="header">
            <img src="{{ asset('assets/img/AdasiLogo.png') }}" alt="">
            <h1>Detail Inquiry</h1>
        </div>

        @if($inquiry) <!-- Check if $inquiry data exists -->
            <table class="table-n">
                <thead class="table-n">
                    <tr class="table-n">
                        @if($inquiry->create_by) <th style="width: 20%;" class="table-n">Create By</th> @endif
                        @if($inquiry->loc_imp) <th style="width: 20%;" class="table-n">Category</th> @endif
                        @if($inquiry->kode_inquiry) <th style="width: 20%;" class="table-n">Reference</th> @endif
                        @if($inquiry->customer) <th style="width: 20%;" class="table-n">Customer</th> @endif
                        @if($inquiry->supplier) <th style="width: 20%;" class="table-n">Supplier</th> @endif
                        @if($inquiry->created_at) <th style="width: 20%;" class="table-n">Date Create</th> @endif
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-n">
                        @if($inquiry->create_by) <td class="table-n">{{ $inquiry->create_by }}</td> @endif
                        @if($inquiry->loc_imp) <td class="table-n">{{ $inquiry->loc_imp }}</td> @endif
                        @if($inquiry->kode_inquiry) <td class="table-n">{{ $inquiry->kode_inquiry }}</td> @endif
                        @if($inquiry->customer) <td class="table-n">{{ $inquiry->customer->name_customer ?? '-' }}</td> @endif
                        @if($inquiry->supplier) <td class="table-n">{{ $inquiry->supplier }}</td> @endif
                        @if($inquiry->created_at) <td class="table-n">{{ $inquiry->created_at }}</td> @endif
                    </tr>
                </tbody>
            </table>
        @endif

        @if($materials->isNotEmpty()) <!-- Check if there are materials data -->
            <table>
                <thead>
                    <tr>
                        @if($materials->first()->type_materials) <th style="width: 100px;">Raw Material</th> @endif
                        @if($materials->first()->jenis) <th style="width: 50px;">Shapes</th> @endif
                        @if($materials->first()->thickness) <th style="width: 40px;text-align:center;">Thickness</th> @endif
                        @if($materials->first()->weight) <th style="width: 40px;text-align:center;">Width</th> @endif
                        @if($materials->first()->inner_diameter) <th style="width: 40px; text-align:center;">Inner Dia</th> @endif
                        @if($materials->first()->outer_diameter) <th style="width: 40px; text-align:center;">Outer Dia</th> @endif
                        @if($materials->first()->length) <th style="width: 50px;text-align:center;">Length</th> @endif
                        @if($materials->first()->qty) <th style="width: 50px; text-align:center;">Qty</th> @endif
                        @if($materials->first()->m1) <th style="width: 50px; text-align:center;">Forecast Month 1</th> @endif
                        @if($materials->first()->m2) <th style="width: 50px; text-align:center;">Forecast Month 2</th> @endif
                        @if($materials->first()->m3) <th style="width: 50px; text-align:center;">Forecast Month 3</th> @endif
                        @if($materials->first()->ship) <th style="width: 70px; text-align:center;">Ship-to</th> @endif
                        @if($materials->first()->so) <th style="width: 50px; text-align:center;">Sales Order</th> @endif
                        <th style="width: 50px;">Remark</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @foreach ($materials as $index => $material)
                        <tr>
                            @if($material->type_materials) <td>{{ $material->type_materials->type_name ?? 'N/A' }}</td> @endif
                            @if($material['jenis']) <td>{{ $material['jenis'] }}</td> @endif
                            @if($material['thickness']) <td style="text-align:center;">{{ $material['thickness'] }}</td> @endif
                            @if($material['weight']) <td style="text-align:center;">{{ $material['weight'] }}</td> @endif
                            @if($material['inner_diameter']) <td style="text-align:center;">{{ $material['inner_diameter'] }}</td> @endif
                            @if($material['outer_diameter']) <td style="text-align:center;">{{ $material['outer_diameter'] }}</td> @endif
                            @if($material['length']) <td style="text-align:center;">{{ $material['length'] }}</td> @endif
                            @if($material['qty']) <td style="text-align:center;">{{ $material['qty'] }}</td> @endif
                            @if($material['m1']) <td style="text-align:center;">{{ $material['m1'] }}</td> @endif
                            @if($material['m2']) <td style="text-align:center;">{{ $material['m2'] }}</td> @endif
                            @if($material['m3']) <td style="text-align:center;">{{ $material['m3'] }}</td> @endif
                            @if($material['ship']) <td style="text-align:center;">{{ $material['ship'] }}</td> @endif
                            @if($material['so']) <td>{{ $material['so'] }}</td> @endif
                            <td>{{ $material['note'] }}</td>
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
