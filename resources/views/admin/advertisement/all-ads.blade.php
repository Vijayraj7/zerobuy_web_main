@extends('layouts.app')

@section('header-title', __('All Running Ads'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h3>{{ __('All Running Ads') }}</h3>
            <p class="text-muted">{{ __('All Running Ads Lists') }}</p>
        </div> 
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">
            <h4 class="m-0 mt-4">{{ __('All Running Ads') }}</h4> 
            <div class="d-flex flex-wrap align-items-center gap-3 mt-3 px-2">
                <!-- Date Filters -->
                <div class="d-flex align-items-center gap-2">
                    <label>From:</label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label>To:</label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                <!-- Reload Button -->
                <button id="reloadBtn" class="btn btn-secondary">
                    <i class="fa fa-rotate-right"></i>
                </button>
                <!-- Export Buttons -->
                <div id="exportButtons"></div>
            </div>
            <div class="table-responsive mt-3"> 
                <table id="advertisementTable" class="table table-bordered datatableCustomCSS">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Create Date</th>
                        <th>Store ID</th>
                        <th>Store Name</th>
                        <th>State</th>
                        <th>Total Products</th>
                        <th>Total Orders</th>
                        <th>Subscription</th>
                        <th>Ads Wallet</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>


        </div>
    </div>
</div> 

@endsection

@push('scripts')
<script>
    $(function () {
        // DATATABLE
        let table = $('#advertisementTable').DataTable({
            processing: true,
            serverSide: true,
            // ajax: "{{ route('admin.advertisement.allads') }}",
            ajax: {
                url: "{{ route('admin.advertisement.allads') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date   = $('#endDate').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'create_date', name: 'created_at' },
                { data: 'store_id', name: 'store_id' },
                { data: 'store_name', name: 'store_name' },
                { data: 'state', name: 'state' },
                { data: 'total_products', searchable: false },
                { data: 'total_orders', searchable: false },
                { data: 'subscription', searchable: false },
                { data: 'wallet_amount', searchable: false },
                { data: 'status', orderable: false, searchable: false },
                { data: 'actions', orderable: false, searchable: false }
            ]

        });
        $('#startDate, #endDate').on('change', function () {
            table.ajax.reload();
        });
        // Reload
        $('#reloadBtn').click(function () {
            startDate = endDate = "";
            filter = "all";
            $('#startDate,#endDate').val(''); 
            table.ajax.reload();
        });
    });
</script>
@endpush
