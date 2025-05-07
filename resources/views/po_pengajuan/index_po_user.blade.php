@extends('layout')

@section('content')
    <style>
        .fodabl {
            font-family: 'Cambria', serif;
        }

        .table thead th {
            background-color: #00587a;
            color: white;
            font-size: 14px;
            border: #000000;
        }

        .badge {
            padding: 8px 10px;
            /* border-radius: 12px; */
            font-size: 10pt;
            color: white;
            display: inline-flex;
            align-items: center;
        }

        .btn-i {
            font-size: 10pt;
            padding: 8px 10px;
            border-radius: 0.2rem;
            background: rgb(0, 97, 189);
            color: #ffffff;
            font-family: 'Cambria', serif;
        }

        .btn:hover {
            background-color: #00ddfa;
        }
    </style>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Menu Form Pengajuan User</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Pengajuan FPB</li>

                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tampilan Data Form Permintaan Barang/Jasa</h5>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <button id="kirimSemuaData" class="btn btn-i">
                                        <i class="fas fa-paper-plane"></i> Kirim Semua FPB
                                    </button>

                                    <div class="d-flex -content-end">
                                        <label for="startDate" class="me-2">From : </label>
                                        <input type="date" id="startDate" name="start_date" class="form-control me-2"
                                            style="width: 150px;">

                                        <label for="endDate" class="me-2">To : </label>
                                        <input type="date" id="endDate" name="end_date" class="form-control me-2"
                                            style="width: 150px;">

                                        <button id="exportData" class="btn btn-success ms-2"><i
                                                class="bi bi-file-earmark-spreadsheet"></i></button>
                                    </div>
                                </div>

                                <table class="datatable table fodabl">
                                    <thead>
                                        <tr>
                                            <th class="text-center fodabl" width="50px">NO</th>
                                            <th class="text-center fodabl" width="50px">
                                                <input type="checkbox" id="selectAll" />
                                            </th>
                                            <th class="text-center fodabl" width="100px">NO FPB</th>
                                            <th class="text-center fodabl" width="100px">PIC</th>
                                            <th class="text-center fodabl" width="100px">Kategori</th>
                                            <th class="text-center fodabl" width="100px">Catatan</th>
                                            <th class="text-center fodabl" width="100px">Tgl Pembaruan</th>
                                            <th class="text-center fodabl" width="100px">Status</th>
                                            <th class="text-center fodabl" width="100px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $row)
                                            @if ($row->status_1 == 3)
                                                <!-- Tambahkan kondisi untuk menampilkan hanya jika status_1 == 2 -->
                                                <tr>
                                                    <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                    <td class="text-center">
                                                        @if (!in_array($row->status_1, [5, 6, 7, 8, 9, 10]))
                                                            <input type="checkbox" class="selectRow"
                                                                data-no_fpb="{{ $row->no_fpb }}" />
                                                        @endif
                                                    </td>
                                                    <td class="text-center py-3">{{ $row->no_fpb }}</td>
                                                    <td class="text-center py-3">{{ $row->modified_at }}</td>
                                                    <td class="text-center py-3">{{ $row->kategori_po }}</td>
                                                    <td class="text-center py-3"><b>{{ $row->catatan }}</b></td>
                                                    <td class="text-center py-3">
                                                        <b>
                                                            {{ $row->trs_updated_at !== '-' && $row->trs_updated_at ? \Carbon\Carbon::parse($row->trs_updated_at)->format('d-m-Y') : '-' }}
                                                        </b>
                                                    </td>
                                                    <td class="text-center py-4"
                                                        style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        @if ($row->status_1 == 1)
                                                            <span class="badge bg-secondary align-items-center"
                                                                style="font-size: 18px;">Draf</span>
                                                        @elseif ($row->status_1 == 2)
                                                            <span class="badge bg-success align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif ($row->status_1 == 3)
                                                            <span class="badge bg-success align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif($row->status_1 == 4)
                                                            <span class="badge bg-success align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif($row->status_1 == 5)
                                                            <span class="badge bg-success align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif($row->status_1 == 6)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif($row->status_1 == 7)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif($row->status_1 == 8)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif($row->status_1 == 9)
                                                            <span class="badge bg-info align-items-center"
                                                                style="font-size: 18px;">Finish</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center py-4">
                                                        <a href="{{ route('view.FormPo.3', ['id' => $row->id]) }}"
                                                            class="btn btn-primary btn-sm" title="View Form">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-success btn-sm btn-kirim"
                                                            data-no_fpb="{{ $row->no_fpb }}" title="Kirim">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
        <!-- Include jQuery and SheetJS library (for Excel export functionality) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Import SheetJS (XLSX) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>

        <!-- Import ExcelJS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.2.1/exceljs.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('kirimSemuaData').addEventListener('click', function() {
                    // Ambil semua checkbox yang dipilih
                    const selectedCheckboxes = document.querySelectorAll('.selectRow:checked');

                    // Periksa apakah ada checkbox yang dipilih
                    if (selectedCheckboxes.length === 0) {
                        Swal.fire('Pilih data terlebih dahulu!', '', 'warning');
                        return;
                    }

                    // Ambil semua no_fpb dari checkbox yang dipilih
                    let noFpbArray = [];
                    selectedCheckboxes.forEach(checkbox => {
                        noFpbArray.push(checkbox.getAttribute('data-no_fpb').replace(/\//g, '-'));
                    });

                    // Tampilkan pesan konfirmasi
                    Swal.fire({
                        title: 'Apakah anda ingin mengirim semua data?',
                        text: "Data yang telah dikirim tidak dapat dirubah!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, kirim!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Lakukan AJAX POST request untuk setiap no_fpb yang dipilih
                            noFpbArray.forEach(no_fpb => {
                                $.ajax({
                                    url: "{{ route('kirim.fpb.user', ':no_fpb') }}"
                                        .replace(':no_fpb', no_fpb),
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response);
                                    },
                                    error: function(xhr) {
                                        console.log("Error occurred:", xhr
                                            .responseText);
                                    }
                                });
                            });

                            // Tampilkan pesan sukses dan refresh halaman setelah semua request berhasil
                            Swal.fire('Terkirim!', 'Semua data berhasil dikirim.', 'success').then(
                                () => {
                                    location.reload();
                                });
                        }
                    });
                });

                document.querySelectorAll('.btn-kirim').forEach(button => {
                    button.addEventListener('click', function() {
                        // Mengambil no_fpb dari data attribute dan mengganti "/" dengan "-"
                        var no_fpb = this.getAttribute('data-no_fpb').replace(/\//g, '-');
                        console.log("FPB Number to send:", no_fpb); // Log FPB number yang diambil

                        Swal.fire({
                            title: 'Apakah anda ingin mengirim data?',
                            text: "Data yang telah dikirim tidak dapat dirubah!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, kirim!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Jika konfirmasi, lakukan AJAX POST request
                                $.ajax({
                                    url: "{{ route('kirim.fpb.user', ':no_fpb') }}"
                                        .replace(':no_fpb', no_fpb),
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response); // Log response dari server

                                        Swal.fire(
                                            'Terkirim!',
                                            'Data berhasil dikirim.',
                                            'success'
                                        ).then(() => {
                                            location
                                                .reload(); // Refresh halaman setelah sukses
                                        });
                                    },
                                    error: function(xhr) {
                                        console.log("Error occurred:", xhr
                                            .responseText
                                        ); // Log error jika terjadi kesalahan

                                        Swal.fire(
                                            'Gagal!',
                                            'Terjadi kesalahan saat mengirim data.',
                                            'error'
                                        );
                                    }
                                });
                            }
                        });
                    });
                });
            });

            // Fungsi untuk memilih semua checkbox
            document.getElementById('selectAll').addEventListener('change', function() {
                document.querySelectorAll('.selectRow').forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            $(document).ready(function() {
                const url = `{{ route('getData') }}`;

                function getDataAndExportExcel(startDate, endDate) {
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            start_date: startDate,
                            end_date: endDate
                        },
                        success: function(response) {
                            exportToExcel(response);
                        },
                        error: function(xhr, status, error) {
                            console.error("Terjadi kesalahan:", error);
                        }
                    });
                }

                async function exportToExcel(data) {
                    const workbook = new ExcelJS.Workbook();
                    const worksheet = workbook.addWorksheet("Data");

                    // Header dengan kolom "No" di awal dan filter
                    const headers = [{
                            header: "No",
                            key: "no",
                            width: 5
                        },
                        {
                            header: "No FPB",
                            key: "no_fpb",
                            width: 15
                        },
                        {
                            header: "No PO",
                            key: "no_po",
                            width: 15
                        },
                        {
                            header: "Kategori PO",
                            key: "kategori_po",
                            width: 20
                        },
                        {
                            header: "Nama Barang",
                            key: "nama_barang",
                            width: 20
                        },
                        {
                            header: "PCS",
                            key: "pcs",
                            width: 7
                        },
                        {
                            header: "Price List",
                            key: "price_list",
                            width: 15
                        },
                        {
                            header: "Total Harga",
                            key: "total_harga",
                            width: 20
                        },
                        {
                            header: "Spesifikasi",
                            key: "spesifikasi",
                            width: 30
                        },
                        {
                            header: "Rekomendasi",
                            key: "rekomendasi",
                            width: 15
                        },
                        {
                            header: "Due Date",
                            key: "due_date",
                            width: 15
                        },
                        {
                            header: "Target Cost",
                            key: "target_cost",
                            width: 15
                        },
                        {
                            header: "Lead Time",
                            key: "lead_time",
                            width: 15
                        },
                        {
                            header: "Nama Customer",
                            key: "nama_customer",
                            width: 20
                        },
                        {
                            header: "Nama Project",
                            key: "nama_project",
                            width: 20
                        },
                        {
                            header: "No SO",
                            key: "no_so",
                            width: 15
                        },
                        {
                            header: "Status 1",
                            key: "status_1",
                            width: 10
                        },
                        {
                            header: "Status 2",
                            key: "status_2",
                            width: 15
                        },
                        {
                            header: "Created At",
                            key: "created_at",
                            width: 23
                        },
                        {
                            header: "Pembuat",
                            key: "modified_at",
                            width: 25
                        }
                    ];

                    worksheet.columns = headers;
                    worksheet.getRow(1).font = {
                        bold: true
                    };
                    worksheet.autoFilter = {
                        from: 'A1',
                        to: String.fromCharCode(64 + headers.length) + '1'
                    };

                    // Format data dan tambahkan ke worksheet
                    data.forEach((item, index) => {
                        const createdAt = new Date(item.created_at);
                        const formattedCreatedAt =
                            `${String(createdAt.getDate()).padStart(2, '0')}-${String(createdAt.getMonth() + 1).padStart(2, '0')}-${createdAt.getFullYear()} ${String(createdAt.getHours()).padStart(2, '0')}:${String(createdAt.getMinutes()).padStart(2, '0')}:${String(createdAt.getSeconds()).padStart(2, '0')}`;

                        worksheet.addRow({
                            no: index + 1,
                            no_fpb: item.no_fpb,
                            no_po: item.no_po,
                            kategori_po: item.kategori_po,
                            nama_barang: item.nama_barang,
                            pcs: item.pcs,
                            price_list: item.price_list,
                            total_harga: item.total_harga,
                            spesifikasi: item.spesifikasi,
                            rekomendasi: item.rekomendasi,
                            due_date: item.due_date,
                            target_cost: item.target_cost,
                            lead_time: item.lead_time,
                            nama_customer: item.nama_customer,
                            nama_project: item.nama_project,
                            no_so: item.no_so,
                            status_1: item.status_1,
                            status_2: item.status_2,
                            created_at: formattedCreatedAt,
                            modified_at: item.modified_at
                        });
                    });

                    const buffer = await workbook.xlsx.writeBuffer();
                    const blob = new Blob([buffer], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = "Export_FPB.xlsx";
                    link.click();
                }

                $('#exportData').click(function() {
                    const startDate = $('#startDate').val();
                    const endDate = $('#endDate').val();

                    if (!startDate || !endDate) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Perhatian',
                            text: 'Silakan pilih tanggal mulai dan tanggal akhir.',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    getDataAndExportExcel(startDate, endDate);
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
