@extends('layout')

@section('content')

    <style>
        body {
            font-family: 'Cambria', serif;
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

        .fotext {
            font-family: 'Cambria', serif;
            font-size: 10pt;
            font-weight: bold;
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

        .swal2-popup {
            width: 300px;
            /* Mengatur lebar pop-up */
            font-size: 0.7rem;
            /* Mengatur ukuran font */
        }

        .swal2-title {
            font-family: 'Cambria', serif;
        }
        .btn-navigation {
            background-color: #2563eb;
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .btn-navigation:hover {
            background-color: #1e40af;
        }

        .btn-navigation i {
            margin-right: 0.5rem;
        }

        .btn-navigation-right {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
    </style>

<head>
    <base href="http://localhost/local_fastware/public/">
</head>
    <main id="main" class="main">
        <div id="main-content">
        <div class="pagetitle">
            <h1>Preview</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><a href="{{ route('createinquiry') }}">Menu Inquiry Sales</a></li>
                    <li class="breadcrumb-item active">Formulir Inquiry Sales</li>
                </ol>
            </nav>
        </div>
        
            <!-- Isi halaman inquiry sales Anda di sini -->
        
            <div class="btn-navigation-right">
                <button
                    type="button"
                    aria-label="Previous Page"
                    class="btn-navigation"
                    onclick="navigateInquiry('previous')"
                >
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <button
                    type="button"
                    aria-label="Next Page"
                    class="btn-navigation"
                    onclick="navigateInquiry('next')"
                >
                    Next <i class="fas fa-chevron-right"></i>
                </button>
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
                            <div class="form-value">
                                <td>{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}</td>
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

                    <div class="table-responsive" >
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 25px;">No</th>
                                    <th style="width: 100px;">Raw Material</th>
                                    <th style="width: 50px;">Shapes</th>
                                    <th style="width: 40px;">Thickness</th>
                                    <th style="width: 40px;">Width</th>
                                    <th style="width: 40px; text-align:center;">Inner Dia</th>
                                    <th style="width: 40px; text-align:center;">Outer Dia</th>
                                    <th style="width: 50px;">Length</th>
                                    <th style="width: 50px; text-align:center;">Qty
                                        <p style="font-size: 9pt; text-align:center;">(in Pcs)</p>
                                    </th>
                                    <th style="width: 50px; text-align:center;">Forecast Month 1</th>
                                    <th style="width: 50px; text-align:center;">Forecast Month 2</th>
                                    <th style="width: 50px; text-align:center;">Forecast Month 3</th>
                                    <th style="width: 90px; text-align:center;">Ship-to</th>
                                    <th style="width: 50px; text-align:center;">Sales Order</th>
                                    {{-- <th style="width: 50px; text-align:center;">PO Number</th> --}}
                                    <th style="width: 50px; text-align:center;">Remark</th>
                                    <th style="width: 50px; text-align:center;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="table-body">
                                @if ($inquiry->status == 1)
                                    @forelse ($materials as $index => $material)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td contenteditable="false" class="editable">
                                                <input type="hidden" name="materials[{{ $index }}][id]" value="{{ $material->id }}">

                                                <select name="id_type" class="material-dropdown" style="width: 180px;"
                                                    disabled>
                                                    <option value="" disabled selected>Cari Material...</option>
                                                    @foreach ($typeMaterials as $type)
                                                        <option value="{{ $type->id }}"
                                                            {{ $material->id_type == $type->id ? 'selected' : '' }}>
                                                            {{ $type->type_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td contenteditable="false" class="editable">
                                                <select name="jenis" class="jenis-dropdown" style="width: 80px;" disabled
                                                    onchange="handleShapeChange(this)">
                                                    <option value="Flat"
                                                        {{ $material['jenis'] == 'Flat' ? 'selected' : '' }}>Flat</option>
                                                    <option value="Round"
                                                        {{ $material['jenis'] == 'Round' ? 'selected' : '' }}>Round
                                                    </option>
                                                    <option value="Honed Tube"
                                                        {{ $material['jenis'] == 'Honed Tube' ? 'selected' : '' }}>Honed
                                                        Tube</option>
                                                </select>
                                            </td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="thickness" value="{{ $material['thickness'] }}" size="10"
                                                    disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="weight" value="{{ $material['weight'] }}" size="5"
                                                    disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="inner_diameter" value="{{ $material['inner_diameter'] }}"
                                                    size="10" disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="outer_diameter" value="{{ $material['outer_diameter'] }}"
                                                    size="10" disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="length" value="{{ $material['length'] }}" size="10"
                                                    disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="qty" value="{{ $material['qty'] }}" size="10" disabled>
                                            </td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="m1" value="{{ $material['m1'] }}" size="10" disabled>
                                            </td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="m2" value="{{ $material['m2'] }}" size="10"
                                                    disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="m3" value="{{ $material['m3'] }}" size="10"
                                                    disabled></td>
                                            <td contenteditable="false" class="editable">
                                                <select name="ship" class="jenis-dropdown" style="width: 100px;"
                                                    disabled>
                                                    <option value="Deltamas"
                                                        {{ $material['ship'] == 'Deltamas' ? 'selected' : '' }}>Deltamas
                                                    </option>
                                                    <option value="DS8"
                                                        {{ $material['ship'] == 'DS8' ? 'selected' : '' }}>DS8</option>
                                                </select>
                                            </td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="so" value="{{ $material['so'] }}" size="10"
                                                    disabled></td>
                                            <td contenteditable="false" class="editable"><input type="text"
                                                    name="note" value="{{ $material['note'] }}" size="10"
                                                    disabled></td>
                                            <td>
                                                @if ($inquiry->status == 1)
                                                    <button class="btn btn-danger btn-sm"
                                                        onclick="deleteRow({{ $material->id }})">Delete</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="15" style="text-align: center;">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                @else
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
                                            <td>{{ $material['qty'] }}</td>
                                            <td>{{ $material['m1'] }}</td>
                                            <td>{{ $material['m2'] }}</td>
                                            <td>{{ $material['m3'] }}</td>
                                            <td>{{ $material['ship'] }}</td>
                                            <td>{{ $material['so'] }}</td>
                                            <td>{{ $material['note'] }}</td>
                                            <td></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="16" style="text-align: center;">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>

                        </table>
                    </div>
                    @if ($isFromApproval)
                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm m-1">Kembali</a>
                    @else
                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm m-1">Kembali</a>
                        @if ($inquiry->status == 1)
                            <form action="{{ route('createinquiry1', ['id' => $inquiry->id]) }}" method="GET"
                                class="d-inline">
                                <input type="hidden" name="id" value="{{ $inquiry->id }}">
                                <button type="submit" class="btn btn-primary btn-sm m-1">Submit</button>
                            </form>
                            <a class="btn btn-custom-form m-1 btn-sm"
                                href="{{ route('formulirInquiry', ['id' => $inquiry->id]) }}" title="Formulir Inquiry">
                                <i class="bi bi-file-earmark-arrow-up-fill btn btn-primary btn-sm m-1"></i>
                            </a>
                            <button id="edit-button" onclick="enableEdit()"
                                class="btn btn-primary btn-sm m-1">Edit</button>
                            <button id="save-button" onclick="saveChanges()" style="display: none;"
                                class="btn btn-primary btn-sm m-1">Save</button>
                        @endif
                    @endif


                    {{-- @if ($inquiry->status == 1)
                        <a href="{{ route('createinquiry') }}" class="btn btn-primary delete-row-button btn-sm m-1">Submit</a>
                    @endif --}}

                    {{-- <a class="btn btn-secondary add-row-button btn-sm" onclick="goBack()">Kembali</a> --}}
                    <a href="{{ route('showFormSS.pdf', $inquiry->id) }}" class="btn btn-danger btn-sm m-1">
                        <i class="bi bi-file-earmark-pdf"></i> Download PDF
                    </a>

                    @if ($inquiry->status == 2)
                        {{-- Action User Kasie --}}
                        <div class="d-flex justify-content-end">
                            @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'ILHAM CHOLID', 'JUN JOHAMIN PD', 'ANDIK TOTOK SISWOYO']))
                                <a href="#" class="btn btn-primary btn-sm m-1"
                                    onclick="approveInquiry({{ $inquiry->id }}); return false;">
                                    <i class="bi bi-check-square-fill fs-6"> Approve</i>
                                </a>
                                <a href="#" class="btn btn-danger btn-sm m-1"
                                    onclick="rejectInquiry({{ $inquiry->id }}); return false;">
                                    <i class="bi bi-file-x-fill fs-6"> Reject</i>
                                </a>
                            @endif
                        </div>
                    @endif
                    {{-- End-Code --}}

                    @if ($inquiry->status == 4)
                        {{-- Action User Dephead --}}
                        <div class="d-flex justify-content-end">
                            @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'YULMAI RIDO WINANDA', 'ANDIK TOTOK SISWOYO']))
                                <a href="#" class="btn btn-primary btn-sm m-1"
                                    onclick="approveKaDept({{ $inquiry->id }}); return false;">
                                    <i class="bi bi-check-square-fill fs-6"> Approve</i>
                                </a>
                                <a href="#" class="btn btn-danger btn-sm m-1"
                                    onclick="rejectKaDept({{ $inquiry->id }}); return false;">
                                    <i class="bi bi-file-x-fill fs-6"> Reject</i>
                                </a>
                            @endif
                        </div>
                    @endif
                    {{-- End-Code --}}

                    @if ($inquiry->status == 3)
                        {{-- Action User Inventory --}}
                        <div class="d-flex justify-content-end">
                            @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'M. IQBAL']))
                                <a href="#" class="btn btn-primary btn-sm m-1"
                                    onclick="approveInventory({{ $inquiry->id }}); return false;">
                                    <i class="bi bi-check-square-fill fs-6"> Approve</i>
                                </a>
                                <a href="#" class="btn btn-danger btn-sm m-1"
                                    onclick="rejectInventory({{ $inquiry->id }}); return false;">
                                    <i class="bi bi-file-x-fill fs-6"> Reject</i>
                                </a>
                            @endif
                        </div>
                    @endif
                    {{-- End-Code --}}
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            {{-- Start Uploaded Files --}}
                            <div class="col-lg-4">
                                <div class="form-group mb-3 mt-2">
                                    <label for="attachments" class="fw-bold mt-2">Upload Attachments (PDF, PNG, JPG,
                                        JPEG)</label>
                                    <input type="file" id="attachments" name="attachments[]" multiple
                                        accept=".pdf,.png,.jpg,.jpeg" class="form-control mt-2"
                                        onchange="updateFileList()">

                                    <button type="button" class="btn btn-success mt-3 btn-sm"
                                        onclick="uploadFiles({{ $inquiry->id }})"
                                        @if (in_array(Auth::user()->name, [
                                                'NURSALIM',
                                                'FAIZAL AFDAU',
                                                'DIAMAN DARMAWINATA',
                                                'MAMIK ABIDIN',
                                                'ABDUR RAHMAN AL FAAIZ',
                                                'FAJAR BAGASKARA',
                                            ])) disabled @endif>
                                        Upload Files
                                    </button>
                                </div>

                                <table class="table-responsive">
                                    <thead>
                                        <tr>
                                            <th>File Name Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody id="uploaded-files-list">
                                        @if (count($uploadedFiles) > 0)
                                            @foreach ($uploadedFiles as $index => $file)
                                                <tr>
                                                    <td>
                                                        <a href="{{ asset('assets/inquiry/' . $file) }}"
                                                            target="_blank">{{ $file }}</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="2">No files uploaded.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table>
                                        <h5 class="card-title text-center mt-4 mb-5" style="font-family: Cambria, serif">
                                            History Progress Inquiry Local
                                            <br>
                                            <small class="fw-bold"> "{{ $inquiry->kode_inquiry }}" </small>
                                        </h5>
                                        <thead>
                                            <tr>
                                                <th style="width : 60px">No</th>
                                                <th class="text-center" style="width : 300px">Date</th>
                                                <th class="text-center" style="width : 250px">User</th>
                                                <th class="text-center">Progress Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($progressUpdates as $index => $progress)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td style="width: 100px; text-align: center;">
                                                        {{ $progress->created_at->format('d/m/Y H:i') }}</td>
                                                    <td style="width: 400px">
                                                        {{ $progress->user ? $progress->user->name : '--- Procurement has not been updated ---' }}
                                                    </td>
                                                    <td>{{ $progress->description }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- End Code --}}

                    </div>
                </div>
            </section>
        </section>
    </div>
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
        <script>
            function goBack() {
                window.history.back();
            }
        </script>

        <script>
            function deleteRow(id) {
                if (confirm('Are you sure you want to delete this row?')) {
                    fetch(`{{ url('deleteInquiryDetail') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Row deleted successfully');
                                location.reload(); // Reload the page to see the changes
                            } else {
                                alert('Failed to delete row');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the row');
                        });
                }
            }

            function enableEdit() {
                document.querySelectorAll('.editable').forEach(element => {
                    element.querySelectorAll('input, select').forEach(input => {
                        input.removeAttribute('disabled');
                    });
                });

                // Pastikan aturan jenis material tetap berlaku setelah edit diaktifkan
                document.querySelectorAll('select[name="jenis"]').forEach(select => {
                    handleShapeChange(select);
                });

                document.getElementById('edit-button').style.display = 'none';
                document.getElementById('save-button').style.display = 'inline-block';
            }

            function saveChanges() {
                const rows = document.querySelectorAll('#table-body tr');
                const data = Array.from(rows).map(row => {
                    const cells = row.querySelectorAll('.editable');
                    return {
                        id: row.querySelector('input[type="hidden"]').value,
                        id_type: cells[0].querySelector('select').value,
                        jenis: cells[1].querySelector('select').value,
                        thickness: cells[2].querySelector('input').value,
                        weight: cells[3].querySelector('input').value,
                        inner_diameter: cells[4].querySelector('input').value,
                        outer_diameter: cells[5].querySelector('input').value,
                        length: cells[6].querySelector('input').value,
                        qty: cells[7].querySelector('input').value,
                        m1: cells[8].querySelector('input').value,
                        m2: cells[9].querySelector('input').value,
                        m3: cells[10].querySelector('input').value,
                        ship: cells[11].querySelector('select').value,
                        so: cells[12].querySelector('input').value,
                        note: cells[13].querySelector('input').value,
                    };
                });

                fetch('{{ route('updateInquiryDetails', $inquiry->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            materials: data
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Changes saved successfully');
                            document.querySelectorAll('.editable').forEach(element => {
                                element.querySelectorAll('input, select').forEach(input => {
                                    input.setAttribute('disabled', 'true');
                                });
                            });

                            document.getElementById('edit-button').style.display = 'inline-block';
                            document.getElementById('save-button').style.display = 'none';
                        } else {
                            alert('Failed to save changes');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving changes');
                    });
            }

            function handleShapeChange(selectElement) {
                const row = selectElement.closest('tr');
                const thicknessInput = row.querySelector('input[name="thickness"]');
                const weightInput = row.querySelector('input[name="weight"]');
                const innerDiameterInput = row.querySelector('input[name="inner_diameter"]');
                const outerDiameterInput = row.querySelector('input[name="outer_diameter"]');

                // Reset semua kolom agar tidak bisa diinput
                if (thicknessInput) thicknessInput.setAttribute('disabled', 'true');
                if (weightInput) weightInput.setAttribute('disabled', 'true');
                if (innerDiameterInput) innerDiameterInput.setAttribute('disabled', 'true');
                if (outerDiameterInput) outerDiameterInput.setAttribute('disabled', 'true');

                // Aktifkan hanya input yang sesuai dengan jenis yang dipilih
                if (selectElement.value === 'Flat') {
                    if (thicknessInput) thicknessInput.removeAttribute('disabled');
                    if (weightInput) weightInput.removeAttribute('disabled');
                } else if (selectElement.value === 'Round') {
                    if (outerDiameterInput) outerDiameterInput.removeAttribute('disabled');
                } else if (selectElement.value === 'Honed Tube') {
                    if (innerDiameterInput) innerDiameterInput.removeAttribute('disabled');
                    if (outerDiameterInput) outerDiameterInput.removeAttribute('disabled');
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('select[name="jenis"]').forEach(select => {
                    handleShapeChange(select); // Set awal saat halaman dimuat
                    select.addEventListener('change', () => handleShapeChange(select));
                });

                // Pastikan input tetap terkunci sebelum tombol edit ditekan
                document.querySelectorAll('.editable').forEach(element => {
                    element.querySelectorAll('input, select').forEach(input => {
                        input.setAttribute('disabled', 'true');
                    });
                });
            });
        </script>

        <script>
            function updateFileList() {
                var fileInput = document.getElementById('attachments');
                var fileListContainer = document.getElementById('uploaded-files-list');

                // Kosongkan daftar jika belum ada file
                if (fileInput.files.length === 0) {
                    fileListContainer.innerHTML = '<li>No files uploaded.</li>';
                    return;
                }

                // Kosongkan daftar sebelumnya
                fileListContainer.innerHTML = '';

                // Tampilkan semua file yang dipilih
                Array.from(fileInput.files).forEach((file, index) => {
                    var li = document.createElement('li'); // Buat item list baru
                    var link = document.createElement('a'); // Buat elemen link

                    // Buat URL objek untuk preview
                    link.href = URL.createObjectURL(file);
                    link.target = '_blank'; // Buka tautan di tab baru
                    link.textContent = file.name; // Nama file
                    li.textContent = `${index + 1}. `; // Menambahkan nomor urut
                    li.appendChild(link); // Tambahkan link ke item list

                    fileListContainer.appendChild(li); // Tambahkan item list ke dalam daftar
                });
            }

            function uploadFiles(id_inquiry) {
                var fileInput = document.getElementById('attachments');
                var formData = new FormData();

                // Tambahkan semua file yang dipilih ke formData
                for (var i = 0; i < fileInput.files.length; i++) {
                    formData.append('attachments[]', fileInput.files[i]);
                }

                formData.append('id_inquiry', id_inquiry); // Tambahkan ID inquiry

                // Kirim data ke route 'uploadFile'
                $.ajax({
                    url: '{{ route('uploadFile') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert('Files uploaded successfully');
                        updateFileList(); // Kembali update daftar
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('Error while uploading files');
                    }
                });
            }


            function navigateInquiry(direction) {
                let currentInquiryId = {{ $inquiry->id }};
                const maxId = {{ $maxInquiryId }}; // ID terbesar untuk loc_imp = 'local'

                let targetInquiryId;
                if (direction === 'next') {
                    // Jika inquiry saat ini bukan 'local', lompat ke inquiry berikutnya yang 'local'
                    targetInquiryId = currentInquiryId === maxId ? 1 : currentInquiryId + 1;
                } else if (direction === 'previous') {
                    // Jika inquiry saat ini bukan 'local', lompat ke inquiry sebelumnya yang 'local'
                    targetInquiryId = currentInquiryId === 1 ? maxId : currentInquiryId - 1;
                }

                // Lanjutkan dengan AJAX
                $.ajax({
                    url: '{{ route("showFormSS", "") }}/' + targetInquiryId, // URL inquiry berdasarkan ID
                    type: 'GET',
                    data: { source: 'ajax' },
                    success: function (response) {
                        $('#main-content').html(response); // Update konten
                        let newUrl = window.location.origin + '/local_fastware/public/showFormSS/' + targetInquiryId;
                        history.pushState(null, '', newUrl); // Update URL
                    },
                    error: function () {
                        alert('Failed to load the next inquiry');
                    }
                });
            }
        </script>


        <script>
            function approveInquiry(id) {
                $.ajax({
                    url: '{{ route('approveKaSie', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success').then(() => {
                            $('a.btn-approve, a.btn-reject').attr('disabled', true);

                            // Alihkan ke halaman approval inventory
                            window.location.href = '{{ route('showApprovalKaSie') }}';
                            // location.reload(); // Reload halaman
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }

            function rejectInquiry(id) {
                $.ajax({
                    url: '{{ route('rejectKaSie', '') }}/' + id, // Buat route untuk reject
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry rejected successfully.', 'success').then(() => {
                            location.reload(); // Reload halaman
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        </script>

        <script>
            function approveKaDept(id) {
                $.ajax({
                    url: '{{ route('approveKaDept', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan token CSRF
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success');
                        // location.reload(); // Reload halaman untuk melihat update
                        $('a.btn-approve, a.btn-reject').attr('disabled', true);

                        // Alihkan ke halaman approval inventory
                        window.location.href = '{{ route('showApprovalKaDept') }}';
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while approving the inquiry.', 'error');
                    }
                });
            }

            function rejectKaDept(id) {
                $.ajax({
                    url: '{{ route('rejectKaDept', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry rejected successfully.', 'success');
                        // location.reload(); // Reload halaman untuk melihat update
                        $('a.btn-approve, a.btn-reject').attr('disabled', true);

                        // Alihkan ke halaman approval inventory
                        window.location.href = '{{ route('showApprovalKaDept') }}';
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while rejecting the inquiry.', 'error');
                    }
                });
            }
        </script>

        <script>
            function approveInventory(id) {
                $.ajax({
                    url: '{{ route('approveInventory', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan CSRF token
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success').then(() => {
                            // location.reload();
                            $('a.btn-approve, a.btn-reject').attr('disabled', true);

                            // Alihkan ke halaman approval inventory
                            window.location.href = '{{ route('showApprovalInventory') }}';
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while approving the inquiry.', 'error');
                    }
                });
            }

            function rejectInventory(id) {
                $.ajax({
                    url: '{{ route('rejectInventory', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan CSRF token
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry rejected successfully.', 'success').then(() => {
                            // location.reload();
                            $('a.btn-approve, a.btn-reject').attr('disabled', true);

                            // Alihkan ke halaman approval inventory
                            window.location.href = '{{ route('showApprovalInventory') }}';
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while rejecting the inquiry.', 'error');
                    }
                });
            }
        </script>

    </main><!-- End #main -->
@endsection
