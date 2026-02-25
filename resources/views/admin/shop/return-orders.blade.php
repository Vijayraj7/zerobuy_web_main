@extends('layouts.app')

@section('header-title', __('Return Orders'))

@section('content')

<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body"> 
            @include('admin.shop.header-nav')
            <h4 class="m-0 mt-4">{{ __('Return Orders') }}</h4>
            <ul class="nav nav-tabs mt-1" id="statusTabs">
                <li class="nav-item">
                    <a href="#" data-status="" class="nav-link active">All</a>
                </li>

                @foreach(\App\Enums\ReturnOderStatus::visibleStatuses() as $status)
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
                <table id="reurnOrderTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr>
                            <th>#</th> 
                            <th>{{ __('Return Date') }}</th>
                            <th>{{ __('Return Code') }}</th>
                            <th>{{ __('Order Code') }}</th>
                            <th>{{ __('Order Date') }}</th> 
                            <th>{{ __('Customer Name') }}</th>
                            <th>{{ __('Mobile No') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Reason') }}</th>
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
        let returnStatus = "";
        let startDate = "";
        let endDate = "";

        let table = $('#reurnOrderTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.shop.return', $shop->id) }}",
                data: function(d) {
                    d.status = returnStatus; 
                    d.startDate = startDate;
                    d.endDate = endDate;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },          
                { data: 'return_code', name: 'return_code' }, 
                { data: 'order_code', name: 'order_code' }, 
                { data: 'order_date', name: 'order_date' }, 
                { data: 'customer_name', name: 'customer_name' },
                { data: 'customer_phone', name: 'customer_phone' },

                { data: 'quantity', orderable: false },
                { data: 'amount', orderable: false },
                { data: 'reason', name: 'reason' },
                { data: 'amount', name: 'amount' }, 
                { data: 'status_badge', orderable: false, searchable: false },
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
            returnStatus = $(this).data('status'); 
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
            returnStatus = "";

            $('#statusTabs a').removeClass('active');
            $('#statusTabs a[data-status=""]').addClass('active');

            table.ajax.reload();
        });
    });
</script>

@endpush