@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
    <h4>{{ __('All Orders') }}</h4> 
</div>

<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body"> 
            <ul class="nav nav-tabs" id="statusTabs">
                <li class="nav-item">
                    <a href="#" data-status="" class="nav-link active">All</a>
                </li>

                @foreach(\App\Enums\OrderStatus::cases() as $status)
                    <li class="nav-item">
                        <a href="#" data-status="{{ $status->value }}" class="nav-link">
                            {{ $status->value }}
                        </a>
                    </li>
                @endforeach
            </ul>

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
                <table id="customerOrderTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Order Date') }}</th>
                            <th>{{ __('Order ID') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Store ID') }}</th>
                            <th>{{ __('Store Name') }}</th>
                            <th>{{ __('Customer Name') }}</th>
                            <th>{{ __('Mobile No') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Total Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
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
    $(function() { 
        let orderStatus = "";
        let startDate = "";
        let endDate = "";

        let table = $('#customerOrderTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.customer.orders', $customerId) }}",
                data: function(d) {
                    d.status = orderStatus; 
                    d.startDate = startDate;
                    d.endDate = endDate;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'order_id_display', name: 'order_id_display' },
                { data: 'first_product_image', name: 'first_product_image', orderable: false, searchable: false },
                { data: 'shop_id_display', name: 'shop_id_display' },
                { data: 'store_name', name: 'shop.name' },
                { data: 'customer_name', name: 'address.name' },
                { data: 'customer_phone', name: 'address.phone' },
                { data: 'total_quantity', orderable: true, searchable: false },
                { data: 'payable_amount', name: 'payable_amount', orderable: true },
                { data: 'order_status_badge', name: 'order_status', orderable: false },
                { data: 'actions', orderable: false, searchable: false },
            ],
            order: [[2, 'desc']],
            dom:'<"row"' + '<"col-md-6 d-flex align-items-center" l>' + '<"col-md-6 d-flex justify-content-end" f>' + '>' + 'rtip',
            // dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-primary',
                    text: '<i class="fas fa-download"></i> Export',
                    buttons: [
                        { extend: 'copy', text: '<i class="fas fa-copy"></i> Copy' },
                        { extend: 'csv', text: '<i class="fas fa-file-csv"></i> CSV' },
                        { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel' },
                        { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF' },
                        { extend: 'print', text: '<i class="fas fa-print"></i> Print' }
                    ]
                }
            ],
            initComplete: function () {
                table.buttons().container().appendTo('#exportButtons');
            },
        });

        $('#statusTabs a').click(function(e) {
            e.preventDefault();
            $('#statusTabs a').removeClass('active');
            $(this).addClass('active');
            orderStatus = $(this).data('status'); 
            table.ajax.reload(null, false); // reload without resetting pagination
        });
        // Auto Date Filtering
        $('#startDate, #endDate').change(function() {
            startDate = $('#startDate').val();
            endDate   = $('#endDate').val();
            table.ajax.reload(null, false);
        });

        // Reload Button
        $('#reloadBtn').click(function () {
            $('#startDate').val('');
            $('#endDate').val('');
            startDate = "";
            endDate = "";
            orderStatus = "";

            $('#statusTabs a').removeClass('active');
            $('#statusTabs a[data-status=""]').addClass('active');

            table.ajax.reload();
        });
    });
</script>


@endpush
