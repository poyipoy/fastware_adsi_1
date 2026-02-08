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

        .dropdown-menu {
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            z-index: 1000;
        }

        .dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background-color: #f0f0f0;
        }

        .progress-display {
            display: inline-block;
            background-color: #007bff; /* Warna biru (Bootstrap primary) */
            color: white; /* Teks hitam */
            padding: 5px 10px; /* Padding untuk membuat kotak */
            border-radius: 4px; /* Sudut membulat */
            margin-bottom: 8px; /* Jarak ke elemen di bawahnya (dropdown) */
        }

        .progress-select {
            margin-top: 8px; /* Jarak tambahan dari span di atas */
        }
    </style>


    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Preview</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><a href="{{ route('createinquiryImport') }}">Menu Inquiry Sales</a>
                    </li>
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
                            <label>Date Create :</label>
                            <div class="form-value">{{ $inquiry->created_at }}</div>
                        </div>
                    </div>
                    <form action="{{ route('updateInquiryDetailsImport', $inquiry->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 25px;">No</th>
                                        <th style="width: 50px; text-align:center;">klasifikasi</th>
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
                                        <th style="width: 50px; text-align:center;">Remark</th>
                                        <th style="width: 50px; text-align:center;">Customer</th>
                                        <th style="width: 50px; text-align:center;">Partner</th>
                                        <th style="width: 50px; text-align:center;">Progress</th>
                                        <th style="width: 50px; text-align:center;">No PO</th>
                                        <th style="width: 50px; text-align:center;">Est. Date</th>
                                        <th style="width: 50px; text-align:center;">Aksi</th>
                                    </tr>
                                </thead>



                                <tbody id="table-body">
                                    @forelse ($materials as $index => $material)
                                        <tr data-id="{{ $material->id }}"
                                            @if ($material->trashed()) style="background-color: #e0e0e0;" @endif>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $material['klasifikasi'] }}</td>
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
                                            <td>
                                                @if($inquiry->status == 3 && Auth::user()->id == 1)
                                                    @php
                                                        $bgColorClass = match($material->progress) {
                                                            'ok' => 'bg-success',
                                                            'pending' => 'bg-primary',
                                                            'cancelled' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <span class="{{ $bgColorClass }} text-white px-2 py-1 rounded d-inline-block mb-2">
                                                        {{ $material->progress ?? 'No Updated' }}
                                                    </span>
                                                    <select class="form-select form-select-sm progress-select" data-id="{{ $material->id }}" style="margin-bottom: 5px;">
                                                        <option value="" disabled {{ is_null($material->progress) ? 'selected' : '' }}>-- Select Progress --</option>
                                                        <option value="ok" {{ $material->progress == 'ok' ? 'selected' : '' }}>OK</option>
                                                        <option value="pending" {{ $material->progress == 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="cancelled" {{ $material->progress == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                    </select>
                                                @else
                                                    @php
                                                        $bgColorClass = match($material->progress) {
                                                            'ok' => 'bg-success',
                                                            'pending' => 'bg-primary',
                                                            'cancelled' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <span class="{{ $bgColorClass }} text-white px-2 py-1 rounded d-inline-block">
                                                        {{ $material->progress ?? 'No Progress' }}
                                                    </span>
                                                @endif
                                            </td>
                                            
                                            
                                                                                        
                                            <td>{{ $material['nopo'] }}</td>
                                            <td>{{ $material['est_date'] }}</td>
                                            <td>
                                                @if (
                                                    !$material->trashed() &&
                                                        $inquiry->status == 1 &&
                                                        ($material->create_by == Auth::id() || in_array(Auth::id(), [1, 2, 3, 4])))
                                                    <a href="{{ route('editimport', ['id' => $material->id]) }}"
                                                        class="btn btn-warning btn-sm">Edit</a>

                                                    @if ($material->create_by == Auth::id())
                                                        <button class="btn btn-danger btn-sm"
                                                            onclick="deleteRowPermanen({{ $material->id }})">
                                                            Delete
                                                        </button>
                                                    @else
                                                        <button class="btn btn-danger btn-sm"
                                                            onclick="deleteRow({{ $material->id }})">
                                                            Delete
                                                        </button>
                                                    @endif
                                                @elseif ($material->trashed())
                                                    <p>Sudah dihapus</p>
                                                @endif
                                            </td>



                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="22" style="text-align: center;">Data tidak ditemukan</td>
                                        </tr>
                                    @endforelse
                                </tbody>


                            </table>
                        </div>

                    </form>
                    @if ($isFromApproval)
                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm m-1">Kembali</a>
                    @else
                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm m-1">Kembali</a>
                        @if ($inquiry->status == 1)
                            @if (in_array(Auth::id(), [1, 2, 3, 4]))
                                <form onsubmit="event.preventDefault(); approveKaDept({{ $inquiry->id }});"
                                    class="d-inline">
                                    <button type="submit" class="btn btn-primary btn-sm m-1">Submit</button>
                                </form>
                            @endif
                            <a class="btn btn-success btn-custom-form m-1 btn-sm"
                            href="{{ route('formulirInquiryimport', ['id' => $inquiry->id]) }}"
                            title="Formulir Inquiry">
                            <i class="bi bi-file-earmark-arrow-up-fill"></i> Tambah
                            </a>

                        @endif
                    @endif

                    {{-- @if ($inquiry->loc_imp == 'Local')
                    <a href="{{ route('showFormSS.pdf', $inquiry->id) }}" class="btn btn-danger btn-sm m-1">
                        <i class="bi bi-file-earmark-pdf"></i> Download PDF
                    </a>
                @endif --}}

                    {{-- @if ($inquiry->status == 2)
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

                @if ($inquiry->status == 4)
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

                @if ($inquiry->status == 3)
                    <div class="d-flex justify-content-end">
                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'RANGGA FADILLAH']))
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
                @endif --}}
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group mb-3 mt-2">
                                    <label for="attachments" class="fw-bold mt-2">Upload Attachments (PDF, PNG, JPG,
                                        JPEG)</label>
                                    <input type="file" id="attachments" name="attachments[]" multiple
                                        accept=".pdf,.png,.jpg,.jpeg" class="form-control mt-2" onchange="updateFileList()">

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
                    </div>
                </div>
            </section>
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

        <script>
            function goBack() {
                window.history.back();
            }
        </script>

        <script>
            function uploadexcel() {
                let fileInput = document.getElementById('file');

                if (fileInput.files.length === 0) {
                    alert("Pilih file terlebih dahulu!");
                    return;
                }

                let formData = new FormData();
                formData.append("file", fileInput.files[0]);

                $.ajax({
                    url: "{{ route('import.purchaseimport') }}", // Pastikan rute ini benar
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        alert(response.message);
                        location.reload(); // Reload halaman setelah sukses
                    },
                    error: function(xhr) {
                        alert("Terjadi kesalahan: " + xhr.responseText);
                    }
                });
            }
        </script>


        <script>
            // function toggleEdit(event, button) {
            //     event.preventDefault(); // Mencegah form submission

            //     const row = button.closest('tr');
            //     const inputs = row.querySelectorAll('input, select');
            //     const isEditing = button.classList.contains('editing');

            //     if (isEditing) {
            //         // Jika dalam mode edit (Save ditekan), kirim data ke database
            //         const formData = new FormData();
            //         formData.append('_token', '{{ csrf_token() }}'); // Tambahkan token CSRF
            //         formData.append('id', row.dataset.id); // Ambil ID dari atribut data-id
            //         inputs.forEach(input => {
            //             formData.append(input.name, input.value);
            //             input.setAttribute('disabled', 'true'); // Kunci input kembali
            //         });

            //         // Kirim data ke server dengan AJAX
            //         fetch(`{{ route('updateInquiryDetailsImport', ['id' => $inquiry->id]) }}`, {
            //                 method: 'PUT',
            //                 body: formData
            //             })
            //             .then(response => response.json())
            //             .then(data => {
            //                 if (data.success) {
            //                     button.innerText = 'Edit'; // Ubah tombol kembali ke Edit
            //                     button.classList.remove('editing'); // Hapus status editing
            //                     Swal.fire('Success!', 'Data berhasil disimpan.', 'success');
            //                 } else {
            //                     Swal.fire('Error!', data.message || 'Gagal menyimpan perubahan.', 'error');
            //                 }
            //             })
            //             .catch(error => {
            //                 console.error('Error:', error);
            //                 Swal.fire('Error!', 'Terjadi kesalahan saat menyimpan data.', 'error');
            //             });
            //     } else {
            //         // Jika dalam mode non-editing (Edit ditekan), aktifkan input
            //         inputs.forEach(input => {
            //             if (input.name !== 'partner') { // Kunci field partner agar tidak bisa diedit
            //                 input.removeAttribute('disabled');
            //             }
            //         });

            //         button.innerText = 'Save'; // Ubah tombol ke Save
            //         button.classList.add('editing'); // Tandai sebagai sedang diedit
            //     }
            // }



            // function handleShapeChange(selectElement) {
            //     const row = selectElement.closest('tr');
            //     const thicknessInput = row.querySelector('input[name="thickness"]');
            //     const weightInput = row.querySelector('input[name="weight"]');
            //     const innerDiameterInput = row.querySelector('input[name="inner_diameter"]');
            //     const outerDiameterInput = row.querySelector('input[name="outer_diameter"]');

            //     // Reset all inputs
            //     thicknessInput.setAttribute('disabled', 'true');
            //     weightInput.setAttribute('disabled', 'true');
            //     innerDiameterInput.setAttribute('disabled', 'true');
            //     outerDiameterInput.setAttribute('disabled', 'true');

            //     // Enable inputs based on selected shape
            //     if (selectElement.value === 'Flat') {
            //         thicknessInput.removeAttribute('disabled');
            //         weightInput.removeAttribute('disabled');
            //     } else if (selectElement.value === 'Round') {
            //         outerDiameterInput.removeAttribute('disabled');
            //     } else if (selectElement.value === 'Honed Tube') {
            //         innerDiameterInput.removeAttribute('disabled');
            //         outerDiameterInput.removeAttribute('disabled');
            //     }
            // }

            // // Update dropdown listeners on page load
            // document.addEventListener('DOMContentLoaded', () => {
            //     const dropdowns = document.querySelectorAll('.jenis-dropdown');
            //     dropdowns.forEach(dropdown => {
            //         handleShapeChange(dropdown); // Set initial state
            //         dropdown.addEventListener('change', () => handleShapeChange(dropdown));
            //     });
            // });

            // document.addEventListener("DOMContentLoaded", function() {
            //     // Mengambil semua input customer
            //     const customerInputs = document.querySelectorAll('.searchable-dropdown input[type="text"]');

            //     customerInputs.forEach(input => {
            //         const dropdownId = input.getAttribute('id') + '_list';
            //         const dropdown = document.getElementById(dropdownId);
            //         const hiddenInputId = input.getAttribute('id').replace('search_customer_', 'id_customer_')
            //             .replace('search_partner_', 'id_partner_');
            //         const hiddenInput = document.getElementById(hiddenInputId);

            //         input.addEventListener("input", function() {
            //             const value = this.value.toLowerCase();
            //             const items = dropdown.querySelectorAll('.dropdown-item');

            //             items.forEach(item => {
            //                 if (item.getAttribute('data-name').toLowerCase().includes(value)) {
            //                     item.style.display = '';
            //                 } else {
            //                     item.style.display = 'none';
            //                 }
            //             });

            //             if (value) {
            //                 dropdown.style.display = 'block';
            //             } else {
            //                 dropdown.style.display = 'none';
            //             }
            //         });

            //         dropdown.addEventListener("click", function(event) {
            //             const item = event.target;
            //             if (item.classList.contains('dropdown-item')) {
            //                 input.value = item.getAttribute('data-name');
            //                 hiddenInput.value = item.getAttribute('data-value');
            //                 dropdown.style.display = 'none';
            //             }
            //         });

            //         document.addEventListener("click", function(event) {
            //             if (!dropdown.contains(event.target) && !input.contains(event.target)) {
            //                 dropdown.style.display = 'none';
            //             }
            //         });
            //     });
            // });



            function deleteRow(id) {
                if (confirm('Are you sure you want to delete this row?')) {
                    fetch(`{{ url('deleteInquiryDetailImport') }}/${id}`, {
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

            function deleteRowPermanen(id) {
                if (confirm('Are you sure you want to delete this row?')) {
                    fetch(`{{ url('deleteInquiryDetailImportpermanen') }}/${id}`, {
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

            function approveInquiry(id) {
                $.ajax({
                    url: '{{ route('approveKaSieImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success').then(() => {
                            $('a.btn-approve, a.btn-reject').attr('disabled', true);

                            // Alihkan ke halaman approval inventory
                            window.location.href = '{{ route('showApprovalKaSieImport') }}';
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }

            function rejectInquiry(id) {
                $.ajax({
                    url: '{{ route('rejectKaSieImport', '') }}/' + id, // Buat route untuk reject
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

            function approveKaDept(id) {
                $.ajax({
                    url: '{{ route('approveKaDeptImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan token CSRF
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success');
                        $('a.btn-approve, a.btn-reject').attr('disabled', true);

                        // Alihkan ke halaman approval inventory
                        window.location.href = '{{ route('createinquiryImport') }}';
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while approving the inquiry.', 'error');
                    }
                });
            }

            function rejectKaDept(id) {
                $.ajax({
                    url: '{{ route('rejectKaDeptImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry rejected successfully.', 'success');
                        $('a.btn-approve, a.btn-reject').attr('disabled', true);

                        // Alihkan ke halaman approval inventory
                        window.location.href = '{{ route('showApprovalKaDeptImport') }}';
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while rejecting the inquiry.', 'error');
                    }
                });
            }

            function approveInventory(id) {
                $.ajax({
                    url: '{{ route('approveInventoryImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan CSRF token
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success').then(() => {
                            $('a.btn-approve, a.btn-reject').attr('disabled', true);

                            // Alihkan ke halaman approval inventory
                            window.location.href = '{{ route('showApprovalInventoryImport') }}';
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
                    url: '{{ route('rejectInventoryImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan CSRF token
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry rejected successfully.', 'success').then(() => {
                            $('a.btn-approve, a.btn-reject').attr('disabled', true);

                            // Alihkan ke halaman approval inventory
                            window.location.href = '{{ route('showApprovalInventoryImport') }}';
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while rejecting the inquiry.', 'error');
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
    const selectElements = document.querySelectorAll('.progress-select');

    selectElements.forEach(select => {
        select.addEventListener('change', function () {
            const materialId = this.getAttribute('data-id');
            const newProgress = this.value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`{{ route('updateProgressImport', ['id' => ':id']) }}`.replace(':id', materialId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ progress: newProgress })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Reload halaman setelah berhasil
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update progress');
                // Kembalikan nilai select ke sebelumnya jika gagal
                const displayElement = this.parentElement.querySelector('.progress-display');
                this.value = displayElement.textContent;
            });
        });
    });
});
        </script>

    </main><!-- End #main -->
@endsection
