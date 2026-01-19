@extends('layouts.app')
@section('header-title', __('Product List'))

@section('content')
    <div>
        <h4>{{ __('Product List') }}</h4>
    </div>

    <form action="" method="GET" class="card card-body">

        @if (request('approve'))
            <input type="hidden" name="approve" value="{{ request('approve') }}">
        @else
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="row">
            <div class="col-lg-4 col-md-6 mb-3">
                <x-select label="Shop" name="shop">
                    <option value="">
                        {{ __('All Shop') }}
                    </option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>
        </div>

        <div class="mt-2 d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.product.index', [
                'status' => request()->query('status'),
                'approve' => request()->query('approve'),
            ]) }}"
                class="btn btn-light py-2 px-4">
                {{ __('Reset') }}
            </a>
            <button type="submit" class="btn btn-primary py-2 px-4">
                {{ __('Filter Data') }}
            </button>
        </div>
    </form>

    <div class="container-fluid mt-3">

        <div class="mb-3 card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table border-left-right table-responsive-lg datatableCustomCSS" id="productTable">
                        <thead>
                            <tr>
                                <!-- <th>{{ __('SL') }}.</th>
                                <th>{{ __('Thumbnail') }}</th>
                                <th style="min-width: 150px">{{ __('Product Name') }}</th>
                                <th style="min-width: 100px">{{ __('Shop') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th style="min-width: 120px">{{ __('Discount Price') }}</th>
                                <th>{{ __('Action') }}</th> -->

                                <th>SL</th>
                                <th>Create Date</th>
                                <th>Product ID</th>
                                <th>Store ID</th>
                                <th>Store Name</th>
                                <th>Product Name</th>
                                <th>Image</th>
                                <th>Qty</th>
                                <th>MRP</th>
                                <th>Selling Price</th>
                                <th>Total Sales</th>
                                <th>Variants</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead> 
                    </table>
                </div>
            </div>
        </div> 

        <form action="" method="POST" class="d-none" id="deleteForm">
            @csrf
            @method('DELETE')
        </form>

    </div>
@endsection

@push('scripts')
    <script> 

        $('#productTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('admin.product.index') }}",
                data: function (d) {
                    d.shop    = $('select[name=shop]').val();
                    d.status  = "{{ request('status') }}";
                    d.approve = "{{ request('approve') }}";
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable:false, searchable:false },
                { data: 'created_date' },
                { data: 'product_code' },
                { data: 'store_code' },
                { data: 'shop', name:'shop.name' },
                { data: 'name', name:'name' },
                { data: 'thumbnail', orderable:false, searchable:false },
                { data: 'quantity' },
                { data: 'mrp' },
                { data: 'selling_price' }, 
                { data: 'total_sale_count', orderable:false, searchable:false },
                { data: 'variants_count', orderable:false, searchable:false },
                { data: 'status', orderable:false, searchable:false },
                { data: 'action', orderable:false, searchable:false }
            ]
        });


        $(".confirmApprove").on("click", function(e) {
            e.preventDefault();
            const url = $(this).attr("href");
            Swal.fire({
                title: "Are you sure?",
                text: "You want to approve this product",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, Approve it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });

        const confirmDeny = (id) => {
            const form = document.getElementById('deleteForm');
            form.action = `{{ route('admin.product.destroy', ':id') }}`.replace(':id', id);
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete this product! If you confirm, it will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Yes, Delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        $(document).on('change', '.toggle-status', function () {
            let id = $(this).data('id');

            $.ajax({
                url: "{{ route('admin.product.status') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function (res) {
                    if (res.success) {
                        toastr.success('Status updated successfully');
                    }
                },
                error: function () {
                    toastr.error('Something went wrong');
                }
            });
        });

    </script>
@endpush
