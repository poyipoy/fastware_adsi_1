@extends('layout')

@section('content')
    <div class="container">
        <h2>Edit Data CRP</h2>

        <form action="{{ route('crp.update', $mstDboCrp->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="category">Category:</label>
                <input type="text" name="category" value="{{ $mstDboCrp->category }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="plan_actual">Plan / Actual:</label>
                <select name="plan_actual" class="form-control" required>
                    <option value="Plan" {{ $mstDboCrp->plan_actual == 'Plan' ? 'selected' : '' }}>Plan</option>
                    <option value="Actual" {{ $mstDboCrp->plan_actual == 'Actual' ? 'selected' : '' }}>Actual</option>
                </select>
            </div>
            <!-- Tambahkan input untuk bulan-bulan jika perlu -->
            <button type="submit" class="btn btn-warning">Perbarui</button>
            <a href="{{ route('crp') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
