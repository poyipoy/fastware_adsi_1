@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Form Elements</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item">Forms</li>
                <li class="breadcrumb-item active">Edit Issue</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->
    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Form Edit Issue {{$mesin->nama_mesin}}</h5>
                            <form id="IssueForm" action="{{ route('detailpreventives.updateIssue', $mesin->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <!-- Input issue -->
                                <div id="input-container">
                                    <!-- Input awal issue -->
                                    @foreach ($issues as $key => $issue)
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <input type="checkbox" name="checked[]" value="{{ $key }}" @if($checkedIssues[$key]==1) checked @endif disabled>
                                            </span>
                                            <input type="text" class="form-control" name="issue[]" value="{{ $issue }}">
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </form>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</main>

@endsection
