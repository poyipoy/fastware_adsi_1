@extends('layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Jumlah Karyawan Berdasarkan Departemen</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Departemen</th>
                                <th>Jumlah Karyawan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employeeCounts as $department => $count)
                            <tr>
                                <td>{{ $department }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
