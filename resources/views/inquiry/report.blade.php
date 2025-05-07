@extends('layout')

@section('content')
    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Report Inquiry Sales</h5>

                    <!-- Table with stripped rows -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="inquiryTable">
                            <thead>
                                <tr>
                                    <th scope="col">No</th>
                                    <th scope="col">Kode Inq.</th>
                                    <th scope="col">Order From</th>
                                    <th scope="col">Create By</th>
                                    <th scope="col">Note</th>
                                    <th scope="col">File</th>
                                    <th scope="col" class="text-center">is Active</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inquiries as $inquiry)
                                    <tr>
                                        <th scope="row">{{ $loop->iteration }}</th>
                                        <td>{{ $inquiry->kode_inquiry }}</td>
                                        <td>
                                            @if ($inquiry->customer)
                                                {{ $inquiry->customer->name_customer }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $inquiry->create_by }}</td>
                                        <td>{{ $inquiry->note }}</td>
                                        <td>
                                            @if ($inquiry->attach_file)
                                                <a href="{{ asset('assets/files/' . $inquiry->attach_file) }}"
                                                    target="_blank">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                            @else
                                                <i class="fas fa-times"></i> No File
                                            @endif
                                        </td>

                                        <td>
                                            @if ($inquiry->status == 0)
                                                <button type="button" class="btn btn-danger" title="Data tidak aktif">
                                                    <i class="bi bi-exclamation-octagon"></i>
                                                </button>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($inquiry->status != 5 && $inquiry->status != 6 && $inquiry->status != 7)
                                                <a class="btn btn-success mt-1" title="Edit">
                                                    <i class="bi bi-check2-all"
                                                        onclick="openViewInquiryModal({{ $inquiry->id }})"></i>
                                                </a>
                                            @endif
                                            @if ($inquiry->status != 4 && $inquiry->status != 6 && $inquiry->status != 7)
                                                <a class="btn btn-primary mt-1" title="Tindak Lanjut"
                                                    href="{{ route('tindakLanjutInquiry', $inquiry->id) }}">
                                                    <i class="bi bi-card-checklist"></i>
                                                </a>
                                            @endif
                                            <a class="btn btn-warning mt-1" title="View Form"
                                                href="{{ route('historyFormSS', $inquiry->id) }}">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- validate -->
                    <div class="modal fade" id="viewInquiryModal" tabindex="-1" aria-labelledby="viewInquiryModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewInquiryModalLabel">Form Approved Inquiry</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="viewInquiryForm" action="{{ route('validateInquiry', ['id' => ':id']) }}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" id="viewInquiryId" name="inquiry_id">
                                        <input type="hidden" id="action_type" name="action_type">
                                        <div class="mb-3">
                                            <label for="viewkode_inquiry" class="form-label">Kode Inquiry</label>
                                            <input type="text" class="form-control" id="viewkode_inquiry"
                                                name="kode_inquiry" required disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label for="vieworder_from" class="form-label">Order From</label>
                                            <input type="text" class="form-control" id="vieworder_from" name="order_from"
                                                required disabled>
                                        </div>
                                        <!-- Note and Attachment fields for validation -->
                                        <div id="noteAttachmentFields" style="display: none;">
                                            <div class="mb-3">
                                                <label for="note" class="form-label">Note</label>
                                                <textarea class="form-control" id="note" name="note"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="file" class="form-label">Attachment File</label>
                                                <input type="file" class="form-control" id="file" name="file">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div>
                                        <div class="modal-footer" id="initialButtons">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary"
                                                onclick="showNoteAttachmentFields('validated')">Validated</button>
                                            <button type="submit" class="btn btn-danger"
                                                onclick="setActionType('not_validated')">Not Validated</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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
        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dataTable = new simpleDatatables.DataTable("#inquiryTable", {
                    searchable: true, // Aktifkan fitur pencarian
                    perPage: 10, // Jumlah entri data per halaman
                    perPageSelect: [5, 10, 20, 50], // Opsi jumlah entri data per halaman
                    dataProps: {
                        "Urutan": (value, data) => {
                            const index = data.tableData.id;
                            const spoOrRo = data[index][0].startsWith("RO") ? "RO" : "SPOR";
                            const month = data[index][1];
                            const year = data[index][2];
                            const order = (index + 1).toString().padStart(3, '0');
                            return `${spoOrRo}/${month}/${year}/${order}`;
                        }
                    }
                });
            });

            function openViewInquiryModal(id) {
                $.ajax({
                    url: '{{ route('editInquiry', ['id' => ':id']) }}'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        console.log('Response:', response); // Debugging log

                        // Isi form modal dengan data yang diperoleh
                        $('#viewkode_inquiry').val(response.kode_inquiry);
                        $('#viewjenis_inquiry').val(response.jenis_inquiry);
                        $('#viewtype').val(response.type);
                        $('#viewsize').val(response.size);
                        $('#viewsupplier').val(response.supplier);
                        $('#viewqty').val(response.qty);
                        $('#vieworder_from').val(response.customer_name); // Update this line
                        $('#viewcreate_by').val(response.create_by);
                        $('#viewto_approve').val(response.to_approve);
                        $('#viewto_validate').val(response.to_validate);
                        $('#viewnote').val(response.note);
                        $('#viewInquiryId').val(response.id);

                        // Update form action URL dengan ID
                        $('#viewInquiryForm').attr('action', '{{ route('validateInquiry', ['id' => ':id']) }}'
                            .replace(':id', response.id));

                        if (response.attach_file) {
                            // Display the attached file (image or other)
                            var fileExtension = response.attach_file.split('.').pop().toLowerCase();
                            var fileLink = '{{ asset('assets/files/') }}/' + response.attach_file;

                            if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                                $('#viewImagePreview').attr('src', fileLink).attr('width', '150').attr('height',
                                    '150').show();
                                $('#viewFilePreview').hide();
                                $('#viewFileName').text('');
                                $('#viewImagePreview').click(function() {
                                    showImageInModal(fileLink);
                                });
                            } else {
                                $('#viewImagePreview').hide();
                                $('#viewFilePreview').attr('href', fileLink).attr('download', response.attach_file)
                                    .show();
                                $('#viewFileName').text(response.attach_file);
                            }
                        } else {
                            // Jika tidak ada file terlampir
                            $('#viewImagePreview').hide();
                            $('#viewFilePreview').hide();
                            $('#viewFileName').text('');
                        }

                        // Display modal
                        $('#viewInquiryModal').modal('show');
                    },
                    error: function(xhr) {
                        // Handle error
                        console.log(xhr.responseText);
                    }
                });
            }

            function showNoteAttachmentFields(actionType) {
                $('#action_type').val(actionType);
                $('#noteAttachmentFields').show();
                $('#initialButtons').hide();
            }

            function setActionType(actionType) {
                $('#action_type').val(actionType);
            }

            function showNoteAttachmentFields() {
                setActionType('validated');
                document.getElementById('noteAttachmentFields').style.display = 'block';
                document.getElementById('initialButtons').style.display = 'none';
            }
        </script>
    </main><!-- End #main -->
@endsection
