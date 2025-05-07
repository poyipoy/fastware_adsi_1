@extends('layout')

@section('content')

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Menu Handling</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Handling</li>

                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tampilan Data Handling</h5>
                            @if (Auth::user()->role_id == 1 ||
                                    Auth::user()->role_id == 2 ||
                                    Auth::user()->role_id == 3 ||
                                    Auth::user()->role_id == 4)
                                <div class="card-header" style="margin-bottom: 20px;">
                                    <a href="{{ route('create') }}" class="btn btn-success btn-sm" style="font-size: 20px;">
                                        <i class="fas fa-plus"></i> Tambah Data
                                    </a>
                                </div>
                            @endif
                            @if (Auth::user()->role_id == 1 ||
                                    Auth::user()->role_id == 2 ||
                                    Auth::user()->role_id == 3 ||
                                    Auth::user()->role_id == 4 ||
                                    Auth::user()->role_id == 11 ||
                                    Auth::user()->role_id == 12 ||
                                    Auth::user()->role_id == 13 ||
                                    Auth::user()->role_id == 14)
                                <!-- Table with stripped rows -->
                                <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                    <table class="datatable table">
                                        <thead>
                                            <tr>
                                                <th class="text-center" width="50px">NO</th>
                                                <th class="text-center" width="100px">NO WO</th>
                                                <th class="text-center" width="100px">PIC</th>
                                                <th class="text-center" width="100px">Kode Pelanggan</th>
                                                <th class="text-center" width="150px">Nama Pelanggan</th>
                                                <th class="text-center" width="100px">Area Pelanggan</th>
                                                <th class="text-center" width="100px">Request</th>
                                                <th class="text-center" width="100px">Tipe Bahan</th>
                                                <th class="text-center" width="105px">Kategori (NG)</th>
                                                <th class="text-center" width="105px">Jenis Test</th>
                                                <th class="text-center" width="100px">Tipe proses</th>
                                                <th class="text-center" width="95px">Jenis 1</th>
                                                <th class="text-center" width="95px">Jenis 2</th>
                                                <th class="text-center" width="100px">Pembaruan Terakhir</th>
                                                <th class="text-center" width="100px">Status</th>
                                                <th class="text-center" width="100px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data as $row)
                                                <tr>
                                                    <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                    <td class="text-center py-3">{{ $row->no_wo }}</td>
                                                    <td class="text-center py-3">{{ $row->user->name ?? '' }}</td>
                                                    <td class="text-center py-3">{{ $row->customers->customer_code ?? '' }}
                                                    </td>
                                                    <td class="text-center py-3">{{ $row->customers->name_customer ?? '' }}
                                                    </td>
                                                    <td class="text-center py-3">{{ $row->customers->area ?? '' }}</td>
                                                    <td class="text-center py-3">{{ $row->notes }}</td>
                                                    <td class="text-center py-3">
                                                        {{ $row->type_materials->type_name ?? $row->nama_barang }}
                                                    </td>
                                                    <td class="text-center py-3">{{ $row->category }}</td>
                                                    <td class="text-center py-3">{{ $row->jenis_test }}</td>
                                                    <td class="text-center py-3">{{ $row->process_type }}</td>
                                                    <td class="text-center py-3">{{ $row->type_1 }}</td>
                                                    <td class="text-center py-3">{{ $row->type_2 }}</td>
                                                    <td class="text-center py-3">{{ $row->created_at }}</td>
                                                    <td class="text-center py-4"
                                                        style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        @if ($row->status == 0)
                                                            <span class="badge bg-danger align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif ($row->status == 1)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif($row->status == 2)
                                                            <span class="badge bg-info align-items-center"
                                                                style="font-size: 18px;">Finish</span>
                                                        @elseif($row->status == 3)
                                                            <span class="badge bg-success align-items-center"
                                                                style="font-size: 18px;">Close</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center py-4">
                                                        <form id="deleteForm-{{ $row->id }}"
                                                            action="{{ route('delete', $row->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            @if ($row->status != 1 && $row->status != 2 && $row->status != 3)
                                                                @if (Auth::user()->role_id == 1 ||
                                                                        Auth::user()->role_id == 2 ||
                                                                        Auth::user()->role_id == 3 ||
                                                                        Auth::user()->role_id == 4)
                                                                    <a href="{{ route('edit', $row->id) }}"
                                                                        class="btn btn-sm btn-primary" title="Ubah Data">
                                                                        <i class="fa-solid fa-edit fa-1x"></i>
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-danger"
                                                                        title="Hapus"
                                                                        onclick="confirmDelete({{ $row->id }})">
                                                                        <i class="fas fa-trash fa-1x"></i>
                                                                    </button>
                                                                @endif
                                                                @if (Auth::user()->role_id == 1 ||
                                                                        Auth::user()->role_id == 11 ||
                                                                        Auth::user()->role_id == 12 ||
                                                                        Auth::user()->role_id == 13 ||
                                                                        Auth::user()->role_id == 14)
                                                                    <a href="{{ route('showHistory', $row->id) }}"
                                                                        class="btn btn-sm btn-success"
                                                                        title="Lihat Progres">
                                                                        <i class="fa fa-eye fa-1x" aria-hidden="true"></i>
                                                                    </a>
                                                                @endif
                                                            @elseif($row->status == 1)
                                                                <a href="{{ route('showHistory', $row->id) }}"
                                                                    class="btn btn-sm btn-warning">
                                                                    <i class="fa fa-eye fa-1x" aria-hidden="true"></i>
                                                                </a>
                                                            @elseif ($row->status == 2)
                                                                @if (Auth::user()->role_id == 1 ||
                                                                        Auth::user()->role_id == 2 ||
                                                                        Auth::user()->role_id == 3 ||
                                                                        Auth::user()->role_id == 4)
                                                                    <button type="button" class="btn btn-sm btn-success"
                                                                        title="CLose Proses"
                                                                        onclick="confirmStatusChange({{ $row->id }})">
                                                                        <i class="fa fa-window-close fa-1x"></i>
                                                                    </button>
                                                                @endif
                                                                @if (Auth::user()->role_id == 1 ||
                                                                        Auth::user()->role_id == 2 ||
                                                                        Auth::user()->role_id == 3 ||
                                                                        Auth::user()->role_id == 4 ||
                                                                        Auth::user()->role_id == 14)
                                                                    <a href="{{ route('showHistory', $row->id) }}"
                                                                        class="btn btn-sm btn-warning">
                                                                        <i class="fa fa-eye fa-1x" aria-hidden="true"></i>
                                                                    </a>
                                                                @endif
                                                            @elseif($row->status == 3)
                                                                <a href="{{ route('showHistory', $row->id) }}"
                                                                    class="btn btn-sm btn-warning">
                                                                    <i class="fa fa-eye fa-1x" aria-hidden="true"></i>
                                                                </a>
                                                            @else
                                                                <button type="button"
                                                                    class="btn btn-sm btn-primary disabled"
                                                                    title="Ubah Data" disabled>
                                                                    <i class="fa-solid fa-edit fa-1x"
                                                                        aria-hidden="true"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-danger disabled" title="Hapus"
                                                                    disabled>
                                                                    <i class="fas fa-trash fa-1x" aria-hidden="true"></i>
                                                                </button>
                                                            @endif
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
        <script>
            //datatabelSales
            $(document).ready(function() {
                new DataTable('#viewSales');
            });

            function confirmStatusChange(id) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Anda ingin Close proses ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Iya',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna menekan tombol "Iya", kirim permintaan POST dengan metode spoofing ke endpoint changeStatus
                        changeStatus(id);
                    }
                });
            }

            function changeStatus(id) {
                $.ajax({
                    type: 'POST', // Menggunakan POST dengan spoofing metode PATCH
                    url: '{{ route('changeStatus', ['id' => '__id__']) }}'.replace('__id__',
                        id), // URL endpoint dengan parameter id
                    data: {
                        _token: '{{ csrf_token() }}', // Token CSRF untuk keamanan
                        _method: 'PATCH' // Metode HTTP yang digunakan adalah PATCH
                    },
                    success: function(response) {
                        Swal.fire('Berhasil!', 'Proses telah close.', 'success')
                            .then(() => {
                                window.location.href = '{{ route('index') }}';
                            });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error!', 'There was an error changing the status.', 'error');
                    }
                });
            }

            function confirmDelete(id) {
                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Menampilkan loading sebelum penghapusan
                        Swal.fire({
                            title: 'Menghapus data...',
                            text: 'Mohon tunggu',
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            onBeforeOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Menunggu 2 detik sebelum melanjutkan penghapusan
                        setTimeout(() => {
                            // Mengirim permintaan penghapusan
                            fetch("{{ route('delete', '') }}/" + id, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            }).then(response => {
                                return response.json(); // Mengonversi respons menjadi JSON
                            }).then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Data berhasil dihapus!',
                                        text: data.success,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        // Muat ulang halaman atau lakukan tindakan lain
                                        location.reload();
                                    });
                                } else {
                                    throw new Error('Penghapusan gagal');
                                }
                            }).catch(error => {
                                Swal.fire({
                                    title: 'Gagal menghapus data',
                                    text: error.message,
                                    icon: 'error'
                                });
                            });
                        }, 2000); // Menunggu 2 detik
                    }
                });
            }
        </script>

    </main><!-- End #main -->
@endsection
