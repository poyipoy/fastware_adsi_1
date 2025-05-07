@extends('layout')

@section('content')
    <main id="main" class="main">
        <section class="section">
            <h2>Tambah Data CRP</h2>

            <form action="{{ route('crp.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="category">Category:</label>
                    <input type="text" name="category" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="plan_actual">Plan / Actual:</label>
                    <select name="plan_actual" class="form-control" required>
                        <option value="Plan">Plan</option>
                        <option value="Actual">Actual</option>
                    </select>
                </div>
                <!-- Tambahkan input untuk bulan-bulan -->
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('crp') }}" class="btn btn-secondary">Batal</a>
            </form>
        </section>
    </main>
@endsection
