blade
Salin kode
@extends('layout')

@section('content')
    <main id="main" class="main">
        <section>
            <div class="pagetitle">
                <h1>Menu Jadwal Kunjungan</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Menu Jadwal Kunjungan</li>
                    </ol>
                </nav>
            </div><!-- End Page Title -->
            <section class="section">
                <div class="row">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Jadwal Kunjungan</h5>
                            <div id='calendar'></div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Modal -->
            <div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="eventModalLabel">Detail Jadwal Kunjungan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="eventDetails"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    </main><!-- End #main -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var scheduleVisits = {!! json_encode($scheduleVisits) !!};
            $('#calendar').fullCalendar({
                locale: 'id',
                defaultView: 'month',
                events: scheduleVisits.map(function(visit) {
                    return {
                        title: visit.name_customer,
                        start: visit.schedule,
                        end: visit.due_date,
                        results: visit.results,
                        pic: visit.pic,
                        schedule: visit.schedule,
                        duedate: visit.due_date,
                        customer_id: visit.customer_id
                    };
                }),
                eventClick: function(calEvent, jsEvent, view) {
                    $('#eventModal #eventDetails').html(
                        '<p><strong>' + calEvent.title + '</strong></p>' +
                        '<p><strong>Tanggal:</strong> ' + moment(calEvent.start).format('YYYY-MM-DD') + '</p>' +
                        '<p><strong>Jadwal Kunjungan:</strong> ' + calEvent.schedule + '</p>' +
                        '<p><strong>Catatan Hasil:</strong> ' + (calEvent.results ? calEvent.results : 'Belum adanya hasil') + '</p>' +
                        '<p><strong>Batas Akhir:</strong> ' + calEvent.duedate + '</p>' +
                        '<p><strong>PIC:</strong> ' + (calEvent.pic ? calEvent.pic : 'Belum adanya PIC') + '</p>'
                    );
                    $('#eventModal').modal('show');
                }
            });
        });
    </script>
@endsection