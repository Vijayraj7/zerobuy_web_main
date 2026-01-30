@extends('layouts.app')

@section('header-title', __('Subscription Store Lists'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h3>{{ __('Subscription Stores') }}</h3>
            <p class="text-muted">{{ __('All Subscription Store Lists') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary">
                <h5 class="text-white mt-2">Active Subscription Stores</h5> 
                <div class="text-white">{{ $activeCount }}</div>
            </a> 
            <a class="btn btn-warning"> 
                <h5 class="text-white mt-2">Expired Subscription Stores</h5>
                <div class="text-white">{{ $expiredCount }}</div>
            </a>
        </div>
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">
            <h4 class="m-0 mt-4">{{ __('All Subscription Store Lists') }}</h4> 
            <ul class="nav nav-tabs mt-3" id="statusTabs">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-filter="all">All</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-filter="Active">Active</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-filter="Expired">Expired</a>
                </li> 
            </ul>

            <div class="table-responsive mt-3"> 
                <table id="subscriptionsTable" class="table table-bordered datatableCustomCSS">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Activation Date</th>
                        <th>Store ID</th>
                        <th>Store Name</th>
                        <th>Subscription Plan</th>
                        <th>Validity</th>
                        <th>Expiry Date</th>
                        <th>Remaining Days</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
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
    let statusFilter = 'all';

    let table = $('#subscriptionsTable').DataTable({
        processing: true,
        serverSide: true, 
        // url: "{{ route('admin.subscription-plan.subscription.all-list') }}",
        ajax: {
            url: "{{ route('admin.subscription-plan.subscription.all-list') }}",
            data: function (d) {
                d.status = statusFilter; // send tab filter
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'activation_date' },
            { data: 'shop_id', name: 'shop_id' },
            { data: 'shop_name', name: 'shop.name' },
            { data: 'subscription_plan_name', name: 'plan.name' },
            { data: 'plan_validity', searchable:false },
            { data: 'expiry_date' },
            { data: 'remaining_days', searchable:false },
            { data: 'price' },
            { data: 'status', orderable:false },
            { data: 'actions', orderable:false, searchable:false },
        ]
    }); 

    // TAB CLICK FILTER
    $('#statusTabs .nav-link').on('click', function (e) {
        e.preventDefault();

        $('#statusTabs .nav-link').removeClass('active');
        $(this).addClass('active');

        statusFilter = $(this).data('filter'); // all | Active | Expired
        table.ajax.reload();
    });

});


$(document).on('click', '.historyBtn', function () {
    let shopId = $(this).data('id');

    $.get(
        "{{ route('admin.subscription-plan.subscription.history', '') }}/" + shopId,
        function (data) {

            let rows = '';
            data.forEach((row, i) => {
                rows += `
                <tr>
                    <td>${i+1}</td>
                    <td>${row.activation_date}</td>
                    <td>${row.plan}</td>
                    <td>${row.validity}</td>
                    <td>${row.remaining_days}</td>
                    <td>${row.expiry_date}</td>
                    <td>${row.amount}</td>
                    <td>${row.status}</td>
                </tr>`;
            });

            $('#historyTable tbody').html(rows);
            $('#historyModal').modal('show');
        }
    );
});

</script>
@endpush
