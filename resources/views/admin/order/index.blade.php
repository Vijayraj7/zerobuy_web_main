@extends('layouts.app')

@section('header-title', __('Orders'))

@section('content')
<div class="card">
    <div class="card-body">

        @php
            $activeStatus = request('status');
        @endphp
        {{-- Status Tabs --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ empty($activeStatus) ? 'active' : '' }}" href="javascript:void(0)" data-status="">
                    {{ __('All') }}
                </a>
            </li>
            @foreach (\App\Enums\OrderStatus::cases() as $status)
                <li class="nav-item">
                    <a class="nav-link {{ $activeStatus == $status->value ? 'active' : '' }}" href="javascript:void(0)"
                       data-status="{{ $status->value }}">
                        {{ $status->value }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="table-responsive">
            <table class="table table-bordered w-100 datatableCustomCSS" id="orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Create Date</th>
                        <th>Order ID</th>
                        <th>Store ID</th>
                        <th>Store Name</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Qty</th>
                        <th>Total Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let defaultStatus = "{{ request('status') }}";
    let table = $('#orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.order.index') }}",
            data: function (d) {
                d.status = $('.nav-tabs .active').data('status') ?? defaultStatus;
                //d.status = $('.nav-tabs .active').data('status');
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'created_date', name: 'created_at' },
            { data: 'order_id', name: 'order_code' },
            { data: 'store_id', name: 'shop_id' },
            { data: 'store_name', name: 'shop.name' },
            { data: 'customer_name', name: 'customer.user.name' },
            { data: 'mobile', name: 'customer.phone' },
            { data: 'quantity', searchable: false },
            { data: 'total_amount', name: 'payable_amount' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'status', name: 'order_status' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('.nav-tabs a').on('click', function () {
        $('.nav-tabs a').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });

});
</script>
@endpush
