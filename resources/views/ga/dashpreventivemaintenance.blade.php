@extends('layout')

@section('content')
<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Tabel Preventif</h5>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="" class="display" style="table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Nomor Mesin</th>
                                        <th rowspan="2">Tipe</th>
                                        @for ($i = 1; $i <= 12; $i++) <th colspan="2">{{ \Carbon\Carbon::create(null, $i, null)->format('F') }}</th>
                                            @endfor
                                    </tr>
                                    <tr>
                                        @for ($i = 0; $i < 12; $i++) <th>Plan</th>
                                            <th>Actual</th>
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


@endsection