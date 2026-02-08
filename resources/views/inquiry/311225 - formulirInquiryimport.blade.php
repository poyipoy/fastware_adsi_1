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

        .selected-customer {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 2px 8px;
        margin: 2px;
        border-radius: 4px;
        font-size: 0.9em;
    }

    </style>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Halaman Inquiry</h1>
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
                        var customers = {!! json_encode($customers) !!};
                        console.log(typeMaterials); // Untuk memastikan data benar
                    </script>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 25px;"><input type="checkbox" onclick="toggle(this);"></th>
                                    <th style="width: 25px;">No</th>
                                    <th style="width: 150px;">Klasifikasi</th>
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
    {{-- excel --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

    <!-- SimpleDataTables JS -->
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

        

            function addRow() {
                let tableBody = document.getElementById('table-body');
                let rowCount = tableBody.rows.length;
                let newRow = tableBody.insertRow(rowCount);

                // Membuat array untuk menyimpan cell
                let cells = [];
                for (let i = 0; i < 18; i++) {
                    cells[i] = newRow.insertCell(i);
                }

                // Kolom 1: Checkbox
                let checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.name = "record";
                cells[0].appendChild(checkbox);

                

                // Kolom 2: Nomor Urut
                cells[1].textContent = rowCount + 1;

                // Kolom 18: Dropdown Klasifikasi
                let klasifikasiDropdown = document.createElement("select");
                klasifikasiDropdown.name = "klasifikasi";
                klasifikasiDropdown.classList.add("klasifikasi-dropdown");
                klasifikasiDropdown.style.width = "150px";
                klasifikasiDropdown.style.height = "30px";

                ["Daido", "NonDaido"].forEach(value => {
                    let option = document.createElement("option");
                    option.value = value;
                    option.textContent = value;
                    klasifikasiDropdown.appendChild(option);
                });

                cells[2].appendChild(klasifikasiDropdown);

                // Kolom 3: Dropdown Material Type
                let idTypeDropdown = document.createElement("select");
                idTypeDropdown.name = "id_type";
                idTypeDropdown.classList.add("material-dropdown");
                idTypeDropdown.style.width = "180px";
                idTypeDropdown.style.height = "30px";

                let defaultOption = document.createElement("option");
                defaultOption.value = "";
                defaultOption.disabled = true;
                defaultOption.selected = true;
                defaultOption.textContent = "Cari Material...";
                idTypeDropdown.appendChild(defaultOption);

                typeMaterials.forEach(type => {
                    let option = document.createElement("option");
                    option.value = type.id;
                    option.textContent = type.type_name;
                    idTypeDropdown.appendChild(option);
                });

                cells[3].appendChild(idTypeDropdown);

                // Kolom 4: Dropdown Jenis
                let jenisDropdown = document.createElement("select");
                jenisDropdown.name = "jenis";
                jenisDropdown.classList.add("jenis-dropdown");
                jenisDropdown.style.width = "80px";
                jenisDropdown.style.height = "30px";

                ["Flat", "Round", "Honed Tube"].forEach(value => {
                    let option = document.createElement("option");
                    option.value = value;
                    option.textContent = value;
                    jenisDropdown.appendChild(option);
                });

                cells[4].appendChild(jenisDropdown);

                // Kolom 5-13: Input Text
                const inputFields = ["thickness", "weight", "inner_diameter", "outer_diameter", "length", "qty", "m1", "m2",
                    "m3"
                ];
                inputFields.forEach((name, index) => {
                    let input = document.createElement("input");
                    input.type = "text";
                    input.name = name;
                    input.size = 5;

                    if (name === "qty" || name === "m1") {
                        input.required = true;
                    }

                    cells[index + 5].appendChild(input);
                });

                // Kolom 14: Dropdown Shipping
                let shipDropdown = document.createElement("select");
                shipDropdown.name = "ship";
                shipDropdown.classList.add("jenis-dropdown");
                shipDropdown.style.width = "100px";
                shipDropdown.style.height = "30px";

                ["Cikarang", "Surabaya"].forEach(value => {
                    let option = document.createElement("option");
                    option.value = value;
                    option.textContent = value;
                    shipDropdown.appendChild(option);
                });

                cells[14].appendChild(shipDropdown);

                let soInput = document.createElement("input");
                soInput.type = "text";
                soInput.name = "so";
                soInput.size = 10;
                soInput.required = true;
                soInput.maxLength = 5;
                soInput.pattern = "\\d{5}"; // Hanya menerima 4 digit angka

                // Mencegah input kurang atau lebih dari 4 angka
                soInput.addEventListener("input", function() {
                    this.value = this.value.replace(/\D/g, ""); // Hanya angka
                    if (this.value.length > 5) {
                        this.value = this.value.slice(0, 5); // Potong jika lebih dari 4
                    }
                });

                cells[15].appendChild(soInput);


                // Kolom 16: Note
                let noteInput = document.createElement("input");
                noteInput.type = "text";
                noteInput.name = "note";
                noteInput.size = 10;
                noteInput.required = true;
                cells[16].appendChild(noteInput);

                // Kolom 17: Searchable Dropdown Customer
                let uniqueId = `search_customer_${rowCount}`;
                let uniqueHiddenId = `customer_${rowCount}`;
                let uniqueNameCustomerId = `name_customer_${rowCount}`;

                let searchContainer = document.createElement("div");
                searchContainer.classList.add("searchable-dropdown");

                let searchInput = document.createElement("input");
                searchInput.type = "text";
                searchInput.classList.add("form-control");
                searchInput.id = uniqueId;
                searchInput.style.width = "300px";
                searchInput.placeholder = "Search customer...";

                let dropdownMenu = document.createElement("div");
                dropdownMenu.id = `customer_list_${rowCount}`;
                dropdownMenu.classList.add("dropdown-menu", "show");
                dropdownMenu.style.width = "300px";
                dropdownMenu.style.display = "none";
                dropdownMenu.style.maxHeight = "100px";
                dropdownMenu.style.overflowY = "auto";

                // Menambahkan customer list
                customers.forEach(customer => {
                    let div = document.createElement("div");
                    div.classList.add("dropdown-item");
                    div.dataset.value = customer.id;
                    div.dataset.name = customer.name_customer; // Menambahkan data-name untuk nama customer
                    div.textContent = customer.name_customer;
                    dropdownMenu.appendChild(div);
                });

                let hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.id = uniqueHiddenId;
                hiddenInput.name = "customer";

                let nameCustomerInput = document.createElement("input");
                nameCustomerInput.type = "hidden";
                nameCustomerInput.id = uniqueNameCustomerId;
                nameCustomerInput.name = "name_customer"; // Menambahkan input tersembunyi untuk nama customer

                let selectedCustomersList = document.createElement("div");
                selectedCustomersList.classList.add("selected-customers-list");

                searchContainer.appendChild(searchInput);
                searchContainer.appendChild(dropdownMenu);
                searchContainer.appendChild(hiddenInput);
                searchContainer.appendChild(nameCustomerInput); // Menambahkan input tersembunyi untuk nama customer
                searchContainer.appendChild(selectedCustomersList);
                cells[17].appendChild(searchContainer);

                // Inisialisasi event listener untuk searchable dropdown
                initializeSearchableDropdown(searchInput, dropdownMenu, hiddenInput, nameCustomerInput, selectedCustomersList);

                

                // Memastikan event listener diperbarui
                updateDropdownListeners();

                

            }

            // Fungsi untuk mengaktifkan pencarian dalam searchable dropdown
            function initializeSearchableDropdown(input, menu, hiddenInput, nameInput, selectedList) {
                input.addEventListener("focus", () => {
                    menu.style.display = "block";
                });

                input.addEventListener("input", () => {
                    const query = input.value.toLowerCase();
                    Array.from(menu.children).forEach(item => {
                        item.style.display = item.textContent.toLowerCase().includes(query) ? "block" : "none";
                    });
                });

                document.addEventListener("click", (e) => {
                    if (!menu.contains(e.target) && e.target !== input) {
                        menu.style.display = "none";
                    }
                });

                // Untuk menyimpan data multiple customer
                let selectedCustomers = [];

                Array.from(menu.children).forEach(item => {
                    item.addEventListener("click", () => {
                        const id = item.dataset.value;
                        const name = item.dataset.name;

                        // Cegah duplikat
                        if (!selectedCustomers.find(c => c.id === id)) {
                            selectedCustomers.push({ id, name });

                            const badge = document.createElement("span");
                            badge.className = "badge bg-primary me-1";
                            badge.textContent = name;

                            const removeBtn = document.createElement("button");
                            removeBtn.textContent = "×";
                            removeBtn.style.marginLeft = "5px";
                            removeBtn.classList.add("btn", "btn-sm", "btn-danger");
                            removeBtn.addEventListener("click", () => {
                                selectedCustomers = selectedCustomers.filter(c => c.id !== id);
                                selectedList.removeChild(badge);
                                updateHiddenField();
                            });

                            badge.appendChild(removeBtn);
                            selectedList.appendChild(badge);

                            updateHiddenField();
                        }

                        input.value = "";
                        menu.style.display = "none";
                    });
                });

                function updateHiddenField() {
                hiddenInput.value = JSON.stringify(selectedCustomers.map(c => String(c.id))); // ← Ubah jadi string array
                nameInput.value = JSON.stringify(selectedCustomers.map(c => c.name));
                }

            }



            function updateDropdownListeners() {
                let dropdowns = document.querySelectorAll('.jenis-dropdown');
                dropdowns.forEach(function(dropdown) {
                    dropdown.addEventListener('change', function() {
                        let row = dropdown.closest('tr');
                        let thickness = row.querySelector('input[name="thickness"]');
                        let weight = row.querySelector('input[name="weight"]');
                        let innerDiameter = row.querySelector('input[name="inner_diameter"]');
                        let outerDiameter = row.querySelector('input[name="outer_diameter"]');

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
            

            function saveTable() {
                var tableBody = document.getElementById('table-body');
                var rows = tableBody.querySelectorAll('tr');
                var createBy = '{{ Auth::id() }}'; // Ambil ID user yang login

                var data = {
                    id_inquiry: '{{ $inquiry->id }}',
                    kode_inquiry: '{{ $inquiry->kode_inquiry }}',
                    create_by: createBy, // Pastikan ID user login terisi
                    materials: []
                };
                var hasInvalidSO = false; // Flag untuk validasi SO

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
                    var customerElement = row.querySelector('input[name="customer"]');
                    var nameCustomerElement = row.querySelector('input[name="name_customer"]');
                    var klasifikasiElement = row.querySelector('select[name="klasifikasi"]');

                    if (idTypeElement && jenisElement && idTypeElement.value !== "" && jenisElement.value !== "") {
                        var soValue = soElement ? soElement.value.trim() : "";

                        var isValidSO = /^\d{5}$/.test(soValue);

                        if (!isValidSO) {
                            hasInvalidSO = true; // Set flag jika ada SO tidak valid
                            soElement.classList.add("is-invalid"); // Tambahkan class untuk error styling
                        } else {
                            soElement.classList.remove("is-invalid"); // Hapus class jika valid
                        }
                        var formattedSO = `SO/${new Date().getFullYear()}/${String(soValue).padStart(5, '0')}`;

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
                            so: formattedSO,
                            note: noteElement ? noteElement.value : null,
                            customer: customerElement ? customerElement.value : [],
                            name_customer: nameCustomerElement ? customerElement.value : [],
                            klasifikasi: klasifikasiElement ? klasifikasiElement.value : null,
                        };


                        data.materials.push(rowData);
                    }
                });

                if (data.materials.length === 0) {
                    alert('Silakan tambahkan material terlebih dahulu.');
                    return;
                }

                // Validasi data sebelum dikirim
                var isValid = data.materials.every(material =>
                    material.id_type && material.jenis && material.qty && material.m1 && material.so && material.note &&
                    material.customer && material.name_customer && material.klasifikasi
                );

                if (!isValid) {
                    alert('Mohon lengkapi semua field yang diperlukan.');
                    return;
                }

                // Jika ada SO yang tidak valid, tampilkan alert dan hentikan proses
                if (hasInvalidSO) {
                    Swal.fire({
                        title: "Error!",
                        text: "Kolom SO harus diisi dengan 5 digit angka!",
                        icon: "error",
                        timer: 3000,
                        timerProgressBar: true
                    });
                    return;
                }


                // Kirim data dengan AJAX
                $.ajax({
                    url: '{{ route('inquiry.previewSSImport') }}',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    dataType: 'json', // Pastikan menerima JSON dari server
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Menyimpan...',
                            text: 'Harap tunggu',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Sukses!',
                            text: 'Inquiry berhasil disimpan',
                            icon: 'success',
                            timer: 2000,
                            timerProgressBar: true,
                            willClose: () => {
                                window.location.href = '{{ route('showFormSSimport', $inquiry->id) }}';
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error('Error occurred:', xhr);
                        var errorMessage = 'Terjadi kesalahan saat menyimpan data.';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                });
            }
        </script>
    </main><!-- End #main -->
@endsection
