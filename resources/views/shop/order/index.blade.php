@extends('layouts.app')

@section('header-title', __('Orders'))

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    {{ __('Orders') }}
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="cardTitleBox">
                <h5 class="card-title chartTitle">
                    {{ __('Orders Summary') }}
                </h5>
            </div>

            <div class="table-responsive mt-3">
                <table id="shopOrderTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Order Date') }}</th>
                            <th>{{ __('Order ID') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Mobile') }}</th>
                            <th>{{ __('Qty') }}</th>
                            <th>{{ __('Total Amount') }}</th>
                            <th>{{ __('Payment') }}</th>
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
        const initialStatus = "{{ $status ?? '' }}";

        $('#shopOrderTable').DataTable({
            responsive: true,
            processing: true,
            stateSave: false,
            paging: false,
            pageLength: -1,
            lengthMenu: [[-1, 10, 25, 50, 100], ['All', 10, 25, 50, 100]],
            ajax: {
                url: "{{ route('shop.order.api.index') }}",
                data: function (d) {
                    if (initialStatus) {
                        d.status = initialStatus;
                    }
                },
                dataSrc: 'data.orders'
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
                { data: 'created_at' },
                { data: 'order_id' },
                { data: 'customer_name' },
                { data: 'mobile_no' },
                { data: 'quantity' },
                { data: 'total_amount' },
                { data: 'payment_method' },
                {
                    data: 'status',
                    render: function (data) {
                        const statusClassMap = {
                            Pending: 'warning',
                            Accepted: 'info',
                            Confirmed: 'info',
                            Processing: 'primary',
                            Shipped: 'primary',
                            Out_for_Delivery: 'info',
                            Delivered: 'success',
                            Completed: 'success',
                            Cancelled: 'danger',
                            Canceled: 'danger',
                            Returned: 'secondary',
                            Refunded: 'secondary'
                        };
                        const key = (data || '').replace(/\s+/g, '_');
                        const badgeClass = statusClassMap[key] || 'secondary';
                        const label = data || '-';
                        return `<span class="badge rounded-pill text-bg-${badgeClass} text-white">${label}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const viewBtn = row.details_url
                            ? `<a href="${row.details_url}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ __('view order details') }}" class="circleIcon btn-outline-primary svg-bg">
                                    <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="icon" loading="lazy" />
                               </a>`
                            : '';
                        const invoiceBtn = row.invoice_url
                            ? `<a href="${row.invoice_url}" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ __('View Invoice') }}" class="circleIcon btn-outline-secondary">
                                    <img src="{{ asset('assets/icons-admin/download-alt.svg') }}" alt="icon" loading="lazy" />
                               </a>`
                            : '';
                        return `${viewBtn} ${invoiceBtn}`;
                    }
                }
            ]
        });
    });
</script>
@endpush
