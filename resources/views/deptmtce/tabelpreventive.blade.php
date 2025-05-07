@extends('layout')

@section('content')
<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Tabel Preventif Dept.Head Maintenance</h5>
                        </div>
                        <div>
                            <a class="btn btn-primary btn-lg" href="{{ route('preventives.create') }}">
                                <i class="bi bi-plus"></i> Tambah Preventif
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Nomor Mesin</th>
                                        <th rowspan="2">Tipe</th>
                                        @php
    \Carbon\Carbon::setLocale('id');
@endphp
@for ($i = 1; $i <= 12; $i++)
<th colspan="2">{{ \Carbon\Carbon::create(null, $i, null)->translatedFormat('F') }}</th>
@endfor
                                    </tr>
                                    <tr>
                                        @for ($i = 0; $i < 12; $i++) <th>Rencana</th>
                                            <th>Aktual</th>
                                            @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $prevMesin = null;
                                    $prevMonth = null;
                                    @endphp
                                    @foreach ($mesins as $mesin)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $mesin->no_mesin }}</td>
                                        <td>{{ $mesin->tipe }}</td>
                                        @for ($i = 1; $i <= 12; $i++) @php $preventif=$mesin->preventifs()->where('jadwal_rencana', 'like', date('Y-m', strtotime("$i/01/".date('Y'))).'%')->first();
                                            @endphp
                                            <td>
                                                @if($preventif)
                                                <a class="btn btn-primary" href="{{ route('preventives.lihatpreventive', $preventif->id) }}">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if($preventif && $preventif->jadwal_aktual)
                                                {{$preventif->jadwal_aktual}}
                                                @endif
                                            </td>
                                            @endfor
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
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(function() {
        $('table').on('click', 'td.editable.actual', function(e) {
            var $this = $(this);
            var currentValue = $this.text().trim();

            // Buat elemen input untuk mengedit tanggal
            var $input = $('<input>', {
                type: 'date',
                value: currentValue
            });

            // Ganti nilai dengan elemen input
            $this.html($input);

            // Fokus pada input
            $input.focus();

            // Reaksi ketika input kehilangan fokus
            $input.on('blur', function() {
                var newValue = $(this).val().trim();

                // Perbarui nilai hanya jika berbeda
                if (newValue !== currentValue) {
                    $this.text(newValue);
                    // Panggil fungsi untuk menyimpan ke database
                    saveToDatabase(newValue, $this);
                }
            });
        });

        // Fungsi untuk menyimpan ke database
        function saveToDatabase(newValue, $element) {
            var rowData = $element.closest('tr').find('td').map(function() {
                return $(this).text().trim();
            }).get();

            var data = {
                nomor_mesin: rowData[1], // Menggunakan nomor_mesin sebagai identifikasi
                newValue: newValue
            };

            // Kirim data ke server menggunakan AJAX
            $.ajax({
                url: '{{ route("updatePreventive") }}', // Sesuaikan dengan route Anda
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('Data berhasil disimpan ke database');
                    // Tampilkan notifikasi SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses!',
                        text: 'Data berhasil disimpan.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Terjadi kesalahan:', error);
                }
            });
        }
    });
</script>

@endsection
