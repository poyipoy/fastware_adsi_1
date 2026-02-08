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
    }

    .input-group label {
        margin-bottom: 5px;
        display: block;
        font-weight: bold;
    }

    .input-group input,
    .input-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 14px;
    }

    .btn {
        padding: 8px;
        margin-left: 5px;
    }

    .searchable-dropdown {
        position: relative;
        margin: 10px 0;
    }

    #search_customer {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 5px;
        outline: none;
        box-sizing: border-box;
        margin: 0;
    }

    .dropdown-items {
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

    .dropdown-item {
        padding: 10px;
        cursor: pointer;
        white-space: nowrap;
    }

    .dropdown-item:hover {
        background-color: #f0f0f0;
    }

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
        padding: 1rem;
        background-color: #f7f7f7;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }

    .table-1 th {
        background-color: rgb(97, 97, 97);
        color: #ffffff;
        font-size: 10pt;
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
        font-size: 0.8rem;
    }

    .dataTable-pagination .dataTable-info,
    .dataTable-pagination .dataTable-pagination-button {
        margin: 0;
    }

    .datatable-dropdown {
        font-family: 'Cambria', serif;
        font-size: 0.8rem;
    }

    .datatable-selector {
        padding: 0.2rem;
        font-size: 0.8rem;
        border-radius: 4px;
        border: 1px solid #ddd;
        font-family: 'Cambria', serif;
    }

    input[type="search"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-bottom: 0.5rem;
        transition: border-color 0.3s;
        font-family: 'Cambria', serif;
    }

    input[type="search"] {
        padding: 0.3rem;
        font-size: 0.8rem;
        border-radius: 10px;
        border: 1px solid #ddd;
    }

    .dataTable-search {
        margin-bottom: 0.5rem;
        font-family: 'Cambria', serif;
    }

    .btn-custom-draft {
        background-color: #6c757d;
        color: white;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-open {
        background-color: #00db37;
        color: rgb(0, 0, 0);
        border: none;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-approve-dept {
        background-color: #00cfeb;
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
        color: rgb(0, 0, 0);
        border: none;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-in-progress {
        background-color: #fbff07;
        color: rgb(0, 0, 0);
        border: none;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-finished {
        background-color: #00346b;
        color: white;
        border: none;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-rejected {
        background-color: #dc3545;
        color: white;
        border: none;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-inventory {
        background-color: #00d39e;
        color: #000000;
        border: none;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-inventory:hover {
        background-color: #00ffbf;
        color: #ffffff;
    }

    .btn-custom-form {
        background-color: #4df300;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-show {
        background-color: #f300a2;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-edit {
        background-color: #3564ff;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-view {
        background-color: #fffb00;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-delete {
        background-color: #ff0000;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-form:hover {
        background-color: #34a500;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-show:hover {
        background-color: #b10076;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-edit:hover {
        background-color: #0026a3;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-view:hover {
        background-color: #ffd000;
        font-size: 8pt;
        font-family: 'Cambria', serif;
        font-weight: bold;
    }

    .btn-custom-delete:hover {
        background-color: #be0000;
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

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Halaman Inquiry</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item active"><a href="{{ route('createinquiryImport') }}">Menu Inquiry Sales</a></li>
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
                    {{-- <div class="form-group">
                        <label>Supplier :</label>
                        <div class="form-value">{{ $inquiry->supplier }}</div>
                    </div> --}}
                    <div class="form-group">
                        <label>Date Create :</label>
                        <div class="form-value">{{ $inquiry->created_at }}</div>
                    </div>
                </div>

                <script>
                    var typeMaterials = {!! json_encode($typeMaterials) !!};
                    var customers = {!! json_encode($customers) !!};
                    console.log(typeMaterials); // Untuk memastikan data benar
                </script>
                <form id="updateForm" method="POST" action="{{ route('inquiry.update', $inquiry->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 25px;">No</th>
                                    <th style="width: 100px;">Raw Material</th>
                                    <th style="width: 50px;">Shapes</th>
                                    <th style="width: 30px;">Thickness</th>
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
                                    <th style="width: 50px;">Remark</th>
                                    <th style="width: 300px;">Customer</th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                @foreach ($materials as $index => $material)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{-- Hidden input untuk mengidentifikasi baris yang akan diupdate --}}
                                            <input type="hidden" name="materials[{{ $index }}][id]" value="{{ $material->id }}">
                                        
                                            {{-- Dropdown untuk memilih tipe material --}}
                                            <select name="materials[{{ $index }}][id_type]" class="material-dropdown" style="width: 180px; height: 30px;">
                                                <option value="" disabled {{ $material->id_type ? '' : 'selected' }}>Cari Material...</option>
                                                @foreach ($typeMaterials as $type)
                                                    <option value="{{ $type->id }}" {{ $type->id == $material->id_type ? 'selected' : '' }}>
                                                        {{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        
                                        <td>
                                            <select name="materials[{{ $index }}][jenis]" class="jenis-dropdown" style="width: 80px; height: 30px;">
                                                <option value="Flat" {{ $material->jenis == 'Flat' ? 'selected' : '' }}>Flat</option>
                                                <option value="Round" {{ $material->jenis == 'Round' ? 'selected' : '' }}>Round</option>
                                                <option value="Honed Tube" {{ $material->jenis == 'Honed Tube' ? 'selected' : '' }}>Honed Tube</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="materials[{{ $index }}][thickness]" size="5" value="{{ $material->thickness }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][weight]" size="5" value="{{ $material->weight }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][inner_diameter]" size="5" value="{{ $material->inner_diameter }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][outer_diameter]" size="5" value="{{ $material->outer_diameter }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][length]" size="5" value="{{ $material->length }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][qty]" size="5" required value="{{ $material->qty }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][m1]" size="5" required value="{{ $material->m1 }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][m2]" size="5" value="{{ $material->m2 }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][m3]" size="5" value="{{ $material->m3 }}"></td>
                                        <td>
                                            <select name="materials[{{ $index }}][ship]" class="jenis-dropdown" style="width: 100px; height: 30px;">
                                                <option value="Deltamas" {{ $material->ship == 'Deltamas' ? 'selected' : '' }}>Deltamas</option>
                                                <option value="DS8" {{ $material->ship == 'DS8' ? 'selected' : '' }}>DS8</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="materials[{{ $index }}][so]" size="10" required value="{{ $material->so }}"></td>
                                        <td><input type="text" name="materials[{{ $index }}][note]" size="10" required value="{{ $material->note }}"></td>
                                        <td>
                                            <div class="searchable-dropdown">
                                                @php
                                                    $selectedCustomerIds = json_decode($material->customer ?? '[]');
                                                    $selectedCustomers = $customers->whereIn('id', $selectedCustomerIds);
                                                @endphp
                                            
                                                <!-- Menampilkan nama-nama customer yang dipilih -->
                                                <div class="selected-customers-list" id="selected_customers_{{ $index }}">
                                                    {{-- @foreach ($selectedCustomers as $cust)
                                                        <span class="selected-customer" data-id="{{ $cust->id }}">{{ $cust->name_customer }}</span>
                                                    @endforeach --}}
                                                </div>
                                            
                                                <!-- Input pencarian -->
                                                <input type="text" id="search_customer_{{ $index }}" class="form-control search-input" placeholder="Search customer...">
                                            
                                                <!-- Daftar pilihan customer -->
                                                <div id="customer_list_{{ $index }}" class="dropdown-menu show" style="width: 300px; display: none; max-height: 200px; overflow-y: auto;">
                                                    @foreach ($customers as $customer)
                                                        <div class="dropdown-item customer-option" data-value="{{ $customer->id }}" data-name="{{ $customer->name_customer }}"
                                                            @if(in_array($customer->id, $selectedCustomerIds)) style="background-color: #007bff; color: white;" @endif>
                                                            {{ $customer->name_customer }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            
                                                <!-- Input tersembunyi untuk mengirimkan array ID -->
                                                <input type="hidden" name="materials[{{ $index }}][customer]" id="customer_input_{{ $index }}" value="{{ $material->customer }}">
                                            </div>
                                            
                                            
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('showFormSSimport', $inquiry->id) }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </section>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.nav-item.dropdown').hover(function() {
                $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
            }, function() {
                $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
            });
        });
    </script>

    <div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
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
                            <input type="file" id="uploadFile" name="file" class="form-control" accept="application/pdf,image/*" required>
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

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function openUploadFileModal(id) {
            document.getElementById('inquiryIdForFile').value = id;
            var myModal = new bootstrap.Modal(document.getElementById('uploadFileModal'), {});
            myModal.show();
        }

        function submitUploadFileForm() {
            const form = document.getElementById('uploadFileForm');
            const formData = new FormData(form);

            $.ajax({
                url: '{{ route('uploadFile') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire('Error!', xhr.responseJSON.error, 'error');
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
    let dropdowns = document.querySelectorAll('.searchable-dropdown');

    dropdowns.forEach(dropdown => {
        const searchInput = dropdown.querySelector('.search-input');
        const customerList = dropdown.querySelector('.dropdown-menu');
        const selectedList = dropdown.querySelector('.selected-customers-list');
        const hiddenInput = dropdown.querySelector('input[type="hidden"]');

        let selectedCustomers = [];

        // Inisialisasi customer dari value yang sudah tersimpan
        try {
            const initialIds = JSON.parse(hiddenInput.value || "[]");

            initialIds.forEach(id => {
                const option = customerList.querySelector(`.customer-option[data-value="${id}"]`);
                if (option) {
                    const name = option.getAttribute('data-name');
                    selectedCustomers.push({ id, name });
                    addCustomerBadge(id, name);
                }
            });

        } catch (e) {
            console.warn("Invalid JSON in hidden input:", hiddenInput.value);
        }

        updateHiddenInput(); // pastikan tersinkron

        // Event pencarian
        searchInput.addEventListener("focus", () => {
            customerList.style.display = "block";
        });

        searchInput.addEventListener("input", () => {
            const query = searchInput.value.toLowerCase();
            Array.from(customerList.children).forEach(item => {
                item.style.display = item.textContent.toLowerCase().includes(query) ? "block" : "none";
            });
        });

        // Tutup dropdown jika klik di luar
        document.addEventListener("click", (e) => {
            if (!dropdown.contains(e.target)) {
                customerList.style.display = "none";
            }
        });

        // Pilih customer dari list
        Array.from(customerList.children).forEach(item => {
            item.addEventListener("click", () => {
                const id = item.dataset.value;
                const name = item.dataset.name;

                // Tambah hanya jika belum ada
                if (!selectedCustomers.some(c => c.id === id)) {
                    selectedCustomers.push({ id, name });
                    addCustomerBadge(id, name);
                    updateHiddenInput();
                }

                searchInput.value = "";
                customerList.style.display = "none";
            });
        });

        function addCustomerBadge(id, name) {
            if (selectedList.querySelector(`[data-id="${id}"]`)) return;

            const badge = document.createElement("span");
            badge.className = "badge bg-primary me-1 selected-customer d-inline-flex align-items-center";
            badge.dataset.id = id;
            badge.textContent = name;

            const removeBtn = document.createElement("button");
            removeBtn.textContent = "×";
            removeBtn.type = "button";
            removeBtn.classList.add("btn-close", "btn-close-white", "ms-2", "btn-sm");
            removeBtn.setAttribute("aria-label", "Remove");

            removeBtn.addEventListener("click", () => {
                selectedCustomers = selectedCustomers.filter(c => c.id !== id);
                badge.remove();
                updateHiddenInput();
            });

            badge.appendChild(removeBtn);
            selectedList.appendChild(badge);
        }

        function updateHiddenInput() {
            hiddenInput.value = JSON.stringify(selectedCustomers.map(c => c.id));
        }
    });
});




        function updateDropdownListeners() {
            let dropdowns = document.querySelectorAll('.jenis-dropdown');
            dropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('change', function() {
                    let row = dropdown.closest('tr');
                    let thickness = row.querySelector('input[name$="[thickness]"]');
                    let weight = row.querySelector('input[name$="[weight]"]');
                    let innerDiameter = row.querySelector('input[name$="[inner_diameter]"]');
                    let outerDiameter = row.querySelector('input[name$="[outer_diameter]"]');

                    switch (dropdown.value) {
                        case 'Flat':
                            thickness.disabled = false;
                            weight.disabled = false;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = true;
                            break;
                        case 'Round':
                            thickness.disabled = true;
                            weight.disabled = true;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = false;
                            break;
                        case 'Honed Tube':
                            thickness.disabled = true;
                            weight.disabled = true;
                            innerDiameter.disabled = false;
                            outerDiameter.disabled = false;
                            break;
                        default:
                            thickness.disabled = false;
                            weight.disabled = false;
                            innerDiameter.disabled = true;
                            outerDiameter.disabled = true;
                    }
                });

                dropdown.dispatchEvent(new Event('change'));
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateDropdownListeners();
        });
    </script>
</main>
@endsection
