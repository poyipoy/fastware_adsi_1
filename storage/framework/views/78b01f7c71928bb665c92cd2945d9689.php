

<?php $__env->startSection('content'); ?>

    <main id="main" class="main">

        <?php
            $inquiries = $inquiries ?? collect();
        ?>

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
                    <div class="d-flex align-items-center justify-content-start mt-4 mb-3">
                        <?php
                            $user = Auth::user();
                        ?>

                        <?php if($user && in_array($user->name, ['YULMAI RIDO WINANDA', 'ILHAM CHOLID','ADMINSTRATOR','JESSICA PAUNE','JUN JOHAMIN PD','ANDIK TOTOK SISWOYO'])): ?>
                        <button class="btn btn-add btn-sm me-2" data-bs-toggle="modal" data-bs-target="#inquiryImportModal">
                            <i class="bx bx-plus-medical fw-bold"> Add Import</i>
                        </button>
                        <?php endif; ?>
                        
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <?php $user = Auth::user(); ?>

                            
                            <?php if($user && in_array($user->name, [
                                'YULMAI RIDO WINANDA', 
                                'JUN JOHAMIN PD',
                                'ADMINSTRATOR',
                                'JESSICA PAUNE',
                                'WULYO EKO PRASETYO',
                                'YAN WELEM MANGINSELA',
                                'SENDY PRABOWO'
                                ])): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title fo fw-bold">Inquiry Region 1</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="inquiryTable1">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">No</th>
                                                        <th scope="col">Bulan</th>
                                                        <th scope="col">Create By</th>
                                                        <th scope="col">Reference</th>
                                                        <th scope="col">Submit Date</th>
                                                        <th scope="col">Category</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Last Update</th>
                                                        <th scope="col">Update Time</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            
                            <?php if($user && in_array($user->name, [
                                'ADMINSTRATOR',
                                'JESSICA PAUNE',
                                'YULMAI RIDO WINANDA', 
                                'ILHAM CHOLID',
                                'SONY STIAWAN',
                                'SARAH EGA BUDI ASTUTI',
                                'HERY HERMAWAN',
                                'HEXAPA DARMADI',
                                'DIMAS ADITYA PRIANDANA'
                                ])): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title fo fw-bold">Inquiry Region 2</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="inquiryTable2">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">No</th>
                                                        <th scope="col">Bulan</th>
                                                        <th scope="col">Create By</th>
                                                        <th scope="col">Reference</th>
                                                        <th scope="col">Submit Date</th>
                                                        <th scope="col">Category</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Last Update</th>
                                                        <th scope="col">Update Time</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            
                            <?php if($user && in_array($user->name, [
                                'ANDIK TOTOK SISWOYO',
                                'ADMINSTRATOR',
                                'JESSICA PAUNE',
                                'RISFAN FAISAL',
                                'DWI KUNTORO',
                                'YUNASIS PALGUNADI'
                                ])): ?>  
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title fo fw-bold">Inquiry Region 3</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="inquiryTable3">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">No</th>
                                                        <th scope="col">Bulan</th>
                                                        <th scope="col">Create By</th>
                                                        <th scope="col">Reference</th>
                                                        <th scope="col">Submit Date</th>
                                                        <th scope="col">Category</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Last Update</th>
                                                        <th scope="col">Update Time</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            
                            <?php if($user && in_array($user->name, ['ANDIK TOTOK SISWOYO', 'DANIA ISNAWATI','ADMINSTRATOR','JESSICA PAUNE'])): ?>  
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title fo fw-bold">Inquiry Region 4</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="inquiryTable4">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">No</th>
                                                        <th scope="col">Bulan</th>
                                                        <th scope="col">Create By</th>
                                                        <th scope="col">Reference</th>
                                                        <th scope="col">Submit Date</th>
                                                        <th scope="col">Category</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Last Update</th>
                                                        <th scope="col">Update Time</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    <!-- Modal Add-->
                    <div class="modal fade" id="inquiryImportModal" tabindex="-1" aria-labelledby="inquiryModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="inquiryModalLabel">Form Inquiry Import</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="<?php echo e(route('storeinquiryImport')); ?>" method="POST"
                                        enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <div class="mb-3">
                                            <label for="loc_imp" class="form-label fw-bold">Jenis Inquiry <span
                                                    class="disabledform">
                                                    [*Form Disabled]</span></label>
                                            <input type="text" class="form-control" id="loc_imp" name="loc_imp"
                                                value="Import" readonly required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="jenis_inquiry_display" class="form-label fw-bold">Category <span
                                                    class="disabledform">
                                                    [*Form Disabled]</span></label>
                                            <input type="text" class="form-control" id="jenis_inquiry_display" value="Order Import" readonly>
                                            <input type="hidden" name="jenis_inquiry" value="IM">

                                        </div>

                                        

                                        <!-- Region (Searchable Dropdown) -->
                                        <div class="mb-3">
                                            <label for="id_region" class="form-label fw-bold">Region</label>
                                            <div class="position-relative">
                                                <select class="form-select" id="id_region" name="region" required>
                                                    <option value="" disabled selected>Pilih Region</option>
                                                    <?php
                                                        $user = Auth::user();
                                                        $allowedRegions = [];

                                                        // Menentukan region yang bisa diakses berdasarkan nama pengguna
                                                        if (in_array($user->name, ['JUN JOHAMIN PD' ,'ADMINSTRATOR','JESSICA PAUNE'])) {
                                                            $allowedRegions[] = 1; // Region 1
                                                        }
                                                        if (in_array($user->name, ['YULMAI RIDO WINANDA', 'ILHAM CHOLID','ADMINSTRATOR','JESSICA PAUNE'])) {
                                                            $allowedRegions[] = 2; // Region 2
                                                        }
                                                        if (in_array($user->name, ['ANDIK TOTOK SISWOYO','ADMINSTRATOR ','JESSICA PAUNE'])) {
                                                            $allowedRegions[] = 3; // Region 3
                                                        }
                                                        if (in_array($user->name, ['ANDIK TOTOK SISWOYO', 'DANIA ISNAWATI','ADMINSTRATOR','JESSICA PAUNE'])) {
                                                            $allowedRegions[] = 4; // Region 4
                                                        }
                                                    ?>
                                                    <?php $__currentLoopData = $allowedRegions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($region); ?>"
                                                            <?php echo e(old('region', $inquiry->region ?? '') == $region ? 'selected' : ''); ?>>
                                                            Region <?php echo e($region); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                        </div>


                                        <!-- Modal Footer -->
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Modal -->

                    <div class="modal fade" id="editInquiryImportModal" tabindex="-1"
                        aria-labelledby="editInquiryModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editInquiryModalLabel">Edit Inquiry</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="editInquiryForm"
                                        action="<?php echo e(route('updateinquiry', ['id' => 0])); ?>"
                                        method="POST" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <input type="hidden" id="editInquiryId" name="inquiry_id">
                                        <div class="mb-3">
                                            <label for="editjenis_inquiry" class="form-label">Jenis Inquiry</label>
                                            <select class="form-select" id="editjenis_inquiry" name="jenis_inquiry"
                                                required disabled>
                                                <option value="RO">Reguler Order</option>
                                                <option value="SPOR">Spesial Order</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="editloc_imp" class="form-label">Category</label>
                                            <input type="text" class="form-control" id="editloc_imp" name="loc_imp"
                                                value="Import" disabled required>
                                        </div>

                                        <div class="mb-3 edit-searchable-dropdown">
                                            <label for="search_edit_customer" class="form-label">Order from</label>
                                            <input type="text" class="form-control" id="search_edit_customer"
                                                placeholder="Search customer...">
                                            <div id="edit_customer_list" class="dropdown-menu show"
                                                style="width: 100%; display: none; max-height: 200px; overflow-y: auto;">
                                                <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="dropdown-item" data-value="<?php echo e($customer->id); ?>">
                                                        <?php echo e($customer->name_customer); ?></div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                            <input type="hidden" id="edit_id_customer" name="id_customer">
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- End Edit Inquiry Modal -->
                </div>
            </div>
            <!-- Modal for Viewing Progress History -->
            <div class="modal fade" id="progressHistoryModal1" tabindex="-1"
                aria-labelledby="progressHistoryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="progressHistoryModalLabel">History Progress</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-1" id="historyTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Progress Description</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyBody">
                                        <!-- Data akan ditambahkan melalui AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- DataTables -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

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
            document.addEventListener('DOMContentLoaded', function() {
                const locImpSelect = document.getElementById('loc_imp'); // Dropdown, tetapi hanya untuk Local
                const searchInput = document.getElementById('search_customer');
                const customerList = document.getElementById('customer_list');
                const hiddenInput = document.getElementById('id_customer');
                const selectedCustomersList = document.getElementById('selected_customers_list');

                if (locImpSelect) {
                    locImpSelect.value = 'Import'; // Set default ke Import
                }

                if (!searchInput || !customerList || !hiddenInput || !selectedCustomersList) {
                    return; // Elemen tidak tersedia (fitur sementara dinonaktifkan)
                }

                customerList.style.display = 'block'; // Selalu tampilkan daftar customer

                // Mencari customer berdasarkan input
                searchInput.addEventListener('input', function() {
                    const filter = searchInput.value.toLowerCase();
                    const items = customerList.getElementsByTagName('div');

                    for (let i = 0; i < items.length; i++) {
                        const txtValue = items[i].textContent || items[i].innerText;
                        items[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
                    }
                });

                    // Menangani pemilihan customer
                customerList.addEventListener('click', function(e) {
                    if (e.target && e.target.matches('div[data-value]')) {
                        const selectedValue = e.target.getAttribute('data-value');
                        const selectedText = e.target.textContent;

                        // Untuk Import, set satu customer dan tidak mengizinkan pemilihan lebih
                        searchInput.value = selectedText; // Tampilkan nama customer
                        hiddenInput.value = selectedValue; // Set ID customer
                        customerList.style.display = 'none'; // Sembunyikan daftar
                        selectedCustomersList.innerHTML = ''; // Kosongkan daftar, hanya satu yang terpilih
                        selectedCustomersList.innerHTML = '<span class="selected-customer">' + selectedText +
                            '</span>'; // Tampilkan customer terpilih
                    }
                });

                // Menangani fokus input
                searchInput.addEventListener('focus', function() {
                    customerList.style.display = 'block'; // Tampilkan dropdown saat mencari
                });

                // Menyembunyikan dropdown jika klik di luar
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.searchable-dropdown')) {
                        customerList.style.display = 'none'; // Sembunyikan dropdown jika klik di luar
                    }
                });
            });

            function openEditInquiryImportModal(id) {
                console.log('Opening modal for inquiry ID: ' + id);
                $.ajax({
                    url: '<?php echo e(route('editInquiry', ['id' => ':id'])); ?>'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        console.log('Response:', response);

                        // Populate the form with the received data
                        $('#editjenis_inquiry').val(response.jenis_inquiry);
                        $('#search_edit_customer').val(response.customer_name);
                        $('#edit_id_customer').val(response.id_customer);
                        $('#editloc_imp').val(response.loc_imp);
                        // $('#editsupplier').val(response.supplier);
                        $('#editInquiryId').val(response.id);

                        // Populate the customer dropdown
                        const editDropdown = $('#edit_customer_list');
                        editDropdown.empty(); // Clear existing options
                        response.customers.forEach(customer => {
                            const item = $('<div>').addClass('dropdown-item').attr('data-value',
                                customer
                                .id).text(customer.name_customer);
                            item.on('click', function() {
                                $('#search_edit_customer').val(customer.name_customer);
                                $('#edit_id_customer').val(customer.id);
                                editDropdown.hide();
                            });
                            editDropdown.append(item);
                        });

                        // Set the current customer name in the search input
                        $('#search_edit_customer').val(response.customer_name);

                        // Show the modal
                        $('#editInquiryImportModal').modal('show');

                        // Update inquiry on save
                        $('#editInquiryForm').off('submit').on('submit', function(e) {
                            e.preventDefault(); // Mencegah form dari pengiriman default
                            const inquiryId = $('#editInquiryId').val();
                            const updateData = {
                                jenis_inquiry: $('#editjenis_inquiry').val(),
                                id_customer: $('#edit_id_customer').val(),
                                loc_imp: $('#editloc_imp').val(),
                                // supplier: $('#editsupplier').val(),
                                _token: '<?php echo e(csrf_token()); ?>', // Sertakan token CSRF untuk keamanan
                                _method: 'PUT', // Menggunakan metode PUT untuk update
                            };

                            $.ajax({
                                url: '<?php echo e(route('updateinquiry', ['id' => ':id'])); ?>'
                                    .replace(':id',
                                        inquiryId),
                                type: 'POST', // Gunakan POST karena kita menipu metode
                                data: updateData,
                                success: function(response) {
                                    // SweetAlert notification
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Inquiry updated successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        $('#editInquiryImportModal').modal(
                                            'hide'); // Tutup modal
                                        location.reload(); // Reload halaman
                                    });
                                },
                                error: function(xhr) {
                                    console.log(xhr.responseText);
                                }
                            });
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            }

            jQuery(function($) {
                const createImportUrl = <?php echo json_encode(route('createinquiryImport'), 15, 512) ?>;

                const escapeHtml = (value) => {
                    if (value === null || value === undefined) {
                        return '';
                    }

                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                const statusBadge = (row) => {
                    const cssClass = row.status_class || 'btn-light';
                    const label = escapeHtml(row.status_label || 'Unknown');

                    return `<span class="btn btn-sm ${cssClass}">${label}</span>`;
                };

                function initRegionTable(selector, region) {
                    if (!$(selector).length) {
                        return null;
                    }

                    return $(selector).DataTable({
                        processing: true,
                        serverSide: true,
                        searching: true,
                        lengthMenu: [5, 10, 20, 50],
                        pageLength: 10,
                        ajax: {
                            url: createImportUrl,
                            data: function(params) {
                                params.format = 'json';
                                params.region = region;
                            }
                        },
                        columns: [
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                render: function(data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                                }
                            },
                            { data: 'month_label', name: 'month_label', defaultContent: '-' },
                            { data: 'create_by', name: 'create_by', defaultContent: '-' },
                            { data: 'kode_inquiry', name: 'kode_inquiry', defaultContent: '-' },
                            { data: 'submit_date', name: 'submit_date', defaultContent: '-' },
                            { data: 'category', name: 'loc_imp', defaultContent: '-' },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                render: function(data, type, row) {
                                    return statusBadge(row);
                                }
                            },
                            { data: 'last_update', name: 'last_update', defaultContent: 'No updates yet' },
                            { data: 'update_time', name: 'update_time', defaultContent: 'No updates yet' },
                            {
                                data: 'actions',
                                orderable: false,
                                searchable: false,
                                render: function(data) {
                                    return data || '';
                                }
                            }
                        ],
                        order: [],
                        columnDefs: [
                            { targets: '_all', className: 'text-center align-middle' }
                        ]
                    });
                }

                ['#inquiryTable1', '#inquiryTable2', '#inquiryTable3', '#inquiryTable4'].forEach(function(selector, index) {
                    initRegionTable(selector, index + 1);
                });
            });

            //delete
            function deleteInquiry(id) {
                Swal.fire({
                    title: "Apakah Anda yakin?",
                    text: "Anda akan menghapus inquiry ini!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?php echo e(route('deleteinquiry', '')); ?>/' + id,
                            type: 'DELETE',
                            data: {
                                '_token': '<?php echo e(csrf_token()); ?>'
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    'Inquiry telah berhasil dihapus!',
                                    'success'
                                ).then((result) => {
                                    // Jika pengguna menekan tombol 'OK', refresh halaman
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                })
                                // Anda bisa menambahkan kode untuk menghapus baris tabel atau memperbarui tampilan di sini
                            }
                        });
                    }
                })
            }

            function showProgressModal1(id) {
                // Tampilkan modal
                $('#progressHistoryModal1').modal('show');

                // Ambil data progress untuk inquiry tersebut
                $.ajax({
                    url: '<?php echo e(route('progressHistory', '')); ?>/' + id, // Pastikan route-nya sesuai
                    method: 'GET',
                    success: function(response) {
                        const historyBody = $('#historyBody');
                        historyBody.empty(); // Bersihkan data sebelumnya

                        // Menambahkan data response ke dalam tabel
                        response.progressUpdates.forEach((progress, index) => {
                            historyBody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${new Date(progress.created_at).toLocaleString()}</td>
                                <td>${progress.user.name}</td>
                                <td>${progress.description}</td>
                            </tr>
                        `);
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while fetching progress history.',
                            'error');
                    }
                });
            }

            // report
            const exportButton = document.getElementById('exportReportBtn');
            if (exportButton) {
                exportButton.addEventListener('click', function() {
                    // Get the table element
                    var table = document.getElementById('inquiryTable');

                    // Create a workbook and add a worksheet
                    var wb = XLSX.utils.table_to_book(table, {
                        sheet: "Inquiry Report"
                    });
                    var ws = wb.Sheets["Inquiry Report"];

                    // Filter out unwanted columns (Note, File, is Active, Actions)
                    // Define the columns we want to keep (1-based index: No, Kode Inq., Type Inq., Type, Size, Supplier, Qty, Order From, Create By, To Approve, To Validate)
                    var columnsToKeep = [1, 2, 3, 4, 5, 6, 7];

                    // Get the range of the worksheet
                    var range = XLSX.utils.decode_range(ws['!ref']);

                    // Create a new worksheet to store the filtered data
                    var newWsData = [];

                    for (var R = range.s.r; R <= range.e.r; ++R) {
                        var newRow = [];
                        for (var C = range.s.c; C <= range.e.c; ++C) {
                            if (columnsToKeep.includes(C + 1)) {
                                var cellAddress = {
                                    c: C,
                                    r: R
                                };
                                var cellRef = XLSX.utils.encode_cell(cellAddress);
                                newRow.push(ws[cellRef] ? ws[cellRef].v : null);
                            }
                        }
                        newWsData.push(newRow);
                    }

                    // Create a new worksheet with the filtered data
                    var newWs = XLSX.utils.aoa_to_sheet(newWsData);

                    // Apply auto filter to the header row
                    newWs['!autofilter'] = {
                        ref: `A1:K${newWsData.length}`
                    };

                    // Adjust column widths
                    var colWidths = [{
                            wpx: 40
                        }, // No
                        {
                            wpx: 100
                        }, // Kode Inq.

                        {
                            wpx: 120
                        }, // Supplier

                        {
                            wpx: 100
                        }, // Order From
                        {
                            wpx: 100
                        }, // Create By
                        {
                            wpx: 100
                        }, // To Approve
                        {
                            wpx: 100
                        } // To Validate
                    ];
                    newWs['!cols'] = colWidths;

                    // Replace the old worksheet with the new one
                    wb.Sheets["Inquiry Report"] = newWs;

                    // Write the workbook to a file
                    XLSX.writeFile(wb, 'Inquiry_Report.xlsx');
                });
            }
        </script>

        <script>
            var inputCount = 1; // Untuk menghitung jumlah input yang ada

            function addInput() {
                inputCount++;
                var typeId = `type_input_${inputCount}`;
                var sizeId = `size_input_${inputCount}`;
                var html = `<div class="mb-3">
                                 <label for="${typeId}" class="form-label">Type</label>
                                <input type="text" class="form-control type-input" id="${typeId}" name="type[]" required>
                            </div>
                            <div class="mb-3">
                                  <label for="${sizeId}" class="form-label">Size</label>
                                <input type="text" class="form-control size-input" id="${sizeId}" name="size[]" required>
                            </div>`;
                $('#inputContainer').append(html);
            }
        </script>

        <script>
            function handleCategoryChange() {
                const locImpSelect = document.getElementById('loc_imp');
                const otherContainer = document.getElementById('other-container');
                const customerInput = document.getElementById('search_customer');

                if (locImpSelect.value === 'Import') {
                    customerInput.disabled = true; // Disable customer input
                    otherContainer.style.display = 'block'; // Show other input
                } else {
                    customerInput.disabled = false; // Enable customer input
                    otherContainer.style.display = 'none'; // Hide other input
                }
            }

            function addOtherField() {
                const additionalFieldsContainer = document.getElementById('additional-fields');
                const newOtherField = document.createElement('input');
                newOtherField.type = 'text';
                newOtherField.name = 'other[]';
                newOtherField.placeholder = 'Enter other...';
                newOtherField.classList.add('form-control');
                additionalFieldsContainer.appendChild(newOtherField);
            }
        </script>


    </main><!-- End #main -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\fastware_adsi_1\resources\views/inquiry/createImport.blade.php ENDPATH**/ ?>