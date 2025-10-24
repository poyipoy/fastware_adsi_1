@extends('layout')

@section('content')
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Overview Inquiry Import</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Overview Inquiry Import</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Inquiry Import</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="overviewInquiryImportTable" class="table table-striped table-bordered align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Submitted By</th>
                                    <th>Kode Inquiry</th>
                                    <th>Kategori</th>
                                    <th>Supplier</th>
                                    <th>Customer</th>
                                    <th>Klasifikasi</th>
                                    <th>Status</th>
                                    <th>Ship To</th>
                                    <th>Last Update</th>
                                    <th>Est. Date</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#overviewInquiryImportTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('overviewInquiryImport') }}',
                    data: function (params) {
                        return params;
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'create_by', name: 'create_by', defaultContent: '-' },
                    { data: 'kode_inquiry', name: 'kode_inquiry', defaultContent: '-' },
                    { data: 'loc_imp', name: 'loc_imp', defaultContent: '-' },
                    {
                        data: 'supplier',
                        name: 'supplier',
                        defaultContent: '-',
                        render: function (data, type) {
                            if (!data) {
                                return type === 'display' ? '-' : '';
                            }

                            return type === 'display' ? data : data.replace(/<br\s*\/?>/gi, '\n');
                        }
                    },
                    {
                        data: 'customer_name',
                        name: 'customer.name_customer',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'klasifikasi',
                        name: 'detailinquiryimport.klasifikasi',
                        defaultContent: '-',
                        render: function (data, type) {
                            if (!data) {
                                return type === 'display' ? '-' : '';
                            }

                            return type === 'display' ? data : data.replace(/<br\s*\/?>/gi, '\n');
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            const css = data.status_class || 'btn-light';
                            const label = data.status_label || 'Unknown';
                            return '<span class="btn btn-sm ' + css + '">' + label + '</span>';
                        }
                    },
                    {
                        data: 'ship_to',
                        name: 'detailinquiryimport.ship',
                        orderable: false,
                        searchable: false,
                        render: function (data, type) {
                            if (type === 'display') {
                                return data || '--- No Shipping Options ---';
                            }

                            return data;
                        }
                    },
                    { data: 'last_update', name: 'latestPurchaseProgress.description', defaultContent: 'No updates yet' },
                    { data: 'est_date', name: 'est_date', defaultContent: '-' },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        defaultContent: ''
                    }
                ],
                order: [[2, 'desc']],
                lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]]
            });

            window.overviewInquiryImportTable = table;
        });
    </script>
@endsection
