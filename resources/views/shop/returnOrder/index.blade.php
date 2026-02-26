@extends('layouts.app')

@section('header-title', __('Orders'))

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive mt-3">

                <table id="returnOrderTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Return Date') }}</th>
                            <th>{{ __('Order ID') }}</th>
                            <th>{{ __('Return ID') }}</th>
                            <th>{{ __('Order Date') }}</th>
                            <th>{{ __('Customer Name') }}</th>
                            <th>{{ __('Mobile No') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Total Amount') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
    $(function () {
        const table = $('#returnOrderTable').DataTable({
            responsive: true,
            processing: true,
            stateSave: false,
            paging: false,
            pageLength: -1,
            lengthMenu: [[-1, 10, 25, 50, 100], ['All', 10, 25, 50, 100]],
            ajax: {
                url: "{{ route('shop.returnOrder.api.index') }}",
                dataSrc: 'data.return_orders'
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'thumbnail',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        const src = data || "{{ asset('default/default.jpg') }}";
                        return `<img src="${src}" alt="product" width="40" height="40" class="rounded" loading="lazy">`;
                    }
                },
                {
                    data: 'return_date',
                    render: function (data) {
                        if (!data) return '-';
                        const parts = data.split(',');
                        const datePart = (parts[0] || '').trim();
                        const timePart = (parts[1] || '').trim();
                        return `<div>${datePart}</div><div class="small text-muted">${timePart}</div>`;
                    }
                },
                { data: 'order_id' },
                { data: 'return_code' },
                {
                    data: 'order_date',
                    render: function (data) {
                        if (!data) return '-';
                        const parts = data.split(',');
                        const datePart = (parts[0] || '').trim();
                        const timePart = (parts[1] || '').trim();
                        return `<div>${datePart}</div><div class="small text-muted">${timePart}</div>`;
                    }
                },
                { data: 'customer_name' },
                { data: 'mobile_no' },
                { data: 'quantity' },
                { data: 'amount' },
                { data: 'total_amount' },
                {
                    data: 'reason',
                    render: function (data, type, row) {
                        if (type === 'display' && data) {
                            return `<span class="text-danger">${data}</span>`;
                        }
                        return data ?? '';
                    }
                },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        return `<span class="badge rounded-pill text-bg-${row.status_class} text-white">${data}</span>`;
                    }
                },
                {
                    data: 'details_url',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `<a href="${data}" class="circleIcon svg-bg" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ __('view details') }}">
                            <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="icon" loading="lazy" />
                        </a>`;
                    }
                }
            ]
        });

        const initialStatus = "{{ request('status') }}";
        if (initialStatus) {
            table.column(11).search('^' + initialStatus + '$', true, false).draw();
        } else {
            table.search('').columns().search('').draw();
        }
    });
</script>
@endpush
