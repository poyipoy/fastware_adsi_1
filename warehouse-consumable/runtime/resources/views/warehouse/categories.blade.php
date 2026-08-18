@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-categories-title">
        <x-warehouse.page-header title="Kategori Barang Habis Pakai" subtitle="Kelola kategori yang dipakai pada master Warehouse.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.consumables.index') }}">Master Consumable</a>
        </x-warehouse.page-header>

        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="warehouse-panel" aria-labelledby="warehouse-category-add-title">
            <div class="warehouse-panel-header"><div><h2 id="warehouse-category-add-title">Tambah Kategori Cepat</h2><p>Kode dan nama harus unik sesuai aturan master.</p></div></div>
            <form method="POST" action="{{ route('warehouse.categories.store') }}" class="warehouse-panel-body">
                @csrf
                <div class="warehouse-detail-grid">
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="category-code">Kode</label><input id="category-code" class="form-control" name="code" value="{{ old('code') }}" required></div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="category-name">Nama</label><input id="category-name" class="form-control" name="name" value="{{ old('name') }}" required></div>
                    <div class="warehouse-form-field"><label class="form-label" for="category-description">Deskripsi</label><input id="category-description" class="form-control" name="description" value="{{ old('description') }}"></div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Tambah Kategori</button>
            </form>
        </section>

        <x-warehouse.panel title="Daftar kategori" class="warehouse-table-panel">
            <div class="warehouse-table-wrap"><table class="table warehouse-table align-middle" aria-label="Kategori barang habis pakai"><thead><tr><th scope="col">Kode</th><th scope="col">Nama</th><th scope="col">Deskripsi</th><th scope="col">Status</th></tr></thead><tbody>
                @forelse ($categories as $category)
                    <tr><td class="font-monospace">{{ $category->code }}</td><td>{{ $category->name }}</td><td>{{ $category->description ?: '—' }}</td><td><x-warehouse.status-badge :status="$category->is_active ? 'ACTIVE' : 'INACTIVE'" context="activity" /></td></tr>
                @empty
                    <tr><td colspan="4"><x-warehouse.empty-state title="Belum ada kategori" message="Tambahkan kategori untuk mengelompokkan master." /></td></tr>
                @endforelse
            </tbody></table></div>{{ $categories->links('pagination::warehouse-bootstrap-5') }}
        </x-warehouse.panel>
    </div>
@endsection
