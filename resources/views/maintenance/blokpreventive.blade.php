@extends('layout')

<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')
<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">List Data Blok Preventive</h5>
                        </div>
                        <!-- <div>
                            <a class="btn btn-primary btn-lg" href="{{ route('events.create') }}">
                                <i class="bi bi-plus"></i> Tambah Event
                            </a>
                        </div> -->
                        <form method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="file" class="form-control">
                            <br>
                            <button class="btn btn-success">
                                Import Preventive
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Calendar -->
                        <div id='calendar'></div>
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


@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        var SITEURL = "{{ url('/') }}";

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var calendar = $('#calendar').fullCalendar({
            editable: true,
            events: SITEURL + "/full-calender",
            displayEventTime: false,
            eventRender: function(event, element, view) {
                // Combine nama_mesin and nomor_baru in the event title
                var titleText = event.nama_mesin + ' - ' + event.no_mesin;

                // Set the event title with combined text
                element.find('.fc-title').text(titleText);

                // Add event time if needed
                var eventTime;
                if (event.status === 0) {
                    var startTime = moment(event.schedule_plan).format('h:mm A');
                    eventTime = 'Start: ' + startTime;
                } else if (event.status === 1) {
                    var endTime = moment(event.actual_plan).format('h:mm A');
                    eventTime = 'End: ' + endTime;
                }

                // Append event time to the event title
                element.find('.fc-title').append('<div class="fc-time">' + eventTime + '</div>');

                // Set event background color and text color based on status
                if (event.status === '0') {
                    element.css('background-color', 'yellow');
                    element.css('color', 'black');
                } else if (event.status === '1') {
                    element.css('background-color', 'green');
                    element.css('color', 'white');
                }
            },
            eventClick: function(event) {
                window.location.href = SITEURL + '/events/editMaintenance/' + event.id;
            },
            eventDisplay: 'block',
            slotEventOverlap: false, // Set to false to prevent events from overlapping
            slotDuration: '00:15:00' // Set the duration of each time slot, in this case 15 minutes
        });
    });
</script>
@endsection

<!-- BUAT IMPORT
<form method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" class="form-control">
    <br>
    <button class="btn btn-success">
        Import Preventive
    </button>
</form>
-->