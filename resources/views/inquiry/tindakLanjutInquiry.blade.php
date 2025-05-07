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
                    <li class="breadcrumb-item active"><a href="{{ route('reportInquiry') }}">Menu Tindak Lanjut Inquiry Sales</a></li>
                    <li class="breadcrumb-item active">Tindak Lanjut Inquiry Sales</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tampilan Formulir Inquiry Sales</h5>
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


                    <script>
                        var typeMaterials = {!! json_encode($typeMaterials) !!};
                        console.log(typeMaterials); // Untuk memastikan data benar
                    </script>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;"><input type="checkbox" onclick="toggle(this);"></th>
                                    <th style="width: 30px;">No</th>
                                    <th style="width: 190px;">Nama Material</th>
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
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                @foreach ($materials as $index => $material)
                                    <tr>
                                        <td>
                                            @if (empty($material['id_type']) &&
                                                    empty($material['thickness']) &&
                                                    empty($material['weight']) &&
                                                    empty($material['inner_diameter']) &&
                                                    empty($material['outer_diameter']) &&
                                                    empty($material['length']) &&
                                                    empty($material['pcs']) &&
                                                    empty($material['qty']) &&
                                                    empty($material['konfirmasi']) &&
                                                    empty($material['no_po']) &&
                                                    empty($material['rencana_kedatangan']))
                                                <input type="checkbox" name="record">
                                            @endif
                                        </td>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <select name="id_type" class="material-dropdown" style="width: 180px;">
                                                <option value="" disabled selected>Cari Material...</option>
                                                @foreach ($typeMaterials as $type)
                                                    <option disabled value="{{ $type->id }}"
                                                        {{ $material->id_type == $type->id ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="jenis" class="jenis-dropdown" style="width: 80px;" disabled>
                                                <option value="Flat" {{ $material['jenis'] == 'Flat' ? 'selected' : '' }}>
                                                    Flat</option>
                                                <option value="Round"
                                                    {{ $material['jenis'] == 'Round' ? 'selected' : '' }}>Round</option>
                                                <option value="Honed Tube"
                                                    {{ $material['jenis'] == 'Honed Tube' ? 'selected' : '' }}>Honed Tube
                                                </option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="thickness" value="{{ $material['thickness'] }}"
                                                size="10" readonly></td>
                                        <td><input type="text" name="weight" value="{{ $material['weight'] }}"
                                                size="5" readonly></td>
                                        <td><input type="text" name="inner_diameter"
                                                value="{{ $material['inner_diameter'] }}" size="10" disabled readonly></td>
                                        <td><input type="text" name="outer_diameter"
                                                value="{{ $material['outer_diameter'] }}" size="10" disabled readonly></td>
                                        <td><input type="text" name="length" value="{{ $material['length'] }}"
                                                size="10" readonly></td>
                                        <td><input type="text" name="pcs" value="{{ $material['pcs'] }}"
                                                size="10" required readonly></td>
                                        <td><input type="text" name="qty" value="{{ $material['qty'] }}"
                                                size="10" required readonly></td>
                                        <td>
                                            <select name="konfirmasi" class="jenis-dropdown" style="width: 100%;">
                                                <option value="OK"
                                                    {{ $material['konfirmasi'] == 'OK' ? 'selected' : '' }}>OK</option>
                                                <option value="NOT OK"
                                                    {{ $material['konfirmasi'] == 'NOT OK' ? 'selected' : '' }}>NOT OK
                                                </option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="no_po" value="{{ $material['no_po'] }}"
                                                size="10" required></td>
                                        <td><input type="text" name="rencana_kedatangan"
                                                value="{{ $material['rencana_kedatangan'] }}" size="10" required></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary" onclick="saveTable()">Save</button>
                    <button class="btn btn-secondary" onclick="history.back()">Back</button>
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function toggle(source) {
                checkboxes = document.getElementsByName('record');
                for (var i = 0, n = checkboxes.length; i < n; i++) {
                    checkboxes[i].checked = source.checked;
                }
            }

            function deleteRow() {
                var tableBody = document.getElementById('table-body');
                var rows = tableBody.querySelectorAll('tr');
                rows.forEach(function(row) {
                    var checkbox = row.querySelector('input[name="record"]');
                    if (checkbox && checkbox.checked) {
                        tableBody.removeChild(row);
                    }
                });
            }

            function addRow() {
                var tableBody = document.getElementById('table-body');
                var rowCount = tableBody.rows.length;
                var newRow = tableBody.insertRow(rowCount);

                var cell1 = newRow.insertCell(0);
                var cell2 = newRow.insertCell(1);
                var cell3 = newRow.insertCell(2);
                var cell4 = newRow.insertCell(3);
                var cell5 = newRow.insertCell(4);
                var cell6 = newRow.insertCell(5);
                var cell7 = newRow.insertCell(6);
                var cell8 = newRow.insertCell(7);
                var cell9 = newRow.insertCell(8);
                var cell10 = newRow.insertCell(9);
                var cell11 = newRow.insertCell(10);
                var cell12 = newRow.insertCell(11);
                var cell13 = newRow.insertCell(12);
                var cell14 = newRow.insertCell(13);

                cell1.innerHTML = '<input type="checkbox" name="record">';
                cell2.innerHTML = rowCount + 1;

                var idTypeDropdown = `<select name="id_type" class="material-dropdown" style="width: 180px;">
        <option value="" disabled selected>Cari Material...</option>`;
                @foreach ($typeMaterials as $type)
                    idTypeDropdown += `<option value="{{ $type->id }}">{{ $type->type_name }}</option>`;
                @endforeach
                idTypeDropdown += `</select>`;
                cell3.innerHTML = idTypeDropdown;

                cell4.innerHTML = `
        <select name="jenis" class="jenis-dropdown" style="width: 80px;">
            <option value="Flat">Flat</option>
            <option value="Round">Round</option>
            <option value="Honed Tube">Honed Tube</option>
        </select>
    `;
                cell5.innerHTML = '<input type="text" name="thickness" size="10">';
                cell6.innerHTML = '<input type="text" name="weight" size="5">';
                cell7.innerHTML = '<input type="text" name="inner_diameter" size="10">';
                cell8.innerHTML = '<input type="text" name="outer_diameter" size="10">';
                cell9.innerHTML = '<input type="text" name="length" size="10">';
                cell10.innerHTML = '<input type="text" name="pcs" size="10" required>';
                cell11.innerHTML = '<input type="text" name="qty" size="10" required>';
                cell12.innerHTML = `
        <select name="konfirmasi" class="jenis-dropdown" style="width: 80px;">
            <option value="OK">OK</option>
            <option value="NOT OK">NOT OK</option>
        </select>
    `;
                cell13.innerHTML = '<input type="text" name="no_po" size="10" required>';
                cell14.innerHTML = '<input type="text" name="rencana_kedatangan" size="10" required>';

                updateDropdownListeners(); // Memastikan listener dropdown diperbarui
            }

            function updateDropdownListeners() {
                var dropdowns = document.querySelectorAll('.jenis-dropdown');
                dropdowns.forEach(function(dropdown) {
                    dropdown.addEventListener('change', function() {
                        var row = dropdown.closest('tr');
                        var thickness = row.querySelector('input[name="thickness"]');
                        var weight = row.querySelector('input[name="weight"]');
                        var innerDiameter = row.querySelector('input[name="inner_diameter"]');
                        var outerDiameter = row.querySelector('input[name="outer_diameter"]');

                        if (dropdown.value === 'Flat') {
                            thickness.disabled = false;
                            weight.disabled = false;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = true;
                        } else if (dropdown.value === 'Round') {
                            thickness.disabled = true;
                            weight.disabled = false;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = true;
                        } else if (dropdown.value === 'Honed Tube') {
                            thickness.disabled = true;
                            weight.disabled = true;
                            innerDiameter.disabled = false;
                            outerDiameter.disabled = false;
                        } else {
                            thickness.disabled = false;
                            weight.disabled = false;
                            innerDiameter.disabled = false;
                            outerDiameter.disabled = false;
                        }
                    });

                    // Trigger change event to set initial state
                    dropdown.dispatchEvent(new Event('change'));
                });
            }

            function saveTable() {
                var tableBody = document.getElementById('table-body');
                var rows = tableBody.querySelectorAll('tr');
                var data = {
                    id_inquiry: '{{ $inquiry->id }}',
                    kode_inquiry: '{{ $inquiry->kode_inquiry }}',
                    order_from: '{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}',
                    create_by: '{{ $inquiry->create_by }}',
                    materials: []
                };

                rows.forEach(function(row, index) {
                    var idTypeElement = row.querySelector('select[name="id_type"]');
                    var jenisElement = row.querySelector('select[name="jenis"]');
                    var thicknessElement = row.querySelector('input[name="thickness"]');
                    var weightElement = row.querySelector('input[name="weight"]');
                    var innerDiameterElement = row.querySelector('input[name="inner_diameter"]');
                    var outerDiameterElement = row.querySelector('input[name="outer_diameter"]');
                    var lengthElement = row.querySelector('input[name="length"]');
                    var pcsElement = row.querySelector('input[name="pcs"]');
                    var qtyElement = row.querySelector('input[name="qty"]');
                    var konfirmasiElement = row.querySelector('select[name="konfirmasi"]');
                    var noPOElement = row.querySelector('input[name="no_po"]');
                    var rencanaKedatanganElement = row.querySelector('input[name="rencana_kedatangan"]');

                    if (idTypeElement && jenisElement && thicknessElement && weightElement && innerDiameterElement &&
                        outerDiameterElement && lengthElement && pcsElement && qtyElement && konfirmasiElement &&
                        noPOElement && rencanaKedatanganElement) {
                        var rowData = {
                            id_type: idTypeElement.value,
                            jenis: jenisElement.value,
                            thickness: thicknessElement.value,
                            weight: weightElement.value,
                            inner_diameter: innerDiameterElement.value,
                            outer_diameter: outerDiameterElement.value,
                            length: lengthElement.value,
                            pcs: pcsElement.value,
                            qty: qtyElement.value,
                            konfirmasi: konfirmasiElement.value,
                            no_po: noPOElement.value,
                            rencana_kedatangan: rencanaKedatanganElement.value
                        };
                        data.materials.push(rowData);
                    } else {
                        console.error("One or more elements are missing in the row:", row);
                    }
                });

                $.ajax({
                    url: '{{ route('inquiry.tindakLanjutInquiry') }}',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert('Inquiry updated successfully');
                        window.location.href = '{{ route('reportInquiry', $inquiry->id) }}';
                    },
                    error: function(error) {
                        console.error(error);
                        alert('An error occurred');
                    }
                });
            }

            // Panggil updateDropdownListeners untuk menginisialisasi listener pada dropdown yang sudah ada
            document.addEventListener('DOMContentLoaded', function() {
                updateDropdownListeners();
            });
        </script>
    </main><!-- End #main -->
@endsection
