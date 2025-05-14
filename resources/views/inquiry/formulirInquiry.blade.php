@extends('layout')

@section('content')
    <style>
        body {
            font-family: 'Cambria', serif;
        }

        .swal2-popup {
            font-size: 0.6rem;
            width: 300px;
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
            border: 1px solid #015974;
            padding: 8px;
            text-align: center;
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

        label {
            color: darkblue;
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
                    <li class="breadcrumb-item active"><a href="{{ route('createinquiry') }}">Menu Inquiry Sales</a></li>
                    <li class="breadcrumb-item active">Formulir Inquiry Sales</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <div class="form-section mt-3">
                        <div class="form-group">
                            <label>Create By :</label>
                            <div class="form-value">{{ $inquiry->create_by }}</div>
                        </div>
                        <div class="form-group">
                            <label>Category :</label>
                            <div class="form-value">{{ $inquiry->loc_imp }}</div>
                        </div>
                        <div class="form-group">
                            <label>Reference :</label>
                            <div class="form-value">{{ $inquiry->kode_inquiry }}</div>
                        </div>
                        <div class="form-group">
                            <label>Customer :</label>
                            <div class="form-value">{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Supplier :</label>
                            <div class="form-value">{{ $inquiry->supplier }}</div>
                        </div>
                        <div class="form-group">
                            <label>Date Create :</label>
                            <div class="form-value">{{ $inquiry->created_at }}</div>
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
                                    <th style="width: 25px;"><input type="checkbox" onclick="toggle(this);"></th>
                                    <th style="width: 25px;">No</th>
                                    <th style="width: 100px;">Raw Material</th>
                                    <th style="width: 50px;">Shapes</th>
                                    <th style="width: 30px;">Thinkness</th>
                                    <th style="width: 30px;">Width</th>
                                    <th style="width: 30px;">Inner-Diameter</th>
                                    <th style="width: 30px;">Outer-Diameter</th>
                                    <th style="width: 50px;">Length</th>
                                    <th style="width: 50px; text-align:center;">Qty
                                        <p style="font-size: 9pt; text-align:center;">(in Pcs)</p>
                                    </th>
                                    <th style="width: 30px;">Forecast Month 1</th>
                                    <th style="width: 30px;">Forecast Month 2</th>
                                    <th style="width: 30px;">Forecast Month 3</th>
                                    <th style="width: 60px;">Ship-to</th>
                                    <th style="width: 60px;">Sales Order</th>
                                    {{-- <th style="width: 60px;">PO Number</th> --}}
                                    <th style="width: 50px;">Remark</th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                
                            </tbody>
                        </table>
                    </div>
                    <a href="#" class="btn btn-success add-row-button" onclick="addRow()">Tambah Baris</a>
                    <a href="#" class="btn btn-danger delete-row-button" onclick="deleteRow()">Hapus Baris</a>
                    <button class="btn btn-primary" onclick="saveTable()">Save</button>
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

        <!-- Modal for Uploading File -->
        <div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadFileModalLabel">Upload File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="uploadFileForm">
                            @csrf
                            <input type="hidden" id="inquiryIdForFile" name="id_inquiry">

                            <div class="mb-3">
                                <label for="uploadFile" class="form-label">Choose File</label>
                                <input type="file" id="uploadFile" name="file" class="form-control"
                                    accept="application/pdf,image/*" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="submitUploadFileForm()">Upload</button>
                    </div>
                </div>
            </div>
        </div>


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
                var cell15 = newRow.insertCell(14);
                // var cell16 = newRow.insertCell(15);
                var cell16 = newRow.insertCell(15);

                cell1.innerHTML = '<input type="checkbox" name="record">';
                cell2.innerHTML = rowCount + 1;

                var idTypeDropdown = `<select name="id_type" class="material-dropdown" style="width: 180px; height:30px">
                <option value="" disabled selected>Cari Material...</option>`;
                typeMaterials.forEach(function(type) {
                    idTypeDropdown += `<option value="${type.id}">${type.type_name}</option>`;
                });
                idTypeDropdown += `</select>`;
                cell3.innerHTML = idTypeDropdown;

                cell4.innerHTML = `<select name="jenis" class="jenis-dropdown" style="width: 80px; height:30px">
                                        <option value="Flat">Flat</option>
                                        <option value="Round">Round</option>
                                        <option value="Honed Tube">Honed Tube</option>
                                    </select>`;
                cell5.innerHTML = '<input type="text" name="thickness" size="5">';
                cell6.innerHTML = '<input type="text" name="weight" size="5">';
                cell7.innerHTML = '<input type="text" name="inner_diameter" size="5">';
                cell8.innerHTML = '<input type="text" name="outer_diameter" size="5">';
                cell9.innerHTML = '<input type="text" name="length" size="10">';
                cell10.innerHTML = '<input type="text" name="qty" size="5" required>';
                cell11.innerHTML = '<input type="text" name="m1" size="5" required>';
                cell12.innerHTML = '<input type="text" name="m2" size="5">';
                cell13.innerHTML = '<input type="text" name="m3" size="5">';
                cell14.innerHTML = `<select name="ship" class="jenis-dropdown" style="width: 100px; height:30px">
                                        <option value="Deltamas">Deltamas</option>
                                        <option value="DS8">DS8</option>
                                    </select>`;
                cell15.innerHTML = '<input type="text" name="so" size="20" pattern="SO/[0-9]{4}/[0-9]{4}" title="Format harus SO/Tahun/4DigitAngka (contoh: SO/2024/1234)" required>';
                // cell16.innerHTML = '<input type="text" name="nopo" size="10" required>';
                cell16.innerHTML = '<input type="text" name="note" size="10" required>';


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
                            weight.disabled = true;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = false;
                        } else if (dropdown.value === 'Honed Tube') {
                            thickness.disabled = true;
                            weight.disabled = true;
                            innerDiameter.disabled = false;
                            outerDiameter.disabled = false;
                        } else {
                            thickness.disabled = false;
                            weight.disabled = false;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = true;
                        }
                    });

                    // Trigger change event to set initial state
                    dropdown.dispatchEvent(new Event('change'));
                });
            }

            // function saveTable() {
            //     var tableBody = document.getElementById('table-body');
            //     var rows = tableBody.querySelectorAll('tr');
            //     var data = {
            //         id_inquiry: '{{ $inquiry->id }}',
            //         kode_inquiry: '{{ $inquiry->kode_inquiry }}',
            //         order_from: '{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}',
            //         create_by: '{{ $inquiry->create_by }}',
            //         materials: []
            //     };

            //     rows.forEach(function(row, index) {
            //         var idTypeElement = row.querySelector('select[name="id_type"]');
            //         var jenisElement = row.querySelector('select[name="jenis"]');
            //         var thicknessElement = row.querySelector('input[name="thickness"]');
            //         var weightElement = row.querySelector('input[name="weight"]');
            //         var innerDiameterElement = row.querySelector('input[name="inner_diameter"]');
            //         var outerDiameterElement = row.querySelector('input[name="outer_diameter"]');
            //         var lengthElement = row.querySelector('input[name="length"]');
            //         var qtyElement = row.querySelector('input[name="qty"]');
            //         var m1Element = row.querySelector('input[name="m1"]');
            //         var m2Element = row.querySelector('input[name="m2"]');
            //         var m3Element = row.querySelector('input[name="m3"]');
            //         var shipElement = row.querySelector('input[name="ship"]');
            //         var noteElement = row.querySelector('input[name="note"]');

            //         // Log all elements to see if they are found
            //         console.log(`Row ${index + 1}:`);
            //         console.log('idTypeElement:', idTypeElement ? idTypeElement.value : 'not found');
            //         console.log('jenisElement:', jenisElement ? jenisElement.value : 'not found');
            //         console.log('thicknessElement:', thicknessElement ? thicknessElement.value : 'not found');
            //         console.log('weightElement:', weightElement ? weightElement.value : 'not found');
            //         console.log('innerDiameterElement:', innerDiameterElement ? innerDiameterElement.value :
            //             'not found');
            //         console.log('outerDiameterElement:', outerDiameterElement ? outerDiameterElement.value :
            //             'not found');
            //         console.log('lengthElement:', lengthElement ? lengthElement.value : 'not found');
            //         console.log('qtyElement:', qtyElement ? qtyElement.value : 'not found');
            //         console.log('m1Element:', m1Element ? m1Element.value : 'not found');
            //         console.log('m2Element:', m2Element ? m2Element.value : 'not found');
            //         console.log('m3Element:', m3Element ? m3Element.value : 'not found');
            //         console.log('shipElement:', shipElement ? shipElement.value : 'not found');
            //         console.log('noteElement:', noteElement ? noteElement.value : 'not found');

            //         if (idTypeElement && jenisElement && thicknessElement && weightElement && innerDiameterElement &&
            //                 outerDiameterElement && lengthElement && qtyElement && m1Element && m2Element && m3Element &&
            //                 shipElement && noteElement) {
            //         var rowData = {
            //                     id_type: idTypeElement.value,
            //                     jenis: jenisElement.value,
            //                     thickness: thicknessElement.value,
            //                     weight: weightElement.value,
            //                     inner_diameter: innerDiameterElement.value,
            //                     outer_diameter: outerDiameterElement.value,
            //                     length: lengthElement.value,
            //                     qty: qtyElement.value,
            //                     m1: m1Element.value,
            //                     m2: m2Element.value,
            //                     m3: m3Element.value,
            //                     ship: shipElement.value,
            //                     note: noteElement.value
            //             };

            //                 data.materials.push(rowData);
            //             } else {
            //                 console.error("One or more elements are missing in the row:", row);
            //             }
            //         });

            //     $.ajax({
            //         url: '{{ route('inquiry.previewSS') }}',
            //         method: 'POST',
            //         data: JSON.stringify(data),
            //         contentType: 'application/json',
            //         headers: {
            //             'X-CSRF-TOKEN': '{{ csrf_token() }}'
            //         },
            //         success: function(response) {
            //             alert('Inquiry updated successfully');
            //             window.location.href = '{{ route('showFormSS', $inquiry->id) }}';
            //         },
            //         error: function(error) {
            //             console.error(error);
            //             alert('An error occurred');
            //         }
            //     });
            // }

            function saveTable() {
                var tableBody = document.getElementById('table-body');
                var rows = tableBody.querySelectorAll('tr');

                var data = {
                    id_inquiry: '{{ $inquiry->id }}',
                    kode_inquiry: '{{ $inquiry->kode_inquiry }}',
                    id_customer: '{{ $inquiry->customer ? $inquiry->customer->id : 'N/A' }}',
                    create_by: '{{ $inquiry->create_by }}',
                    materials: [] // Inisialisasi array materials
                };

                var currentYear = new Date().getFullYear(); // Ambil tahun saat ini

                rows.forEach(function(row) {
                    var idTypeElement = row.querySelector('select[name="id_type"]');
                    var jenisElement = row.querySelector('select[name="jenis"]');
                    var thicknessElement = row.querySelector('input[name="thickness"]');
                    var weightElement = row.querySelector('input[name="weight"]');
                    var innerDiameterElement = row.querySelector('input[name="inner_diameter"]');
                    var outerDiameterElement = row.querySelector('input[name="outer_diameter"]');
                    var lengthElement = row.querySelector('input[name="length"]');
                    var qtyElement = row.querySelector('input[name="qty"]');
                    var m1Element = row.querySelector('input[name="m1"]');
                    var m2Element = row.querySelector('input[name="m2"]');
                    var m3Element = row.querySelector('input[name="m3"]');
                    var shipElement = row.querySelector('select[name="ship"]');
                    var soElement = row.querySelector('input[name="so"]');
                    var noteElement = row.querySelector('input[name="note"]');

                    // Ambil input SO (tidak ada pembatasan digit)
                    var soNumber = soElement ? soElement.value.trim() : '';

                    // Pastikan SO diinput dengan angka
                    if (!/^\d+$/.test(soNumber)) {
                        alert('SO harus berisi angka saja!');
                        return;
                    }

                    // Format SO otomatis: "SO/Tahun/[Angka yang diinput]"
                    var formattedSO = `SO/${currentYear}/${soNumber}`;

                    if (idTypeElement && jenisElement.value !== "" && idTypeElement.value !== "") {
                        var rowData = {
                            id_type: idTypeElement.value,
                            jenis: jenisElement.value,
                            thickness: thicknessElement ? thicknessElement.value : null,
                            weight: weightElement ? weightElement.value : null,
                            inner_diameter: innerDiameterElement ? innerDiameterElement.value : null,
                            outer_diameter: outerDiameterElement ? outerDiameterElement.value : null,
                            length: lengthElement ? lengthElement.value : null,
                            qty: qtyElement ? qtyElement.value : null,
                            m1: m1Element ? m1Element.value : null,
                            m2: m2Element ? m2Element.value : null,
                            m3: m3Element ? m3Element.value : null,
                            ship: shipElement ? shipElement.value : null,
                            so: formattedSO, // Simpan SO dalam format otomatis
                            note: noteElement ? noteElement.value : null
                        };
                        data.materials.push(rowData);
                    }
                });

                // Cek apakah materials tidak kosong
                if (data.materials.length === 0) {
                    alert('Silakan tambahkan material terlebih dahulu.');
                    return;
                }

                // Kirim data via AJAX
                $.ajax({
                    url: '{{ route('inquiry.previewSS') }}',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Sukses!',
                            text: 'Inquiry updated successfully',
                            icon: 'success',
                            timer: 2000,
                            timerProgressBar: true,
                            willClose: () => {
                                window.location.href = '{{ route('showFormSS', $inquiry->id) }}';
                            }
                        });
                    },
                    error: function(error) {
                        console.error('Error occurred:', error);
                        if (error.responseJSON) {
                            console.error('Error response:', error.responseJSON);
                        }
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred',
                            icon: 'error',
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                });
            }
            // Panggil updateDropdownListeners untuk menginisialisasi listener pada dropdown yang sudah ada
            document.addEventListener('DOMContentLoaded', function() {
                updateDropdownListeners();
            });
        </script>

        <script>
            function openUploadFileModal(id) {
                document.getElementById('inquiryIdForFile').value = id; // Set inquiry ID

                // Tampilkan modal
                var myModal = new bootstrap.Modal(document.getElementById('uploadFileModal'), {});
                myModal.show();
            }

            function submitUploadFileForm() {
                const form = document.getElementById('uploadFileForm');
                const formData = new FormData(form); // Mengambil data dari form

                $.ajax({
                    url: '{{ route('uploadFile') }}', // Ganti dengan route yang benar
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload(); // Reload halaman setelah upload
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', xhr.responseJSON.error, 'error'); // Tampilkan pesan error
                    }
                });
            }
        </script>
    </main><!-- End #main -->
@endsection
