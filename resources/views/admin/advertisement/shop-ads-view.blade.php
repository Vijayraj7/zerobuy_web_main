@extends('layouts.app')

@section('header-title', __('Advertisements'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h3>{{ __('Advertisements') }}</h3>
            <p class="text-muted">{{ __('Advertisement View') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-info">
                <i class="fas fa-wallet"></i>
                ₹{{ number_format($wallet?->balance ?? 0, 2) }}
            </a> 
        </div>
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">
            <h4 class="m-0 mt-4">{{ __('Advertisement View') }}</h4> 
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
                        <th>Ads ID</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Ads Type</th>
                        <th>Image</th>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Daily Budget</th>
                        <th>Total Budget</th>
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
        // ajax: "{{ route('admin.advertisement.shop.ads', $shop->id) }}",
        ajax: {
            url: "{{ route('admin.advertisement.shop.ads', $shop->id) }}",
            data: function (d) {
                d.start_date = $('#startDate').val();
                d.end_date   = $('#endDate').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'ads_id', name: 'id' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'ads_type', name: 'ads_type' },
            { data: 'product_image', orderable:false, searchable:false },
            { data: 'product_id', name: 'product_id' },
            { data: 'product_name', name: 'product_name' },
            { data: 'daily_budget', name: 'daily_budget' },
            { data: 'total_budget', name: 'total_budget' },
            { data: 'status', orderable:false, searchable:false },
            { data: 'actions', orderable:false, searchable:false }
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

$(document).on('click', '.delete-ad', function () {
    let adId = $(this).data('id');

    Swal.fire({
        title: 'Delete Advertisement?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (!result.isConfirmed) return;

        $.ajax({
            url: "{{ route('admin.advertisement.destroy', ':id') }}".replace(':id', adId),
            type: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function (res) {
                Swal.fire('Deleted!', res.message, 'success');
                $('#advertisementTable').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                Swal.fire(
                    'Error',
                    xhr.responseJSON?.message ?? 'Delete failed',
                    'error'
                );
            }
        });
    });
});

</script> 
@endpush
