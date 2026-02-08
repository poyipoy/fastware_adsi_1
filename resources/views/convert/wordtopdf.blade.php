@extends('layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Convert Word to PDF</h3>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('convert.wordtopdf.post') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="files">Select Word Files (DOC/DOCX)</label>
                            <input type="file" class="form-control-file" id="files" name="files[]" multiple required accept=".doc,.docx">
                            <small class="form-text text-muted">You can select multiple files. Maximum 10MB per file.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Convert to PDF</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
