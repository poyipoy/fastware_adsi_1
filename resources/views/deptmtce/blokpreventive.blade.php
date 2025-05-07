@extends('layout')

<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />


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
                        <div>
                            <a class="btn btn-primary btn-lg" href="{{ route('events.create') }}">
                                <i class="bi bi-plus"></i> Tambah Event
                            </a>
                        </div>
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
                var startTime = moment(event.start).format('h:mm A');
                var eventTime = startTime;

                // Append event time to the event title
                element.find('.fc-title').append('<div class="fc-time">' + eventTime + '</div>');
            },
            eventClick: function(event) {
                window.location.href = SITEURL + '/events/edit/' + event.id;
            },
            eventDisplay: 'block',
            eventBackgroundColor: function(event) {
                return event.color;
            },
            eventTextColor: function(event) {
                return event.textColor;
            },
            slotEventOverlap: false, // Set to false to prevent events from overlapping
            slotDuration: '00:15:00' // Set the duration of each time slot, in this case 15 minutes
        });

    });
</script>
@endsection