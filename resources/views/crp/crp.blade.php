@extends('layout')

@section('content')
    {{-- Style Css --}}
    <style>
        body {
            background-color: #f8f9fa;
        }

        .table-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background-color: #007bff;
            color: white;
            text-align: center;
            font-weight: bold;
        }

        td,
        th {
            vertical-align: middle;
            text-align: center;
        }

        .plan-row {
            background-color: #e9f5ff;
        }

        .actual-row {
            background-color: #fff3e6;
        }

        .btn-custom {
            margin: 10px 5px;
            border-radius: 5px;
        }

        input[type="text"] {
            width: 80px;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }

        select {
            width: 150px;
            padding: 5px;
            border-radius: 5px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .red-text {
            color: red;
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .button-container button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 10px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }

        .button-container button:hover {
            background-color: #45a049;
        }

        .action-buttons button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            margin: 0 5px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
        }
       
        .modal-dialog.modal-lg {
            max-width: 90%;
        }


        .action-buttons button:hover {
            background-color: #45a049;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"] {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
        }
    </style>

    <main id="main" class="main">
        <section class="section">

            <head>
                <meta name="csrf-token" content="{{ csrf_token() }}">
            </head>
            <div class="card shadow-lg rounded">
                <div class="card-body">
                    <p></p>
                    <h4 class="title text-center font-weight-bold text-primary">Tabel Summary</h4>
                    <h6 class="text-left font-weight-bold text-primary">
                        Partner user: {{ $userName }}
                    </h6>
                    <a href="{{ route('export.mst.actual') }}" class="btn btn-success mb-3">
                        Export to Excel
                    </a>

                    <div class="table-responsive">
                        <table id="tabelsummary" class="table table-striped table-bordered table-hover text-center">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 25px;"></th>
                                    <th style="width: 200px;">Category</th>
                                    <th>Type</th>
                                    <th>Jan</th>
                                    <th>Feb</th>
                                    <th>Mar</th>
                                    <th>Apr</th>
                                    <th>May</th>
                                    <th>Jun</th>
                                    <th>Jul</th>
                                    <th>Aug</th>
                                    <th>Sep</th>
                                    <th>Oct</th>
                                    <th>Nov</th>
                                    <th>Dec</th>
                                    <th>FY</th>
                                    <th>YTD</th>
                                    <th>PICA</th>
                                </tr>
                            </thead>
                            <tbody id="summaryBody">
                                @php
                                    $allCategories = [
                                        'IT',
                                        'Subcont',
                                        'Consumable',
                                        'Repair Maintenance',
                                        'Utility',
                                        'General Affair',
                                        'Material Cost',
                                        'Indirect Material',
                                        'Others',
                                    ];
                                @endphp
                                @foreach ($allCategories as $category)
                                    @php
                                        $records = $mstDboCrps->where('nm_category', $category);
                                        $plan = $records->firstWhere('plan_actual', 'Plan');
                                        $actual = $records->firstWhere('plan_actual', 'Actual');
                                        $value = ($plan->id ?? '') . ($actual ? ',' . $actual->id : '');
                                    @endphp
                                    <!-- Plan Row -->
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="record[]" value="{{ $value }}">
                                        </td>
                                        <td rowspan="2" class="align-middle font-weight-bold text-dark">
                                            {{ $category }}
                                        </td>
                                        <td class="font-weight-bold text-primary">Plan</td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td>
                                                <input type="text" class="form-control text-center" name="plan_values[{{ $category }}][{{ $i }}]"
                                                    value="{{ $plan ? $plan->{'month_' . $i} : '' }}" oninput="calculateYTD()">
                                            </td>
                                        @endfor
                                        <td>
                                            <input type="text" class="form-control text-center font-weight-bold" name="plan_ytd[{{ $category }}]"
                                                value="{{ $plan ? $plan->grand_tot : '' }}" readonly />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control text-center font-weight-bold" readonly />
                                        </td>
                                    </tr>
                                    <!-- Actual Row -->
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold text-success">Actual</td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td>
                                                <input type="text" class="form-control text-center" name="actual_values[{{ $category }}][{{ $i }}]"
                                                    value="{{ $actual ? $actual->{'month_' . $i} : '' }}" disabled />
                                            </td>
                                        @endfor
                                        <td>
                                            <input type="text" class="form-control text-center font-weight-bold" disabled name="actual_ytd[{{ $category }}]"
                                                value="{{ $actual ? $actual->grand_tot : '' }}" readonly />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control text-center font-weight-bold" disabled value="{{ $actual ? $actual->grand_tot : '' }}" readonly />
                                        </td>
                                        <td>
                                            <button class="btn btn-info" onclick="openModal({{ $actual ? $actual->id : 0 }})">Act</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>



                    {{-- <div class="table-responsive">
                        <table id="tabelsummary" class="table table-striped table-bordered table-hover text-center">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 25px;"></th>
                                    <th style="width: 200px;">Category</th>
                                    <th>Type</th>
                                    <th>Jan</th>
                                    <th>Feb</th>
                                    <th>Mar</th>
                                    <th>Apr</th>
                                    <th>May</th>
                                    <th>Jun</th>
                                    <th>Jul</th>
                                    <th>Aug</th>
                                    <th>Sep</th>
                                    <th>Oct</th>
                                    <th>Nov</th>
                                    <th>Dec</th>
                                    <th>YTD</th>
                                </tr>
                            </thead>
                            <tbody id="summaryBody">
                                @php
                                    $categories = $mstDboCrps->groupBy('nm_category');
                                @endphp
                                @foreach ($categories as $category => $records)
                                    @php
                                        $plan = $records->firstWhere('plan_actual', 'Plan');
                                        $actual = $records->firstWhere('plan_actual', 'Actual');
                                    @endphp
                                    <!-- Plan Row -->
                                    <tr>
                                        <td>
                                            @php
                                                $value = $plan->id ?? '';
                                                $value .= $actual ? ',' . $actual->id : '';
                                            @endphp
                                            <input type="checkbox" name="record[]" value="{{ $value }}">
                                        </td> 
                                        <td rowspan="2">
                                            <select class="form-control" name="category[]">
                                                <option value="" disabled>Pilih Kategori</option>
                                                @foreach (['Consumable', 'Subcont', 'Repair Maintenance', 'Utility', 'General Afffair', 'IT', 'Material Cost', 'Indirect Material', 'Others'] as $opt)
                                                    <option value="{{ $opt }}" {{ $category == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="font-weight-bold text-primary">Plan</td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td>
                                                <input type="text" class="form-control text-center"
                                                       name="plan_values[{{ $category }}][]"
                                                       value="{{ $plan ? $plan->{'month_' . $i} : '' }}"
                                                       oninput="calculateYTD(this)" />
                                            </td>
                                        @endfor
                                        <td>
                                            <input type="text" class="form-control text-center font-weight-bold"
                                                   name="plan_ytd[{{ $category }}]"
                                                   value="{{ $plan ? $plan->grand_tot : '' }}" readonly />
                                        </td>
                                    </tr>
                                    <!-- Actual Row -->
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold text-success">Actual</td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td>
                                                <input type="text" class="form-control text-center"
                                                       name="actual_values[{{ $category }}][]"
                                                       value="{{ $actual ? $actual->{'month_' . $i} : '' }}" disabled/>
                                            </td>
                                        @endfor
                                        <td>
                                            <input type="text" class="form-control text-center font-weight-bold" disabled
                                                   name="actual_ytd[{{ $category }}]"
                                                   value="{{ $actual ? $actual->grand_tot : '' }}" readonly />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            
                        </table>
                    </div> --}}

                    <div class="d-flex justify-content-center mt-3 gap-2">
                        {{-- <a href="#" class="btn btn-success font-weight-bold px-4" onclick="addRow1()">
                            Tambah Baris
                        </a> --}}
                        {{-- <a href="#" class="btn btn-danger font-weight-bold px-4" onclick="deleteRows1()">
                            Hapus Baris
                        </a> --}}
                        <button class="btn btn-warning font-weight-bold px-4 text-white" onclick="resetInputs()">
                            Reset
                        </button>
                        <button class="btn btn-primary" onclick="saveData()">
                            Simpan
                        </button>
                        <button class="btn btn-primary" onclick="deletePermanen()">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
            <br> </br>


            <div class="card shadow-lg">
                <div class="card-body">
                    <p></p>
                    <div class="d-flex gap-2 mb-3">
                        <a href="{{ route('export.detailcrp') }}" class="btn btn-success">
                            Export to Excel
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-file-earmark-excel"></i> Import Data
                        </button>
                    </div>
                    <div class="title text-center font-weight-bold text-primary">Detail Pencatatan Actual</div>
                    <div class="table-responsive">
                        <table id="detailTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Category</th>
                                    <th>Detail Activity</th>
                                    <th>No PO</th>
                                    <th>Date</th>
                                    <th>Qty</th>
                                    <th>Price Before</th>
                                    <th>Price After</th>
                                    <th>Selisih</th>
                                    <th>Total Cost Before</th>
                                    <th>Total Cost After</th>
                                    <th>Total Cost CRP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trsDboCrps as $row)
                                    <tr data-id="{{ $row->id }}">
                                        <td><input type="checkbox" name="record[]" value="{{ $row->id }}"></td>
                                        <td>
                                            <select class="form-select" name="actual_category[]">
                                                @foreach ($mstDboCrps->pluck('nm_category')->unique() as $category)
                                                    <option value="{{ $category }}"
                                                        {{ $row->nm_category == $category ? 'selected' : '' }}>
                                                        {{ $category }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control" name="detail_activity[]"
                                                value="{{ $row->detail_activity }}"></td>
                                        <td><input type="text" class="form-control" name="no_po[]"
                                                value="{{ $row->no_po }}"></td>
                                        <td><input type="date" class="form-control" name="date[]"
                                                value="{{ $row->date }}"></td>
                                        <td><input type="number" class="form-control" name="qty[]"
                                                oninput="calculateCRP(this)" value="{{ $row->qty }}"></td>
                                        <td><input type="number" class="form-control" name="price_before[]"
                                                oninput="calculateCRP(this)" value="{{ $row->price_before }}"></td>
                                        <td><input type="number" class="form-control" name="price_after[]"
                                                oninput="calculateCRP(this)" value="{{ $row->price_after }}"></td>
                                        <td><input type="number" class="form-control" name="selisih[]"
                                                value="{{ $row->price_sell }}" readonly></td>
                                        <td><input type="number" class="form-control" name="total_cost_before[]"
                                                value="{{ $row->total_cost_before }}" readonly></td>
                                        <td><input type="number" class="form-control" name="total_cost_after[]"
                                                value="{{ $row->total_cost_after }}" readonly></td>
                                        <td><input type="number" class="form-control crp" name="total_cost_crp[]"
                                                value="{{ $row->total_cost_crp }}" readonly></td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3 gap-2">
                        <button class="btn btn-success" onclick="addRow()">
                            Tambah Baris
                        </button>
                        <button class="btn btn-danger" onclick="deleteRows()">
                            Hapus Baris
                        </button>
                        <button class="btn btn-warning text-white" onclick="resetInputs1()">
                            Reset
                        </button>
                        <button class="btn btn-primary" onclick="saveDetail()">
                            Submit
                        </button>
                        <button class="btn btn-primary" onclick="hapusPermanenDetail()">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="#" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="importModalLabel">Import Detail Data</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if (session('import_success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('import_success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif

                                @if (session('import_error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ session('import_error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label for="file" class="form-label">Excel File</label>
                                    <input type="file" name="file" id="file"
                                        class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls"
                                        required>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Upload the Excel file that was previously exported from the
                                        system.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="uploadexcel()">Import</button>
                                <!-- Ubah type menjadi button -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">  <!-- Modal diperbesar dengan modal-lg -->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="detailModalLabel">Detail DBO CRP</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{ route('crp.saveDetails') }}">
                                @csrf
                                <input type="hidden" name="crp_id" id="crp_id_input">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Bulan</th>
                                            <th>PI</th>
                                            <th>CA</th>
                                            <th>Due Date</th>
                                            <th>Remark</th>
                                            <th>Check CRP</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalTableBody">
                                        @foreach(range(1, 12) as $month)
                                            <tr>
                                                <td>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</td>
                                                <!-- Ganti input teks menjadi textarea untuk kolom yang lebih panjang -->
                                                <td><textarea name="pi_{{ $month }}" class="form-control" rows="3"></textarea></td>
                                                <td><textarea name="ca_{{ $month }}" class="form-control" rows="3"></textarea></td>
                                                <td><input type="date" name="due_date_{{ $month }}" class="form-control"></td>
                                                <td><textarea name="remark_{{ $month }}" class="form-control" rows="3"></textarea></td>
                                                <td><input type="checkbox" name="check_crp_{{ $month }}"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                // function calculateCRP(input) {
                //     const row = input.closest('tr');
                //     const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
                //     const priceBefore = parseFloat(row.querySelector('input[name="price_before[]"]').value) || 0;
                //     const priceAfter = parseFloat(row.querySelector('input[name="price_after[]"]').value) || 0;

                //     // Hitungkan CRP (Anda perlu mendefinisikan bagaimana CRP dihitung)
                //     const crp = (priceAfter - priceBefore) * qty; // Contoh perhitungan CRP
                //     row.querySelector('.crp').value = crp.toFixed(2); // Modifikasi menyesuaikan hasil
                // }
            </script>

            <script>
                window.mstCategories = @json($mstDboCrps->pluck('nm_category')->unique()->values());
            </script>
            <script>


                function openModal(crpId) {
                    $('#crp_id_input').val(crpId);

                    $.ajax({
                        url: "{{ route('crp.showDetailModal', ['crpId' => '__crpId__']) }}".replace('__crpId__', crpId),
                        method: 'GET',
                        success: function(response) {
                            // Pastikan data dikirim dan tidak kosong
                            if (response && response.length > 0) {
                                for (let month = 1; month <= 12; month++) {
                                    // Cari detail untuk bulan tersebut
                                    let data = response.find(detail => detail.Month === month); 

                                    // Jika data bulan ada, masukkan ke dalam input
                                    if (data) {
                                        $(`textarea[name="pi_${month}"]`).val(data.pi || '');  // Menangani null atau undefined
                                        $(`textarea[name="ca_${month}"]`).val(data.ca || '');
                                        $(`input[name="due_date_${month}"]`).val(data.due_date || '');
                                        $(`textarea[name="remark_${month}"]`).val(data.remark || '');
                                        // Cek apakah check_crp 1 (centang) atau 0 (tidak centang)
                                        $(`input[name="check_crp_${month}"]`).prop('checked', data.check_crp == 1);
                                    } else {
                                        // Jika tidak ada data untuk bulan ini, kosongkan input
                                        $(`textarea[name="pi_${month}"]`).val('');
                                        $(`textarea[name="ca_${month}"]`).val('');
                                        $(`input[name="due_date_${month}"]`).val('');
                                        $(`textarea[name="remark_${month}"]`).val('');
                                        $(`input[name="check_crp_${month}"]`).prop('checked', false);
                                    }
                                }
                            } else {
                                console.log('No data found for this CRP ID.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Error fetching data:', error);
                        }
                    });

                    // Show modal
                    $('#detailModal').modal('show');
                }



                function uploadexcel() {
                    let fileInput = document.getElementById('file'); // Perbaiki ID di sini

                    if (fileInput.files.length === 0) {
                        alert("Pilih file terlebih dahulu!");
                        return;
                    }

                    let formData = new FormData();
                    formData.append("file", fileInput.files[0]);

                    $.ajax({
                        url: "{{ route('import.detailcrp') }}", // Pastikan rute ini benar
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            alert(response.message);
                            window.location.href = response.redirect; // Redirect ke halaman tujuan
                        },
                        error: function(xhr) {
                            alert("Terjadi kesalahan: " + xhr.responseText);
                        }
                    });
                }

                function addRow() {
                    const table = document.getElementById('detailTable').getElementsByTagName('tbody')[0];
                    const newRow = table.insertRow();
                    newRow.classList.add('new-row'); // ← Tambahkan di sini, setelah insertRow()

                    newRow.innerHTML = `
                        <td><input type="checkbox" name="record"></td>
                        <td>
                            <select class="form-select" name="actual_category[]">
                                @foreach ($mstDboCrps->pluck('nm_category')->unique() as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control" name="detail_activity[]"></td>
                        <td><input type="text" class="form-control" name="no_po[]"></td>
                        <td><input type="date" class="form-control" name="date[]"></td>
                        <td><input type="number" class="form-control" name="qty[]" oninput="calculateCRP(this)"></td>
                        <td><input type="number" class="form-control" name="price_before[]" oninput="calculateCRP(this)"></td>
                        <td><input type="number" class="form-control" name="price_after[]" oninput="calculateCRP(this)"></td>
                        <td><input type="text" class="form-control" name="selisih[]" readonly></td>
                        <td><input type="number" class="form-control" name="total_cost_before[]" readonly></td>
                        <td><input type="number" class="form-control" name="total_cost_after[]" readonly></td>
                        <td><input type="text" class="form-control crp" name="total_cost_crp[]" readonly></td>
                    `;
                }


                function deleteRows() {
                    const table = document.getElementById('detailTable');
                    const checkboxes = table.querySelectorAll('input[name="record"]:checked');
                    checkboxes.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        if (row) {
                            row.remove();
                        }
                    });
                }

                // function calculateCRP(input) {
                //     const row = input.closest('tr');
                //     const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
                //     const priceBefore = parseFloat(row.querySelector('input[name="price_before[]"]').value) || 0;
                //     const priceAfter = parseFloat(row.querySelector('input[name="price_after[]"]').value) || 0;

                //     // Calculate selisih as the difference between price_after and price_before (can be negative)
                //     const selisih = priceAfter - priceBefore;
                //     row.querySelector('input[name="selisih[]"]').value = selisih.toFixed(2);

                //     // Calculate total costs
                //     const totalCostBefore = qty * priceBefore;
                //     const totalCostAfter = qty * priceAfter;

                //     // Calculate total_cost_crp as the difference between total_cost_after and total_cost_before (can be negative)
                //     const totalCostCrp = totalCostAfter - totalCostBefore;

                //     row.querySelector('input[name="total_cost_before[]"]').value = totalCostBefore.toFixed(2);
                //     row.querySelector('input[name="total_cost_after[]"]').value = totalCostAfter.toFixed(2);
                //     row.querySelector('input[name="total_cost_crp[]"]').value = totalCostCrp.toFixed(2);
                // }


                function calculateCRP(input) {
                    const row = input.closest('tr');
                    const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
                    const priceBefore = parseFloat(row.querySelector('input[name="price_before[]"]').value) || 0;
                    const priceAfter = parseFloat(row.querySelector('input[name="price_after[]"]').value) || 0;

                    // Calculate selisih as the absolute difference between price_after and price_before
                    const selisih = Math.abs(priceAfter - priceBefore);
                    row.querySelector('input[name="selisih[]"]').value = selisih.toFixed(2);

                    // Calculate total costs
                    const totalCostBefore = qty * priceBefore;
                    const totalCostAfter = qty * priceAfter;

                    // Calculate total_cost_crp as the absolute difference between total_cost_after and total_cost_before
                    const totalCostCrp = Math.abs(totalCostAfter - totalCostBefore);

                    row.querySelector('input[name="total_cost_before[]"]').value = totalCostBefore.toFixed(2);
                    row.querySelector('input[name="total_cost_after[]"]').value = totalCostAfter.toFixed(2);
                    row.querySelector('input[name="total_cost_crp[]"]').value = totalCostCrp.toFixed(2);
                }

                function resetInputs1() {
                    document.querySelectorAll("#detailTable tbody input").forEach(input => {
                        if (input.type === "text" || input.type === "number" || input.type === "date") {
                            input.value = "";
                        }
                    });
                }

                function saveDetail() {
                    const table = document.getElementById('detailTable');
                    if (!table) {
                        console.error('Table with ID "detailTable" not found');
                        alert('Error: Table not found. Please check the table ID.');
                        return;
                    }

                    const tbody = table.getElementsByTagName('tbody')[0];
                    const rows = tbody.querySelectorAll('tr.modified-row, tr.new-row');
                    const data = [];

                    for (let row of rows) {
                        try {
                            const actualCategory = row.querySelector('select[name="actual_category[]"]')?.value || '';
                            if (!actualCategory) {
                                alert('Error: Category is required for all rows.');
                                return;
                            }

                            const rowData = {
                                id: row.dataset.id || null,
                                actual_category: actualCategory,
                                detail_activity: row.querySelector('input[name="detail_activity[]"]')?.value || '',
                                no_po: row.querySelector('input[name="no_po[]"]')?.value || '',
                                date: row.querySelector('input[name="date[]"]')?.value || '',
                                qty: parseFloat(row.querySelector('input[name="qty[]"]')?.value) || 0,
                                selisih: parseFloat(row.querySelector('input[name="selisih[]"]')?.value) || 0,
                                price_before: parseFloat(row.querySelector('input[name="price_before[]"]')?.value) || 0,
                                price_after: parseFloat(row.querySelector('input[name="price_after[]"]')?.value) || 0,
                                total_cost_before: parseFloat(row.querySelector('input[name="total_cost_before[]"]')?.value) ||
                                    0,
                                total_cost_after: parseFloat(row.querySelector('input[name="total_cost_after[]"]')?.value) || 0,
                                total_cost_crp: parseFloat(row.querySelector('input[name="total_cost_crp[]"]')?.value) || 0
                            };
                            data.push(rowData);
                        } catch (error) {
                            console.error('Error processing row:', error);
                            alert('Error: Invalid data in table row. Please check all fields.');
                            return;
                        }
                    }

                    if (data.length === 0) {
                        alert('No changes detected. Please modify or add at least one row.');
                        return;
                    }

                    console.log('Sending data:', data);

                    fetch('{{ route('crp.savedetail') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                rows: data
                            })
                        })
                        .then(async response => {
                            const text = await response.text();
                            console.log('Raw response:', text);

                            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                throw new Error('Invalid JSON response from server: ' + text);
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                alert('Data saved successfully!');
                                location.reload();
                            } else {
                                alert('Error saving data: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            alert('An error occurred while saving data: ' + error.message);
                        });
                }

                document.addEventListener('input', function(e) {
                    const row = e.target.closest('tr');
                    if (row && row.dataset.id) {
                        row.classList.add('modified-row');
                    }
                });





                function processRow(row, category, type, summaryData) {
                    const inputs = row.querySelectorAll('input[type="text"]');
                    const monthValues = Array.from(inputs).slice(0, 12).map(input => parseInt(input.value) || 0);
                    const ytd = parseInt(inputs[12]?.value) || 0;

                    const dataEntry = {
                        nm_category: category,
                        plan_actual: type,
                        grand_tot: ytd
                    };

                    for (let i = 0; i < 12; i++) {
                        dataEntry[`month_${i + 1}`] = monthValues[i] || 0;
                    }

                    summaryData.push(dataEntry);
                }

                function hapusPermanenDetail() {
                    let checked = document.querySelectorAll('input[name="record[]"]:checked');
                    let ids = [];

                    checked.forEach((checkbox) => {
                        // Pisahkan ID Plan dan Actual
                        let values = checkbox.value.split(',');
                        ids.push(...values);
                    });

                    if (ids.length === 0) {
                        alert("Pilih minimal satu data untuk dihapus.");
                        return;
                    }

                    if (confirm("Yakin ingin menghapus data terpilih?")) {
                        // Kirim ke endpoint Laravel pakai AJAX / fetch / form tersembunyi
                        fetch('{{ route('crp.deletePermanenDetail') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    ids: ids
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert("Data berhasil dihapus.");
                                    location.reload();
                                } else {
                                    alert("Gagal menghapus data.");
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                    }
                }
            </script>

        </section>

        <script></script>


        {{-- Tabel SummaryJSQ --}}

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

        <script>
            function addRow1() {
                const table = document.getElementById('tabelsummary').getElementsByTagName('tbody')[0];

                // Membuat baris Plan
                const newRowPlan = table.insertRow();
                newRowPlan.classList.add('plan-row');

                // Checkbox
                const cellCheckbox = newRowPlan.insertCell();
                cellCheckbox.innerHTML = '<input type="checkbox" name="record1">';

                // Kategori (rowspan 2)
                const cellCategory = newRowPlan.insertCell();
                cellCategory.rowSpan = 2;
                cellCategory.innerHTML = `
                    <select class="form-control" name="category[]">
                        <option value="" disabled selected>Pilih Kategori</option>
                        <option value="Consumable">Consumable</option>
                        <option value="Subcont">Subcont</option>
                        <option value="Repair Maintenance">Repair Maintenance</option>
                        <option value="Utility">Utility</option>
                        <option value="General Afffair">General Afffair</option>
                        <option value="IT">IT</option>
                        <option value="Material Cost">Material Cost</option>
                        <option value="Indirect Material">Indirect Material</option>
                        <option value="Others">Others</option>
                    </select>
                `;

                // Tipe Plan
                const cellTypePlan = newRowPlan.insertCell();
                cellTypePlan.className = 'font-weight-bold text-primary';
                cellTypePlan.textContent = 'Plan';

                // Input bulan Jan-Dec untuk Plan
                for (let i = 0; i < 12; i++) {
                    const cell = newRowPlan.insertCell();
                    cell.innerHTML =
                        `<input type="text" class="form-control text-center" name="plan_values[]" oninput="calculateYTD()">`;
                }

                // YTD Plan
                const cellYTDPlan = newRowPlan.insertCell();
                cellYTDPlan.innerHTML =
                    '<input type="text" class="form-control text-center font-weight-bold" name="plan_ytd" readonly>';

                // Membuat baris Actual
                const newRowActual = table.insertRow();
                newRowActual.classList.add('actual-row');

                // Cell kosong untuk checkbox (karena rowspan kategori)
                newRowActual.insertCell();

                // Tipe Actual
                const cellTypeActual = newRowActual.insertCell();
                cellTypeActual.className = 'font-weight-bold text-success';
                cellTypeActual.textContent = 'Actual';

                // Input bulan Jan-Dec untuk Actual
                for (let i = 0; i < 12; i++) {
                    const cell = newRowActual.insertCell();
                    cell.innerHTML =
                        `<input type="text" class="form-control text-center" name="actual_values[]" oninput="calculateYTD()">`;
                }

                // YTD Actual
                const cellYTDActual = newRowActual.insertCell();
                cellYTDActual.innerHTML =
                    '<input type="text" class="form-control text-center font-weight-bold" name="actual_ytd" readonly>';
            }

            function processRow(row, category, type, summaryData) {
                const inputs = row.querySelectorAll(`input[name="${type.toLowerCase()}_values[]"]`);
                const ytdInput = row.querySelector(`input[name="${type.toLowerCase()}_ytd"]`);

                const monthlyValues = Array.from(inputs).map(input => {
                    const value = input.value.trim();
                    return value === '' ? 0 : parseFloat(value) || 0;
                });

                const ytdValue = ytdInput ? (parseFloat(ytdInput.value) || 0) : 0;

                summaryData.push({
                    category: category,
                    type: type,
                    month_1: monthlyValues[0] || 0,
                    month_2: monthlyValues[1] || 0,
                    month_3: monthlyValues[2] || 0,
                    month_4: monthlyValues[3] || 0,
                    month_5: monthlyValues[4] || 0,
                    month_6: monthlyValues[5] || 0,
                    month_7: monthlyValues[6] || 0,
                    month_8: monthlyValues[7] || 0,
                    month_9: monthlyValues[8] || 0,
                    month_10: monthlyValues[9] || 0,
                    month_11: monthlyValues[10] || 0,
                    month_12: monthlyValues[11] || 0,
                    ytd: ytdValue
                });
            }

            function saveData() {
                const tableRows = document.querySelectorAll('#summaryBody tr');
                const summaryData = {};

                let currentCategory = '';

                for (let i = 0; i < tableRows.length; i += 2) {
                    const planRow = tableRows[i];
                    const actualRow = tableRows[i + 1];

                    const categoryCell = planRow.querySelector('td:nth-child(2)');
                    if (!categoryCell) continue;

                    currentCategory = categoryCell.textContent.trim();

                    // Plan inputs
                    const planInputs = planRow.querySelectorAll('input[name^="plan_values"]');
                    const ytdPlanInput = planRow.querySelector('input[name^="plan_ytd"]');

                    // Actual inputs
                    const actualInputs = actualRow.querySelectorAll('input[name^="actual_values"]');
                    const ytdActualInput = actualRow.querySelector('input[name^="actual_ytd"]');

                    const monthlyPlan = Array.from(planInputs).map(input => {
                        const val = parseFloat(input.value);
                        return isNaN(val) ? 0 : val;
                    });

                    const monthlyActual = Array.from(actualInputs).map(input => {
                        const val = parseFloat(input.value);
                        return isNaN(val) ? 0 : val;
                    });

                    const planYTD = parseFloat(ytdPlanInput?.value) || 0;
                    const actualYTD = parseFloat(ytdActualInput?.value) || 0;

                    summaryData[currentCategory] = {
                        plan_values: monthlyPlan,
                        plan_ytd: planYTD,
                        actual_values: monthlyActual,
                        actual_ytd: actualYTD
                    };
                }

                console.log("Summary data to send:", summaryData);

                fetch('{{ route('crp.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            summaryData
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert('Data berhasil disimpan!');
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Terjadi kesalahan:', error);
                        alert('Gagal menyimpan data.');
                    });
            }



            // function saveData() {
            //     const tableRows = document.querySelectorAll('#summaryBody tr');
            //     const summaryData = {};

            //     let currentCategory = '';
            //     tableRows.forEach((row) => {
            //         const planActual = row.querySelector('td:nth-child(3)')?.textContent?.trim();

            //         if (planActual === 'Plan') {
            //             const categorySelect = row.querySelector('select[name="category[]"]');
            //             currentCategory = categorySelect?.value;

            //             if (!currentCategory) return;

            //             const planInputs = row.querySelectorAll(`input[name^="plan_values"]`);
            //             const ytdInput = row.querySelector(`input[name^="plan_ytd"]`);

            //             const actualInputs = row.querySelectorAll(`input[name^="actual_values"]`);
            //             const actualYTDInput = row.querySelector(`input[name^="actual_ytd"]`);

            //             const monthlyPlan = Array.from(planInputs).map(input => {
            //                 const val = parseFloat(input.value);
            //                 return isNaN(val) ? 0 : val;
            //             });

            //             const monthlyActual = Array.from(actualInputs).map(input => {
            //                 const val = parseFloat(input.value);
            //                 return isNaN(val) ? 0 : val;
            //             });

            //             const planYTD = parseFloat(ytdInput?.value) || 0;
            //             const actualYTD = parseFloat(actualYTDInput?.value) || 0;

            //             summaryData[currentCategory] = {
            //                 plan_values: monthlyPlan,
            //                 plan_ytd: planYTD,
            //                 actual_values: monthlyActual,
            //                 actual_ytd: actualYTD
            //             };
            //         }
            //     });

            //     console.log("Summary data to send:", summaryData);

            //     fetch('{{ route('crp.store') }}', {
            //         method: 'POST',
            //         headers: {
            //             'Content-Type': 'application/json',
            //             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            //         },
            //         body: JSON.stringify({ summaryData })
            //     })
            //     .then(response => response.json())
            //     .then(data => {
            //         alert('Data berhasil disimpan!');
            //         location.reload();
            //     })
            //     .catch(error => {
            //         console.error('Terjadi kesalahan:', error);
            //         alert('Gagal menyimpan data.');
            //     });
            // }




            function deletePermanen() {
                let checked = document.querySelectorAll('input[name="record[]"]:checked');
                let ids = [];

                checked.forEach((checkbox) => {
                    // Pisahkan ID Plan dan Actual
                    let values = checkbox.value.split(',');
                    ids.push(...values);
                });

                if (ids.length === 0) {
                    alert("Pilih minimal satu data untuk dihapus.");
                    return;
                }

                if (confirm("Yakin ingin menghapus data terpilih?")) {
                    // Kirim ke endpoint Laravel pakai AJAX / fetch / form tersembunyi
                    fetch('{{ route('crp.deletePermanen') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                ids: ids
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("Data berhasil dihapus.");
                                location.reload();
                            } else {
                                alert("Gagal menghapus data.");
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            }

            function resetInputs() {
                document.querySelectorAll("#tabelsummary tbody input[type='text']").forEach(input => {
                    input.value = "";
                });
            }
        </script>


        <script>
            document.addEventListener("DOMContentLoaded", function() {
                calculateYTD(); // Panggil fungsi untuk menghitung YTD saat halaman dimuat
            });
            // Fungsi untuk menghitung YTD
            function calculateYTD() {
                const rows = document.querySelectorAll('#tabelsummary tbody tr');
                console.log('Jumlah baris:', rows.length);

                const currentMonth = new Date().getMonth() + 1; // Mendapatkan bulan saat ini, 1-12

                for (let i = 0; i < rows.length; i += 2) {
                    const planRow = rows[i];
                    const actualRow = rows[i + 1]; // Mungkin ada atau tidak

                    // --- Hitung YTD PLAN (FY dan Existing) ---
                    let totalPlanFY = 0;
                    let totalPlanExisting = 0;
                    if (planRow) {
                        for (let col = 3; col <= 14; col++) { // Kolom bulan 1 hingga bulan 12
                            const input = planRow.cells[col]?.querySelector('input');
                            if (input) {
                                totalPlanFY += parseFloat(input.value) || 0; // Untuk menghitung FY
                                if (col <= (2 + currentMonth)) { // Untuk menghitung Existing (bulan yang sudah terlewati)
                                    totalPlanExisting += parseFloat(input.value) || 0;
                                }
                            }
                        }
                        const ytdPlanInputFY = planRow.cells[15]?.querySelector('input'); // Kolom YTD untuk FY (15)
                        const ytdPlanInputExisting = planRow.cells[16]?.querySelector('input'); // Kolom YTD untuk Existing (16)
                        console.log('Plan YTD FY:', totalPlanFY, 'Input:', ytdPlanInputFY);
                        console.log('Plan YTD Existing:', totalPlanExisting, 'Input:', ytdPlanInputExisting);

                        if (ytdPlanInputFY) {
                            ytdPlanInputFY.value = Math.round(totalPlanFY); // Mengisi nilai YTD FY
                        }
                        if (ytdPlanInputExisting) {
                            ytdPlanInputExisting.value = Math.round(totalPlanExisting); // Mengisi nilai YTD Existing
                        }
                    }

                    // --- Hitung YTD ACTUAL (FY dan Existing) ---
                    let totalActualFY = 0;
                    let totalActualExisting = 0;
                    if (actualRow) {
                        for (let col = 2; col <= 13; col++) { // Kolom bulan 1 hingga bulan 12
                            const input = actualRow.cells[col]?.querySelector('input');
                            if (input) {
                                totalActualFY += parseFloat(input.value) || 0; // Untuk menghitung FY
                                if (col <= (1 + currentMonth)) { // Untuk menghitung Existing (bulan yang sudah terlewati)
                                    totalActualExisting += parseFloat(input.value) || 0;
                                }
                            }
                        }
                        const ytdActualInputFY = actualRow.cells[14]?.querySelector(
                            'input[name*="actual_ytd"]'); // Kolom Actual YTD untuk FY (14)
                        const ytdActualInputExisting = actualRow.cells[15]?.querySelector(
                            'input[name*="actual_ytd_existing"]'); // Kolom Actual YTD untuk Existing (15)
                        console.log('Actual YTD FY:', totalActualFY, 'Input:', ytdActualInputFY);
                        console.log('Actual YTD Existing:', totalActualExisting, 'Input:', ytdActualInputExisting);

                        if (ytdActualInputFY) {
                            ytdActualInputFY.value = Math.round(totalActualFY); // Mengisi nilai YTD Actual FY
                        }
                        if (ytdActualInputExisting) {
                            ytdActualInputExisting.value = Math.round(totalActualExisting); // Mengisi nilai YTD Actual Existing
                        }
                    }
                }
            }
        </script>
    </main>
@endsection
