@extends('layout')

@section('content')
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        .form-section {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .form-section .form-group {
            flex: 1 1 15%;
            /* Adjust this value to control the width of each item */
            margin-right: 2px;
            margin-bottom: 15px;
        }

        .form-section label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .add-column-button {
            margin-top: 15px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Halaman Inquiry</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><a href="">Menu Inquiry Sales</a></li>
                    <li class="breadcrumb-item active">Riwayat Inquiry Sales</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Kode Inquiry:</label>
                            <div class="form-value">{{ $inquiry->kode_inquiry }}</div>
                        </div>
                        <div class="form-group">
                            <label>Order From:</label>
                            <div class="form-value">{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Create By:</label>
                            <div class="form-value">{{ $inquiry->create_by }}</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 30px;">No</th>
                                    <th style="width: 150px;">Nama Material</th>
                                    <th style="width: 50px;">Jenis</th>
                                    <th style="width: 50px;">Thickness</th>
                                    <th style="width: 50px;">Weight</th>
                                    <th style="width: 50px;">Inner Diameter</th>
                                    <th style="width: 50px;">Outer Diameter</th>
                                    <th style="width: 50px;">Length</th>
                                    <th style="width: 50px;">Pcs</th>
                                    <th style="width: 50px;">Qty</th>
                                    <th style="width: 50px;">Konfirmasi</th>
                                    <th style="width: 50px;">NO PO</th>
                                    <th style="width: 100px;">Rencana Kedatangan</th>
                                    <th style="width: 50px;">Pembaruan Terakhir</th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                @forelse ($materials as $index => $material)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $material->type_materials ? $material->type_materials->type_name : 'N/A' }}
                                        </td>
                                        <td>{{ $material['jenis'] }}</td>
                                        <td>{{ $material['thickness'] }}</td>
                                        <td>{{ $material['weight'] }}</td>
                                        <td>{{ $material['inner_diameter'] }}</td>
                                        <td>{{ $material['outer_diameter'] }}</td>
                                        <td>{{ $material['length'] }}</td>
                                        <td>{{ $material['pcs'] }}</td>
                                        <td>{{ $material['qty'] }}</td>
                                        <td>{{ $material['konfirmasi'] }}</td>
                                        <td>{{ $material['no_po'] }}</td>
                                        <td>{{ $material['rencana_kedatangan'] }}</td>
                                        <td>{{ $material['updated_at'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" style="text-align: center;">Data tidak ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <a class="btn btn-secondary add-row-button btn-sm" onclick="goBack()">Kembali</a>
                    <a href="javascript:void(0)" class="btn btn-primary" onclick="exportTableToExcel()">
                        <i class="fas fa-print"></i> Print Excel
                    </a>
                </div>
            </div>
        </section>
                        <!-- jQuery -->
                        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
                        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
                        <script>
                            $(document).ready(function() {
                                // Hover function for dropdowns
                                $('.nav-item.dropdown').hover(function() {
                                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                                }, function() {
                                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                                });
                            });
                            </script>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <!-- excel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

        <script>
            function exportTableToExcel() {
                // Fetch the form data
                const formData = {
                    kodeInquiry: document.querySelectorAll('.form-value')[0].innerText, // Kode Inquiry
                    orderFrom: document.querySelectorAll('.form-value')[1].innerText, // Order From
                    createBy: document.querySelectorAll('.form-value')[2].innerText // Create By
                };

                // Prepare table data
                const table = document.querySelector('table');
                const rows = table.querySelectorAll('tr');

                let tableData = [];

                // Collect table header and rows
                rows.forEach((row, index) => {
                    let rowData = [];
                    row.querySelectorAll('th, td').forEach(cell => {
                        rowData.push(cell.innerText);
                    });
                    tableData.push(rowData);
                });

                // Create a combined data array for the required header order
                let data = [];

                // Add custom header
                data.push([
                    'Kode Inquiry', 'Nama Material', 'Thickness', 'Weight', 'Inner Diameter', 'Outer Diameter',
                    'Length', 'Jenis', 'Pcs', 'Qty', 'Order From', 'Create By', 'Konfrimasi', 'NO PO',
                    'Rencana Kedatangan', 'Tanggal Terakhir'
                ]);

                // Add form data horizontally
                tableData.slice(1).forEach(row => {
                    data.push([
                        formData.kodeInquiry,
                        row[1], // Nama Material
                        row[3], // Thickness
                        row[4], // Weight
                        row[5], // Inner Diameter
                        row[6], // Outer Diameter
                        row[7], // Length
                        row[2], // Jenis
                        row[8], // Pcs
                        row[9], // Qty
                        formData.orderFrom,
                        formData.createBy,
                        row[10], // Qty
                        row[11], // Qty
                        row[12], // Qty
                        row[13] // Tanggal Terakhir
                    ]);
                });

                // Create a workbook and a worksheet
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.aoa_to_sheet(data);

                // Set custom column widths
                ws['!cols'] = [{
                        wpx: 100
                    }, // Kode Inquiry
                    {
                        wpx: 150
                    }, // Nama Material
                    {
                        wpx: 80
                    }, // Thickness
                    {
                        wpx: 80
                    }, // Weight
                    {
                        wpx: 100
                    }, // Inner Diameter
                    {
                        wpx: 100
                    }, // Outer Diameter
                    {
                        wpx: 80
                    }, // Length
                    {
                        wpx: 100
                    }, // Jenis
                    {
                        wpx: 50
                    }, // Pcs
                    {
                        wpx: 50
                    }, // Qty
                    {
                        wpx: 100
                    }, // Supplier
                    {
                        wpx: 100
                    }, // Order From
                    {
                        wpx: 100
                    }, // Create By
                    {
                        wpx: 100
                    }, // konfirmasi
                    {
                        wpx: 100
                    }, // no_po
                    {
                        wpx: 100
                    }, // rencana kedatangan
                    {
                        wpx: 125
                    } // Tanggal Terakhir
                ];

                // Add worksheet to workbook
                XLSX.utils.book_append_sheet(wb, ws, 'InquiryData');

                // Prepare file name with inquiry code and latest date
                const lastDate = tableData.length > 1 ? tableData[tableData.length - 1][10] : new Date().toISOString().slice(0,
                    10);
                const fileName = `Report_Inquiry_${formData.kodeInquiry}.xlsx`;

                // Export workbook to Excel file
                XLSX.writeFile(wb, fileName);
            }

            function goBack() {
                window.history.back();
            }
        </script>

    </main><!-- End #main -->
@endsection
