<!DOCTYPE html>
<html>

<head>
    <title>PDF Kerusakan Mesin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        /* Tambahkan CSS khusus untuk desain kolom hitam dan teks putih pada elemen <th> */
        .table th {
            background-color: #000000;
            color: #ffffff;
        }
    </style>
</head>

<body>
    <h1>{{ $title }}</h1>
    <p>{{ $date }}</p>
    <p>Berikut merupakan List Kerusakan Mesin berdasarkan Nomor Mesin</p>

    <table class="table table-bordered">
        <tr>
            <th>ID FPP</th>
            <th>Section</th>
            <th>Mesin</th>
            <th>Lokasi</th>
            <th>Kendala</th>
            <th>Status</th>
        </tr>
        @foreach($formperbaikans as $formperbaikan)
        <tr>
            <td>{{ $formperbaikan->id_fpp }}</td>
            <td>{{ $formperbaikan->section }}</td>
            <td>{{ $formperbaikan->mesin }}</td>
            <td>{{ $formperbaikan->lokasi }}</td>
            <td>{{ $formperbaikan->kendala }}</td>
            <td>{{ $formperbaikan->status }}</td>
        </tr>
        @endforeach
    </table>

</body>

</html>