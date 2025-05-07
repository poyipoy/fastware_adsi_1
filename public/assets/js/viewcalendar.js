$(document).ready(function() {
    $('#calendar').fullCalendar({
        locale: 'id',
        defaultView: 'month',
        events: scheduleVisits.map(function(visit) {
            return {
                title: visit.name_customer,
                start: visit.schedule,
                end: visit.due_date,
                results: visit.results, // tambahkan deskripsi jika diperlukan
                pic: visit.pic, // tambahkan PIC
                schedule: visit.schedule, // tambahkan schedule
                duedate: visit.due_date, // tambahkan duedate
                customer_id: visit.customer_id // tambahkan customer_id
            };
        }),
        eventClick: function(calEvent, jsEvent, view) {
           // Menampilkan detail acara beserta nama pelanggan
           $('#eventModal #eventDetails').html(
            '<p><strong>' + calEvent.title + '</strong></p>' +
            '<p><strong>Tanggal:</strong> ' + calEvent.start.format('YYYY-MM-DD') + '</p>' +
            '<p><strong>Schedule:</strong> ' + calEvent.schedule + '</p>' +
            '<p><strong>Results:</strong> ' + (calEvent.results ? calEvent.results : 'Belum adanya hasil') + '</p>' +
            '<p><strong>Due Date:</strong> ' + calEvent.duedate + '</p>' +
            '<p><strong>PIC:</strong> ' + (calEvent.pic ? calEvent.pic : 'Belum adanya PIC') + '</p>'
        );
        $('#eventModal').modal('show');
        }
    });
});

