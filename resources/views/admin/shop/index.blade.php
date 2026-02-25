@extends('layouts.app')

@section('header-title', __('Shops'))

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="w-100 page-title-heading d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    {{__('Shops')}}
                    <div class="page-title-subheading">
                        {{__('This is a shops list')}}
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center gap-md-4">
                    <div class="d-flex gap-2 gap-md-3">
                        <button class="gridBtn" id="gridView" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="{{__('Grid View')}}">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                        </button>
                        <button class="gridBtn" id="listView" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{__('List View')}}">
                            <i class="fa-solid fa-list-ul"></i>
                        </button>
                    </div>

                    @hasPermission('admin.shop.create')
                    <a href="{{ route('admin.shop.create') }}" class="btn py-2 btn-primary">
                        <i class="fa fa-plus-circle"></i>
                        {{__('Add Shop')}}
                    </a>
                    @endhasPermission
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row row-gap mb-4 d-none" id="gridItem">
            @foreach ($shops as $key => $shop)
                <div class="col-12 col-md-6 col-xl-4 col-xxl-3">
                    <div class="card shadow-sm rounded-12 show-card position-relative overflow-hidden">
                        <div class="card-body shop p-2">
                            <div class="banner">
                                <img class="img-fit" src="{{ $shop->banner }}" />
                            </div>
                            <div class="main-content">
                                <div class="logo">
                                    <img class="img-fit" src="{{ $shop->logo }}" />
                                </div>
                                <div class="personal">
                                    <span class="name">{{ $shop->name }}</span>
                                    <span class="email">{{ $shop->user?->email }}</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2 px-3 mt-2">
                                <div class="item">
                                    <strong>Branded</strong>
                                    <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                        data-bs-title="Click here to enable branded store">
                                        @hasPermission('admin.shop.status.toggle')
                                            <a href="{{ route('admin.shop.branded.toggle', $shop->id) }}">
                                                <input type="checkbox" {{ $shop->is_branded ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </a>
                                        @else
                                            <input type="checkbox" {{ $shop->is_branded ? 'checked' : '' }}>
                                        @endhasPermission
                                    </label>
                                </div>
                                <div class="item">
                                    <strong>Verified</strong>
                                    <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                        data-bs-title="Click here to enable verified store">
                                        @hasPermission('admin.shop.status.toggle')
                                            <a href="{{ route('admin.shop.verify.toggle', $shop->id) }}">
                                                <input type="checkbox" {{ $shop->is_verified ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </a>
                                        @else
                                            <input type="checkbox" {{ $shop->is_verified ? 'checked' : '' }}>
                                        @endhasPermission
                                    </label>
                                </div>
                                <div class="item">
                                    <strong>{{ __('Status') }}</strong>
                                    <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                        data-bs-title="{{__('Click here to change status')}}">
                                        @hasPermission('admin.shop.status.toggle')
                                            <a href="{{ route('admin.shop.status.toggle', $shop->id) }}">
                                                <input type="checkbox" {{ $shop->user?->is_active ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </a>
                                        @else
                                            <input type="checkbox" {{ $shop->user?->is_active ? 'checked' : '' }}>
                                        @endhasPermission
                                    </label>
                                </div>
                                @hasPermission('admin.shop.products')
                                <div class="item">
                                    <strong>{{ __('Products') }}</strong>
                                    <a href="{{ route('admin.shop.products', $shop->id) }}" class="btn btn-secondary btn-sm"
                                        data-bs-toggle="tooltip" data-bs-placement="left"
                                        data-bs-title="{{__('Click here to see products')}}">
                                        {{ $shop->products->count() }}
                                    </a>
                                </div>
                                @endhasPermission

                                @hasPermission('admin.shop.orders')
                                <div class="item">
                                    <strong>{{ __('Orders') }}</strong>
                                    <a href="{{ route('admin.shop.orders', $shop->id) }}" class="btn btn-primary btn-sm"
                                        data-bs-toggle="tooltip" data-bs-placement="left"
                                        data-bs-title="{{__('Click here to see orders')}}">
                                        {{ $shop->orders->count() }}
                                    </a>
                                </div>
                                @endhasPermission
                            </div>
                        </div>
                        <div class="overlay">
                            @hasPermission('admin.shop.edit')
                            <a class="icons btn-outline-info" href="{{ route('admin.shop.edit', $shop->id) }}" data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-title="Edit">
                                <img src="{{ asset('assets/icons-admin/edit.svg') }}" alt="edit" loading="lazy" />
                            </a>
                            @endhasPermission
                            @hasPermission('admin.shop.show')
                            <a class="icons svg-bg" href="{{ route('admin.shop.show', $shop->id) }}" data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-title="View">
                                <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="view" loading="lazy" />
                            </a>
                            @endhasPermission
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mb-3 card" id="listItem">
            <div class="card-body">
                <ul class="nav nav-tabs mt-3" id="statusTabs">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" data-filter="all">All</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-filter="branded">Branded Stores</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-filter="verified">Verified Stores</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-filter="active">Active Stores</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-filter="inactive">Inactive Stores</a>
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
                <div class="mt-3 table-responsive">
                    <table id="shopTable" class="table table-bordered mt-3 datatableCustomCSS">
                        <thead>
                            <tr>
                                <th>{{ __('SL') }}</th>
                                <th>{{ __('Created Date') }}</th>
                                <th>{{ __('Store ID') }}</th>
                                <th>{{ __('Name') }}</th> 
                                <th>{{ __('Logo') }}</th>
                                <th>{{ __('Mobile Number') }}</th> 
                                <th>{{ __('State') }}</th>
                                <th>{{ __('Store Type') }}</th>
                                <th>{{ __('Subscription') }}</th> 
                                @hasPermission('admin.shop.products')
                                <th>{{ __('Products') }}</th>
                                @endhasPermission
                                @hasPermission('admin.shop.orders')
                                <th>{{ __('Orders') }}</th>
                                @endhasPermission
                                @hasPermission('admin.shop.status.toggle')
                                <th>Branded</th>
                                @endhasPermission
                                @hasPermission('admin.shop.status.toggle')
                                <th>Verified</th>
                                @endhasPermission
                                @hasPermission('admin.shop.status.toggle')
                                <th>{{ __('Status') }}</th>
                                @endhasPermission 
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
        let startDate = "";
        let endDate = "";
        let filter    = "all";

        let table = $('#shopTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.shop.index') }}",
                data: function(d) { 
                    d.startDate = startDate;
                    d.endDate = endDate;
                    d.filter    = filter;
                }
            },
             columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'shop_code', name: 'shop_code' },
                { data: 'name', name: 'name' },
                { data: 'logo', name: 'logo', orderable: false, searchable: false },
                { data: 'phone', name: 'phone' },
                { 
                    data: 'state', name: 'state',
                    render: function(data) {
                        return data ? data : '<span class="text-muted">-</span>';
                    }
                },
                { data: 'store_type', name: 'store_type',
                    render: function (data) {
                        if (!data) {
                            return '<span class="text-muted">-</span>';
                        }
                        return data.charAt(0).toUpperCase() + data.slice(1);
                    }
                },
                { data: 'subscription_days', name: 'subscription_days' },
                { data: 'products', name: 'products' },
                { data: 'orders', name: 'orders' },
                { data: 'branded', name: 'branded', orderable: false, searchable: false },
                { data: 'verified', name: 'verified', orderable: false, searchable: false },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            order: [[2, 'desc']],
            dom:'<"row"' + '<"col-md-6 d-flex align-items-center" l>' + '<"col-md-6 d-flex justify-content-end" f>' + '>' + 'rtip',
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
 
        // Date filter
        $('#startDate, #endDate').change(function () {
            startDate = $('#startDate').val();
            endDate   = $('#endDate').val();
            table.ajax.reload();
        });

        // Reload
        $('#reloadBtn').click(function () {
            startDate = endDate = "";
            filter = "all";
            $('#startDate,#endDate').val('');
            $('#statusTabs .nav-link').removeClass('active');
            $('#statusTabs .nav-link[data-filter="all"]').addClass('active');
            table.ajax.reload();
        });
        
        // Tabs click
        $('#statusTabs').on('click', '.nav-link', function (e) {
            e.preventDefault();

            $('#statusTabs .nav-link').removeClass('active');
            $(this).addClass('active');

            filter = $(this).data('filter'); // 👈 get tab filter
            table.ajax.reload();
        });
    });
</script>


@endpush