@yield('js');

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all the accordion elements
        var accordions = document.querySelectorAll('.accordion');

        // Add click event listener to each accordion
        accordions.forEach(function(accordion) {
            // Toggle the 'show' class on collapse element when the accordion title is clicked
            accordion.querySelector('.card-title').addEventListener('click', function() {
                accordion.querySelector('.collapse').classList.toggle('show');
            });
        });
    });
</script>

<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.umd.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>

<!-- Template Main JS File -->
<script src="{{ asset('assets/js/main.js') }}"></script>

<!-- Template Main JS File -->
<script src="{{ asset('assets/js/main.js') }}"></script>

{{-- JS Search DropDown --}}
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
{{-- datatable --}}
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

{{-- searchdropdownJS --}}
<!-- Tambahkan library Select2 -->
{{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script> --}}

{{-- DateRangePicker --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- JS for jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<!-- JS for full calender -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<!-- Menghubungkan ke jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
@yield('scripts')
<script>
    //datepickerExcel
    // Fungsi untuk mendapatkan nilai tanggal dari input dan mengatur tautan tombol eksport
    // Mengambil nilai tanggal mulai dan tanggal selesai
    function exportToExcel() {
        // Mengambil nilai tanggal mulai dan tanggal selesai
        var startDate = document.getElementById("start-date").value;
        var endDate = document.getElementById("end-date").value;

        // Memeriksa apakah kedua tanggal sudah dipilih
        if (!startDate || !endDate) {
            // Menampilkan pesan SweetAlert untuk validasi
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Select the start date and end date first'
            });
            return; // Berhenti dari fungsi jika salah satu atau kedua tanggal belum dipilih
        }

        // Mengambil semua baris data dari tabel
        var tableRows = document.querySelectorAll("tbody tr");

        // Membuat objek workbook Excel
        var wb = XLSX.utils.book_new();
        var ws_name = "Data"; // Nama sheet Excel

        // Membuat array untuk menyimpan data
        var data = [];

        // Mengambil header dari tabel untuk judul kolom
        var tableHeader = [];
        document.querySelectorAll("thead th").forEach(function(th) {
            tableHeader.push(th.textContent.trim());
        });
        data.push(tableHeader);

        // Melakukan iterasi melalui setiap baris tabel
        tableRows.forEach(function(row) {
            // Mengambil tanggal due_date dari kolom 'Due Date'
            var dueDate = row.cells[19].innerText.trim();

            // Memeriksa apakah tanggal 'Due Date' berada dalam rentang yang dipilih
            if (dueDate >= startDate && dueDate <= endDate) {
                // Jika dalam rentang tanggal, tambahkan data baris ke dalam array
                var rowData = [];
                row.querySelectorAll('td').forEach(function(cell) {
                    rowData.push(cell.innerText.trim());
                });
                data.push(rowData);
            }
        });

        // Membuat worksheet Excel dari data yang dipilih
        var ws = XLSX.utils.aoa_to_sheet(data);

        // Menambahkan header autofilter
        ws['!autofilter'] = {
            ref: XLSX.utils.encode_range(XLSX.utils.decode_range(ws['!ref']))
        };

        // Menambahkan worksheet ke dalam workbook
        XLSX.utils.book_append_sheet(wb, ws, ws_name);

        // Membuat file Excel dari workbook
        XLSX.writeFile(wb, 'History_Claim_Complain.xlsx');
    }

    // Menentukan objek jsPDF di window
    window.jsPDF = window.jspdf.jsPDF;

    // EksportPDF
    document.addEventListener('DOMContentLoaded', function() {
        // Kode JavaScript Anda di sini akan dijalankan setelah seluruh dokumen HTML telah dimuat

        // Misalnya, Anda dapat menambahkan kode untuk menangani klik tombol export PDF di sini:
        document.querySelectorAll('.export-pdf-button').forEach(function(button) {
            button.addEventListener('click', function() {
                var rowId = this.getAttribute(
                    'data-row-id'); // Mendapatkan ID baris dari atribut data-row-id tombol
                exportRowToPDF(
                    rowId); // Memanggil fungsi exportRowToPDF dengan ID baris yang diberikan
            });
        });
    });

    function exportRowToPDF(rowId) {
        // Logika untuk mengekstrak data dari baris yang ditentukan menggunakan rowId
        var rowData = getRowDataById(rowId);

        // Logika untuk memformat rowData dan membuat dokumen PDF
        var doc = new jsPDF();

        // Menambahkan judul
        doc.setFontSize(22);
        doc.setTextColor(44, 62, 80); // Warna judul
        doc.text("History Claim & Complain", 105, 20, {
            align: "center"
        });

        // Menambahkan garis pembatas
        doc.setLineWidth(0.5);
        doc.setDrawColor(44, 62, 80); // Warna garis pembatas
        doc.line(20, 25, 190, 25);

        // Menambahkan data ke dokumen PDF
        doc.setFontSize(12);
        doc.setTextColor(44, 62, 80); // Warna teks
        var startY = 35;
        var lineHeight = 10;
        var marginLeft = 20;
        rowData.forEach(function(data, index) {
            doc.text(marginLeft, startY + index * lineHeight, data);
        });

        // Simpan dokumen PDF
        doc.save("invoice.pdf");
    }

    function getRowDataById(rowId) {
        console.log("Row ID:", rowId); // Tambah consol log untuk rowId
        // Logika untuk mengekstrak data dari baris yang ditentukan berdasarkan ID-nya
        // Anda dapat menggunakan metode manipulasi DOM untuk mengambil data dari baris tabel dengan ID yang diberikan
        // Contoh:
        var row = document.getElementById("row_" + rowId);
        console.log("Row Element:", row); // Tambah consol log untuk row
        var rowData = [];
        // Ekstrak data dari setiap sel dalam baris
        row.querySelectorAll('td').forEach(function(cell) {
            rowData.push(cell.innerText.trim());
        });
        return rowData;
    }


    // imageModal
    $(document).ready(function() {
        $('.clickable-image').click(function() {
            var imageUrl = $(this).attr('src');
            $('#modalImage').attr('src', imageUrl);
            $('#imageModal').modal('show');
        });
    });

    function SaveAndUpdate() {
        // Lakukan sesuatu saat tombol "Save" atau "Finish" ditekan
        // Contoh: Validasi form, kemudian kirimkan data melalui AJAX jika valid
        // Untuk contoh sederhana, saya hanya menampilkan pesan
        alert('Save or Finish button clicked');
    }

    function FinishAndUpdate() {
        // Lakukan sesuatu saat tombol "Back" ditekan
        // Contoh: Kembali ke halaman sebelumnya atau lakukan navigasi lainnya
        // Untuk contoh sederhana, saya hanya menampilkan pesan
        alert('Back button clicked');
    }
    //sweetalertSave
    function validateAndSubmit() {
        var formData = new FormData(document.getElementById('formInputHandling'));

        var no_wo = formData.get('no_wo');
        var image = formData.get('image');
        var customerName = formData.get('name_customer');
        var customerCode = formData.get('customer_id');
        var area = formData.get('area');
        var qty = formData.get('qty');
        var pcs = formData.get('pcs');
        var category = formData.get('category');
        var process_type = formData.get('process_type');
        var type_1 = formData.getAll('type_1');

        // Memeriksa apakah ada input yang kosong
        if (!no_wo || !image || !customerName || !customerCode || !area || !qty || !pcs || !category || !process_type ||
            type_1.length === 0) {
            // Menampilkan sweet alert error jika ada input yang kosong
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Please fill all the fields before saving.',
            });
        } else {
            // Simulasi validasi
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Data has been saved successfully.',
                showConfirmButton: false
            });
        }
    }

    //datatabelSales
    $(document).ready(function() {
        new DataTable('#viewSales');

    });

    //datatableSubmision
    $(document).ready(function() {
        new DataTable('table.display');
    });

    //COnfrimDelete
    document.addEventListener('DOMContentLoaded', function() {
        // Menggunakan event listener untuk menangkap klik pada tombol delete
        document.querySelectorAll('.delete-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                // Menampilkan SweetAlert untuk konfirmasi penghapusan
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You sure delete this data?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna menekan tombol konfirmasi pada SweetAlert,
                        // maka arahkan ke rute penghapusan
                        window.location.href = button.getAttribute(
                            'data-url');
                    }
                });
            });
        });
    });

    // //jsDropdownCLaim-cutting
    document.getElementById('process_type').addEventListener('change', function() {
        var dropdownValue = this.value;
        var checkBox1 = document.getElementById('type_2');

        if (dropdownValue === 'Cutting') {
            checkBox1.checked = true;
        } else {
            checkBox1.checked = false;
        }
    });

    //RefreshFromPageInputCreateHandling
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');

        // Check if the page was accessed from the index page
        const fromIndex = document.referrer.includes("index");

        if (fromIndex) {
            // Set initial values for form elements if coming from index page
            resetFormValues();
        }

        const resetButton = document.querySelector('button[type="reset"]');

        resetButton.addEventListener('click', function() {
            // Reset values to default or empty
            resetFormValues();

            // Hide cancel upload button and error message
            document.getElementById('cancelUpload').style.display = 'none';
            document.getElementById('fileError').style.display = 'none';
        });

        function resetFormValues() {
            // Reset values to default or empty for text inputs
            const textInputs = form.querySelectorAll('input[type="text"]');
            textInputs.forEach(input => {
                input.value = '';
            });

            // Reset selected values for dropdowns
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.value = '';
            });

            // Reset checkboxes to default state (checked or unchecked)
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = checkbox.defaultChecked;
            });
        }
    });
    // readimageform
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById('preview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);

        // Show the preview image div
        document.getElementById('imagePreview').style.display = 'block';
    }

    //upload fileJS
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('formFile');
        const cancelUploadButton = document.getElementById('cancelUpload');
        const fileError = document.getElementById('fileError');

        fileInput.addEventListener('change', handleFileSelection);
        cancelUploadButton.addEventListener('click', cancelFileUpload);

        function handleFileSelection() {
            const allowedFormats = ['image/jpeg', 'image/png',
                'image/gif'
            ]; // Add more formats if needed
            const selectedFile = fileInput.files[0];

            if (selectedFile) {
                if (allowedFormats.includes(selectedFile.type)) {
                    // Valid image format
                    fileError.style.display = 'none';
                    cancelUploadButton.style.display = 'inline-block';
                } else {
                    // Invalid image format
                    fileError.style.display = 'block';
                    cancelUploadButton.style.display = 'none';
                    resetFileInput();
                }
            }
        }


        function cancelFileUpload() {
            resetFileInput();
            fileError.style.display = 'none';
            cancelUploadButton.style.display = 'none';

            // Hide the preview image div
            document.getElementById('imagePreview').style.display = 'none';
            // Hide the cancel upload button
            document.getElementById('cancelUpload').style.display = 'none';
            // Clear the file input value
            document.getElementById('formFile').value = '';
        }

        function resetFileInput() {
            // Reset file input by cloning and replacing it
            const newFileInput = fileInput.cloneNode(true);
            fileInput.parentNode.replaceChild(newFileInput, fileInput);
            newFileInput.addEventListener('change', handleFileSelection);
        }
    });

    //reset
    document.addEventListener('DOMContentLoaded', function() {
        const resetButton = document.querySelector('button[type="submit"][name="reset"]');
        const form = document.querySelector('form');

        resetButton.addEventListener('click', function() {
            // Reset values to default or empty
            form.reset();

            // Reset checkboxes to default state (checked or unchecked)
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = checkbox.defaultChecked;
            });

            // Hide cancel upload button and error message
            document.getElementById('cancelUpload').style.display = 'none';
            document.getElementById('fileError').style.display = 'none';
        });
    });

    //backButonSales
    function goToIndex() {
        window.location.href = "{{ route('index') }}"; // Ganti 'index' dengan nama rute halaman index Anda
    }

    // searchdropdown
    // Inisialisasi Select2 pada semua dropdown dengan class "select2"
    $(document).ready(function() {
        $('.select2').select2();
    });

    //backButonDeptMan
    function goToSubmission() {
        window.location.href =
            "{{ route('submission') }}"; // Ganti 'index' dengan nama rute halaman index Anda
    }

    // searchdropdown
    // Inisialisasi Select2 pada semua dropdown dengan class "select2"
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>