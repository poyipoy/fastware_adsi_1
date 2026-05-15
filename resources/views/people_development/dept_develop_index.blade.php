@extends('layout')

@section('content')
    <main id="main" class="main">

        <style>
            .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                border-radius: 50%;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
            }

            input:checked+.slider {
                background-color: #4CAF50;
            }

            input:checked+.slider:before {
                transform: translateX(26px);
            }

            .disabled {
                pointer-events: none;
                opacity: 0.6;
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Training Dept. Head</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Training Dept. Head</li>
                </ol>
            </nav>
        </div>

        <div class="container">
            @php
                $user = auth()->user(); // Ambil data pengguna yang sedang login
                $currentYear = now()->year; // Ambil tahun saat ini
                $nextYear = $currentYear + 1; // Hitung tahun depan
            @endphp

            <a href="{{ route('createPD') }}" id="trainingButton"
                class="btn btn-success {{ $buttonStatus ? '' : 'disabled' }}">
                Tambah Data Training
            </a>

            @if (in_array($user->role_id, [1, 3, 15]))
                <!-- Periksa role_id dan tahun -->
                <label class="switch">
                    <input type="checkbox" id="toggleSwitch" {{ $buttonStatus ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            @endif

            <!-- SweetAlert untuk Notifikasi -->
            @if (!empty($hasDoneStatus))
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Notifikasi!',
                            text: 'Isilah form evaluasi pada menu view',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                    });
                </script>
            @endif

            <table class="datatable table">
                <thead>
                    <tr>
                        <th scope="col">NO</th>
                        <th scope="col">PIC</th>
                        <th scope="col">Tahun Plan</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $index => $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td> <!-- Nomor urut otomatis -->
                            <td>{{ $item->modified_at }}</td> <!-- PIC dari modified_at -->
                            <td>{{ $item->tahun_aktual }}</td>
                            <td>
                                @if ($item->status_1 == 1)
                                    <span class="badge rounded-pill bg-secondary">Draf</span>
                                @elseif ($item->status_1 == 2)
                                    <span class="badge rounded-pill bg-warning">Menunggu Konfirmasi HRGA</span>
                                @elseif ($item->status_1 == 3)
                                    <span class="badge rounded-pill bg-success">Telah Disetujui</span>
                                @else
                                    <!-- Tambahkan opsi lain jika diperlukan -->
                                @endif
                            </td>
                            <td>
                                @if (!in_array($item->status_1, [2, 3, 4]))
                                    <a href="{{ route('editPdPengajuan', ['modified_at' => $item->modified_at, 'tahun_aktual' => $item->tahun_aktual]) }}"
                                        class="btn btn-warning" title="Edit Form"> <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="{{ route('sendPD', ['modified_at' => $item->modified_at, 'tahun_aktual' => $item->tahun_aktual]) }}"
                                        class="btn btn-sm btn-success" title="Kirim">
                                        <i class="fas fa-paper-plane"></i>
                                    </a>
                                @endif
                                <a href="{{ route('viewPD', ['modified_at' => $item->modified_at, 'tahun_aktual' => $item->tahun_aktual]) }}"
                                    class="btn btn-sm btn-info" title="View Form">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggleSwitch = document.getElementById('toggleSwitch');
                const trainingButton = document.getElementById('trainingButton');

                function applyButtonState(enabled) {
                    if (enabled) {
                        trainingButton.classList.remove('disabled');
                        trainingButton.style.pointerEvents = 'auto';
                    } else {
                        trainingButton.classList.add('disabled');
                        trainingButton.style.pointerEvents = 'none';
                    }
                }

                // Inisialisasi awal berdasarkan server-rendered class
                applyButtonState(!trainingButton.classList.contains('disabled'));

                if (toggleSwitch) {
                    // Admin: toggle mengontrol button dan update server
                    toggleSwitch.addEventListener('change', function() {
                        const isChecked = toggleSwitch.checked;

                        applyButtonState(isChecked);

                        fetch('{{ route('updateButtonStatus') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ enabled: isChecked })
                        }).then(response => {
                            if (!response.ok) throw new Error('HTTP ' + response.status);
                            return response.json();
                        })
                          .then(data => {
                              console.log('Status updated:', data);
                              alert('Button status berhasil diubah: ' + (isChecked ? 'ON' : 'OFF'));
                          })
                          .catch(error => {
                              console.error('Error:', error);
                              alert('GAGAL update button status! Error: ' + error.message);
                              // Revert toggle jika gagal
                              toggleSwitch.checked = !isChecked;
                              applyButtonState(!isChecked);
                          });
                    });
                }
            });
        </script>
    </main><!-- End #main -->
@endsection
