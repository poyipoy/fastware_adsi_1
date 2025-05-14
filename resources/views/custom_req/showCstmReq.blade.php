@extends('layout')

@section('content')

    <main id="main" class="main">

        <style>
            .card-title1 {
                text-align: center;
                width: 100%;
            }

            .swal2-popup {
                font-size: 0.6rem;
                width: 300px;
            }

            .modal-content {
                font-family: 'Cambria', serif;
                width: 400px;
                max-width: 90%;
                color: #000000;
                background-color: rgb(114, 114, 114);
            }

            .modal-title {
                font-family: 'Cambria', serif;
                font-weight: bold;
                font-size: 20px;
                color: #ecf000;
            }

            .input-group {
                margin-bottom: 15px;
                /* Jarak antar input */
            }

            .input-group label {
                margin-bottom: 5px;
                display: block;
                /* Memisahkan label dari input */
                font-weight: bold;
                /* Mempertegas label */
            }

            .input-group input,
            .input-group select {
                width: 100%;
                /* Lebar penuh untuk semua input */
                padding: 10px;
                /* Padding seragam */
                border: 1px solid #ccc;
                /* Border seragam */
                border-radius: 4px;
                /* Sudut border seragam */
                box-sizing: border-box;
                /* Memastikan padding masuk ke dalam lebar */
                font-size: 14px;
                /* Ukuran font seragam */
            }

            .btn {
                padding: 8px;
                /* Sesuaikan ukuran tombol */
                margin-left: 5px;
                /* Jarak antara input dan tombol */
            }

            /* Dropdown Input Styling */
            .searchable-dropdown {
                position: relative;
                margin: 10px 0;
            }

            #search_customer {
                width: 100%;
                padding: 8px;
                /* Mengurangi padding */
                border: 1px solid #ccc;
                border-radius: 5px;
                outline: none;
                box-sizing: border-box;
                /* Pastikan padding dihitung dalam lebar */
                margin: 0;
                /* Pastikan margin adalah 0 */
            }

            /* Dropdown Items Styling */
            .dropdown-items {
                /* position: absolute; */
                top: 100%;
                left: 0;
                right: 0;
                z-index: 1000;
                background-color: white;
                border: 1px solid #ccc;
                border-radius: 5px;
                max-height: 200px;
                overflow-y: auto;
                display: none;
                padding: 10px;
            }

            /* Style for each item */
            .dropdown-item {
                padding: 10px;
                cursor: pointer;
                white-space: nowrap;
            }

            .dropdown-item:hover {
                background-color: #f0f0f0;
            }

            /* Style for selected customers */
            .selected-customer {
                display: inline-block;
                margin: 5px;
                padding: 5px 8px;
                background-color: #e0e0e0;
                border-radius: 5px;
            }

            .font-sii {
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .table-1 {
                margin: 5px auto;
                /* Pusatkan tabel */
                padding: 1rem;
                /* Padding di sekeliling tabel */
                background-color: #f7f7f7;
                /* Warna latar belakang */
                border-radius: 8px;
                /* Sudut membulat */
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
                /* Bayangan untuk efek kedalaman */
            }

            .table-1 th {
                background-color: rgb(97, 97, 97);
                /* Warna latar belakang */
                color: #ffffff;
                font-size: 10pt;
                /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); */
                /* Bayangan untuk efek kedalaman */
                text-align: center;
                font-family: 'Cambria', serif;
            }

            .table-1 td {
                font-size: 8pt;
                font-family: 'Cambria', serif;
            }

            .datatable-table>tbody>tr>td {
                text-align: center;
            }


            .dataTable-pagination {
                padding: 0.25rem;
                /* Padding lebih kecil untuk pagination */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
            }

            .dataTable-pagination .dataTable-info,
            .dataTable-pagination .dataTable-pagination-button {
                margin: 0;
                /* Hapus margin untuk elemen info dan tombol pagination */
            }

            .datatable-dropdown {
                font-family: 'Cambria', serif;
                font-size: 0.8rem;
            }

            .datatable-selector {
                padding: 0.2rem;
                /* Padding lebih kecil pada dropdown pagination */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
                border-radius: 4px;
                /* Sudut membulat */
                border: 1px solid #ddd;
                /* Border untuk dropdown */
                font-family: 'Cambria', serif;
            }

            input[type="search"] {
                width: 100%;
                /* Lebar input pencarian */
                padding: 0.5rem;
                /* Padding untuk input */
                border: 1px solid #ddd;
                /* Border untuk input */
                border-radius: 10px;
                /* Sudut membulat untuk input */
                margin-bottom: 0.5rem;
                /* Jarak antara input dan tabel */
                transition: border-color 0.3s;
                /* Transisi saat berinteraksi */
                font-family: 'Cambria', serif;
            }

            input[type="search"] {
                padding: 0.3rem;
                /* Padding lebih kecil untuk input pencarian */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
                border-radius: 10px;
                /* Sudut membulat */
                border: 1px solid #ddd;
                /* Border untuk input */
            }

            .dataTable-search {
                margin-bottom: 0.5rem;
                /* Jarak antara input pencarian dan tabel */
                font-family: 'Cambria', serif;
            }

            .btn-custom-draft {
                background-color: #6c757d;
                /* atau warna lain yang Anda inginkan */
                color: white;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-open {
                background-color: #00db37;
                /* atau warna lain */
                color: rgb(0, 0, 0);
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-approve-dept {
                background-color: #00cfeb;
                /* Warna kuning bisa jadi untuk approve ka.dept */
                color: black;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-approve-dept:hover {
                background-color: #14b4c9;
                color: #ffffff;
            }

            .btn-custom-approve-sie {
                background-color: #00ffff;
                /* Warna biru bisa untuk approve ka.sie */
                color: rgb(0, 0, 0);
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-in-progress {
                background-color: #fbff07;
                /* Warna kuning tua untuk on progress */
                color: rgb(0, 0, 0);
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-finished {
                background-color: #00346b;
                /* Warna biru untuk finished */
                color: white;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-rejected {
                background-color: #dc3545;
                /* Merah untuk rejected */
                color: white;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-inventory {
                background-color: #00d39e;
                /* Merah untuk show form */
                color: #000000;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-inventory:hover {
                background-color: #00ffbf;
                /* Merah untuk show form */
            }

            .btn-custom-form {
                background-color: #4df300;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-show {
                background-color: #f300a2;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-edit {
                background-color: #3564ff;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-view {
                background-color: #fffb00;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-delete {
                background-color: #ff0000;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-form:hover {
                background-color: #34a500;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-show:hover {
                background-color: #b10076;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-edit:hover {
                background-color: #0026a3;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-view:hover {
                background-color: #ffd000;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-delete:hover {
                background-color: #be0000;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-stts {
                text-align: center;
            }

            .btn-add {
                font-size: 8pt;
                background-color: #0033da;
                color: #ffffff;
            }

            .btn-add:hover {
                background-color: #0026a3;
                color: #fbff00;
            }

            .eempty {
                font-family: 'Cambria', serif;
                border: 1px solid #220000;
                border-radius: 10px;
                color: #be0000;
                font-style: italic;
            }

            .disabledform {
                font-size: 8pt;
                color: red;
            }
        </style>

        <section class="">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-item-center justify-content-start mt-4 mb-3">
                        <button class="btn btn-add btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                            <i class="bi bi-plus-circle"> Add Request Custom</i>
                        </button>
                    </div>
                    @if($materials->isEmpty())
                        <div class="alert alert-danger text-center" role="alert">
                            <strong>---Data Not Found---</strong>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-1" id="customTable">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Sales</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Confirm Drawing</th>
                                        <th scope="col">Remark</th>
                                        <th scope="col">Tanggal Dibuat</th>
                                        <th scope="col">Nama project</th>
                                        <th scope="col">Update Progress</th>
                                        <th scope="col">Upload Attachment</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Harga Awal</th>
                                        <th scope="col">Harga Akhir</th>
                                        <th scope="col">Progress</th>
                                        <th scope="col">Marketing</th>
                                        <th scope="col">Finance</th>
                                        <th scope="col">SO</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($materials as $material)
                                    <tr>
                                        <th scope="row">{{ $loop->iteration }}</th>
                                        <td style="text-align: center;">{{ $material->sales }}</td>
                                        <td style="text-align: center;">{{ $material->customers->name_customer }}</td>
                                        <td style="text-align: center;">{{ $material->ket_drawing }}</td>
                                        <td style="text-align: center;">{{ $material->remark }}</td>
                                        <td style="text-align: center;">{{ $material->created_at }}</td>
                                        <td style="text-align: center;">{{ $material->nama_project }}</td>
                                        <td style="text-align: center;">{{ $material->tgl_update }}</td>
                                        <td style="text-align: center;">{{ $material->Attachment }}</td>
                                        @php
                                            $statusDescriptions = [
                                                1 => 'Draft',
                                                2 => 'Open',
                                                3 => 'Open',
                                                4 => 'Approve Marketing',
                                                5 => 'Approve Finance',
                                                6 => 'Open',
                                                7 => 'Open',
                                                8 => 'Rejected',
                                                9 => 'finished',
                                            ];

                                            // Mendefinisikan kelas tombol berdasarkan status
                                            $buttonClasses = [
                                                1 => 'btn-secondary', // Draft
                                                2 => 'btn-warning', // Open
                                                3 => 'btn-warning', // Approve Ka.Dept
                                                4 => 'btn-info', // Approve Ka.Sie
                                                5 => 'btn-warning', // On Progress
                                                6 => 'btn-warning', // Finished
                                                7 => 'btn-danger', // Rejected
                                                8 => 'btn-success', // Approve Inventory
                                                9 => 'btn-primary', // Confirm Purchasing
                                            ];
                                        @endphp
                                        <td class="btn-stts" >
                                            <button class="btn btn-sm
                                                {{ $buttonClasses[$material->status] ?? 'btn-light' }}
                                                {{ $material->status == 1 ? 'btn-custom-draft' : '' }}
                                                {{ $material->status == 2 ? 'btn-custom-open' : '' }}
                                                {{ $material->status == 3 ? 'btn-custom-open' : '' }}
                                                {{ $material->status == 4 ? 'btn-custom-open' : '' }}
                                                {{ $material->status == 5 ? 'btn-custom-open' : '' }}
                                                {{ $material->status == 6 ? 'btn-custom-marketing' : '' }}
                                                {{ $material->status == 7 ? 'btn-custom-finance' : '' }}
                                                {{ $material->status == 8 ? 'btn-custom-rejected' : '' }}
                                                {{ $material->status == 9 ? 'btn-custom-finished' : '' }}">
                                                {{ $statusDescriptions[$material->status] ?? 'Unknown' }}
                                            </button>
                                        </td>
                                        <td style="text-align: center;">{{ $material->harga_awal }}</td>
                                        <td style="text-align: center;">{{ $material->harga_akhir }}</td>
                                        <td style="text-align: center;">{{ $material->progress }}</td>
                                        <td style="text-align: center;">{{ $material->marketing ? $material->marketing->name : '' }}
                                        </td>
                                        <td style="text-align: center;">{{ $material->finance ? $material->finance->name : '' }}
                                        </td>
                                        <td style="text-align: center;">{{ $material->ref_so }}</td>
                                        <td style="text-align: center;">
                                            <button class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editMaterialModal{{ $material->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        
                                            <form action="{{ route('CustomRequest.delete', $material->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>                                        
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </section>


        <!-- Modal Tambah Material -->
        <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form action="{{ route('CustomRequest.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Form Request Quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <!-- Customer Dropdown Search -->
                    {{-- Pada modal tambah --}}
                    <div class="searchable-dropdown" data-modal-type="create">
                        <label class="form-label fw-bold">Customer</label>
                        <input type="text" id="search_customer_create" class="form-control" placeholder="Cari customer...">
                        <div class="dropdown-items border rounded p-2" id="customer_list_create" style="display: none; max-height: 200px; overflow-y: auto;">
                            @foreach ($customers as $customer)
                                <div data-value="{{ $customer->id }}">{{ $customer->name_customer }}</div>
                            @endforeach
                        </div>
                        <input type="hidden" id="customer_create" name="customer" required>
                        <div id="selected_customers_list_create" class="mt-2"></div>
                    </div>
          
                    <!-- Keterangan Drawing -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan Drawing</label>
                        <select name="ket_drawing" class="form-control" required>
                        <option value="" disabled selected>Pilihlah salah satu</option>
                        <option value="via email">Via Email</option>
                        <option value="via whatsapp">Via Whatsapp</option>
                        </select>
                    </div>
          
                    <!-- Remark -->
                    <div class="mb-3">
                      <label class="form-label fw-bold">Remark</label>
                      <textarea name="remark" class="form-control" rows="3"></textarea>
                    </div>
          
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                  </div>
                </div>
              </form>
            </div>
        </div>
        
          

        <!-- End Modal Edit Material -->
        @php
    $userId = auth()->user()->id;
@endphp

@foreach($materials as $material)
<div class="modal fade" id="editMaterialModal{{ $material->id }}" tabindex="-1" aria-labelledby="editMaterialModalLabel{{ $material->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('CustomRequest.update', $material->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Form Request Quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    @if(in_array($userId, [1, 2, 5]))
                        <div class="searchable-dropdown" data-modal-type="edit-{{ $material->id }}">
                            <label class="form-label">Customer</label>
                        
                            @php
                                $selectedCustomer = $customers->firstWhere('id', $material->customer);
                            @endphp
                        
                            <input
                                type="text"
                                id="search_customer_edit_{{ $material->id }}"
                                class="form-control"
                                placeholder="Cari customer..."
                                value="{{ $selectedCustomer ? $selectedCustomer->name_customer : '' }}">
                        
                            <div class="dropdown-items border rounded p-2"
                                 id="customer_list_edit_{{ $material->id }}"
                                 style="display: none; max-height: 200px; overflow-y: auto;">
                                @foreach ($customers as $customer)
                                    <div data-value="{{ $customer->id }}">{{ $customer->name_customer }}</div>
                                @endforeach
                            </div>
                        
                            <input type="hidden"
                                   id="customer_edit_{{ $material->id }}"
                                   name="customer"
                                   value="{{ $selectedCustomer ? $selectedCustomer->id : '' }}"
                                   required>
                        
                            <div id="selected_customers_list_edit_{{ $material->id }}" class="mt-2">
                                @if ($selectedCustomer)
                                    <div class="selected-customer badge bg-primary p-2">
                                        {{ $selectedCustomer->name_customer }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Ket Drawing --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan Drawing</label>
                            <select name="ket_drawing" class="form-control" required>
                                <option value="via email" {{ $material->ket_drawing == 'via email' ? 'selected' : '' }}>Via Email</option>
                                <option value="via whatsapp" {{ $material->ket_drawing == 'via whatsapp' ? 'selected' : '' }}>Via Whatsapp</option>
                            </select>
                        </div>

                        {{-- Remark --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Remark</label>
                            <textarea name="remark" class="form-control" rows="3">{{ $material->remark }}</textarea>
                        </div>
                    @endif

                    @if(in_array($userId, [1, 4, 5]))
                        {{-- Nama Project --}}
                        <div class="mb-3">
                            <label class="form-label">Nama Project</label>
                            <input type="text" name="nama_project" class="form-control" value="{{ $material->nama_project }}">
                        </div>

                        {{-- Tanggal Update --}}
                        <div class="mb-3">
                            <label class="form-label">Tanggal Update</label>
                            <input type="date" name="tgl_update" class="form-control" value="{{ $material->tgl_update }}">
                        </div>

                        {{-- Attachment --}}
                        <div class="mb-3">
                            <label class="form-label">Attachment (Opsional)</label>
                            <input type="file" name="attachment" class="form-control">
                            @if ($material->attachment)
                                <small class="text-muted">File saat ini: {{ $material->attachment }}</small>
                            @endif
                        </div>

                        {{-- Harga Awal --}}
                        <div class="mb-3">
                            <label class="form-label">Harga Awal</label>
                            <input type="number" name="harga_awal" class="form-control" value="{{ $material->harga_awal }}">
                        </div>

                        {{-- Harga Akhir --}}
                        <div class="mb-3">
                            <label class="form-label">Harga Akhir</label>
                            <input type="number" name="harga_akhir" class="form-control" value="{{ $material->harga_akhir }}">
                        </div>

                        {{-- Progress --}}
                        <div class="mb-3">
                            <label class="form-label">Progress</label>
                            <input type="text" name="progress" class="form-control" value="{{ $material->progress }}">
                        </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach


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
            function initSearchableDropdown(searchInputId, listId, hiddenInputId, selectedListId) {
            const searchInput = document.getElementById(searchInputId);
            const customerList = document.getElementById(listId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const selectedCustomersList = document.getElementById(selectedListId);

            if (!searchInput || !customerList || !hiddenInput) return;

            searchInput.addEventListener('input', function () {
                const filter = searchInput.value.toLowerCase();
                const items = customerList.getElementsByTagName('div');

                for (let i = 0; i < items.length; i++) {
                    const txtValue = items[i].textContent || items[i].innerText;
                    items[i].style.display = txtValue.toLowerCase().includes(filter) ? '' : 'none';
                }
            });

            customerList.addEventListener('click', function (e) {
                if (e.target && e.target.matches('div[data-value]')) {
                    const selectedValue = e.target.getAttribute('data-value');
                    const selectedText = e.target.textContent;

                    searchInput.value = selectedText;
                    hiddenInput.value = selectedValue;
                    customerList.style.display = 'none';
                    selectedCustomersList.innerHTML = '<span class="selected-customer badge bg-success p-2">' + selectedText + '</span>';
                }
            });

            searchInput.addEventListener('focus', function () {
                customerList.style.display = 'block';
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#' + searchInputId) && !e.target.closest('#' + listId)) {
                    customerList.style.display = 'none';
                }
            });
        }

        // Inisialisasi untuk modal tambah
        document.addEventListener('DOMContentLoaded', function () {
            initSearchableDropdown('search_customer_create', 'customer_list_create', 'customer_create', 'selected_customers_list_create');

            @foreach ($materials as $material)
                initSearchableDropdown(
                    'search_customer_edit_{{ $material->id }}',
                    'customer_list_edit_{{ $material->id }}',
                    'customer_edit_{{ $material->id }}',
                    'selected_customers_list_edit_{{ $material->id }}'
                );
            @endforeach
        });
            </script>

            <script>
                const soInput = document.getElementById('so');
                const submitBtn = document.getElementById('submitBtn');
                const soError = document.getElementById('so-error');
            
                soInput.addEventListener('input', function () {
                const soVal = soInput.value.trim();
                const isValidSO = /^\d{4}$/.test(soVal);
            
                if (isValidSO) {
                    submitBtn.disabled = false;
                    soError.style.display = 'none';
                } else {
                    submitBtn.disabled = true;
                    soError.style.display = 'block';
                }
                });
            </script>
  
  
            
          
    </main>
@endsection