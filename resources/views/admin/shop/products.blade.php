@extends('layouts.app')

@section('header-title', __('Shop Products'))

@section('content')
    
<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body"> 
            @include('admin.shop.header-nav')

            <h4 class="m-0 mt-4">{{ __('Shop Products') }}</h4>
            <ul class="nav nav-tabs mt-3" id="statusTabs">
                <li class="nav-item">
                    <a href="#" data-status="" class="nav-link active">All</a>
                </li>
                <li class="nav-item">
                    <a href="#" data-status="1" class="nav-link">Active</a>
                </li>
                <li class="nav-item">
                    <a href="#" data-status="0" class="nav-link">Inactive</a>
                </li>
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
                <table id="productsTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr> 
                            <th>#</th>
                            <th>{{ __('Create Date') }}</th>
                            <th>{{ __('Product ID') }}</th> 
                            <th>{{ __('Product Name') }}</th>
                            <th>{{ __('Product Image') }}</th> 
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('MRP') }}</th>
                            <th>{{ __('Sale Amount') }}</th>
                            <th>{{ __('Total Sale') }}</th> <!-- sale count -->
                            <th>{{ __('Variant') }}</th>    <!-- variant count -->
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


{{-- 
<!-- Grid View -->
<div class="container-fluid">

    <div class="card">
        <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
            <h4 class="m-0">{{ __('Shop Products') }}</h4>
        </div>
        <div class="card-body">
            @include('admin.shop.header-nav')

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4 g-4 mt-3">
                @forelse($products as $key => $product)
                    <div class="col">
                        <div class="card shadow-sm d-flex flex-column h-100">
                            <img src="{{ $product->thumbnail }}" alt="" class="card-img-top" loading="lazy"
                                width="100%" style="height: 200px; object-fit: cover" />
                            <div class="card-body h-100">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                        data-bs-title="{{ __('Update product status') }}">
                                            <input type="checkbox" {{ $product->is_active ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                    </label>
                                    <div class="d-flex gap-2">
                                        @hasPermission('shop.product.show')
                                            <a href="{{ route('shop.product.show', $product->id) }}"
                                                class="svg-bg btn-outline-primary circleIcon btn-sm"
                                                data-bs-toggle="tooltip" data-bs-placement="left"
                                                data-bs-title="{{ __('View Product') }}">
                                                <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="icon"
                                                    loading="lazy" />
                                            </a>
                                        @endhasPermission
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h5 class="card-title">{{ Str::limit($product->name, 50, '...') }}</h5>

                                    <p>
                                        {{ $product->short_description }}
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3">
                                        <p class="card-text m-0 d-flex align-items-center gap-1">
                                            <strong>{{ showCurrency($product->price) }}</strong>
                                            @if ($product->discount_price)
                                                <span class="badge bg-primary rounded-pill">
                                                    {{ showCurrency($product->discount_price) }}
                                                </span>
                                            @endif
                                        </p>

                                        <div class="d-flex align-items-center gap-1">
                                            <i class="fa fa-star text-warning"></i>
                                            ({{ number_format($product->reviews->avg('rating'), 1) }})
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                {{ __('No Data Found') }}
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="my-3">
        {{ $products->links() }}
    </div>

</div> 
--}}

@endsection

@push('scripts') 

<script>
    $(function() { 
        let Status = "";
        let startDate = "";
        let endDate = "";

        let table = $('#productsTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.shop.products', $shop->id) }}",
                data: function(d) {
                    d.status = Status; 
                    d.startDate = startDate;
                    d.endDate = endDate;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'product_id', name: 'product_id' },                 
                { data: 'name', name: 'name' }, 
                { data: 'product_image', orderable: false },
                { data: 'quantity', name: 'quantity' },
                { data: 'price', name: 'price' },   
                { data: 'discount_price', name: 'discount_price' }, 
                { data: 'total_sale_count', orderable: false, searchable: false },
                { data: 'variants_count', name: 'variants_count', orderable: false, searchable: false},
                { data: 'status_badge', orderable: false },
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
            Status = $(this).data('status'); 
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
            Status = "";

            $('#statusTabs a').removeClass('active');
            $('#statusTabs a[data-status=""]').addClass('active');

            table.ajax.reload();
        });
    });
</script>


@endpush
