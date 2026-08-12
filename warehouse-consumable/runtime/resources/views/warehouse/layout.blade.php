@extends('layout')

@section('bodyClass', 'warehouse-page')

@push('styles')
    @vite('resources/css/warehouse/foundation.css')
@endpush

@section('content')
    <main class="warehouse-shell" data-warehouse-shell>
        @yield('warehouse-content')
    </main>
@endsection
