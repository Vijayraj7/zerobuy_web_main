@extends('layouts.app')

@section('header-title', __('Shop Products'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="w-100 page-title-heading d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                {{__('Advertisements')}}
                <div class="page-title-subheading">
                    {{__('Advertisement List')}}
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center gap-md-4"> 
                <a href="javascript:void(0);" class="btn py-2 btn-info"> <i class="fas fa-wallet"></i> ₹{{ number_format($wallet?->balance ?? 0, 2) }} </a>
                <a href="#" class="btn py-2 btn-success"> <i class="fa fa-money">&#xf0d6;</i> {{__('Add Money')}} </a> 
                <a href="{{route('shop.advertisement.transactions')}}" class="btn py-2 btn-warning"> <i class="fa fa-exchange"></i> {{__('Transaction History')}} </a> 
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#advertisementModal"> Run Ads </button>
                <!-- <a href="javascript:void(0);" class="btn py-2 btn-primary" data-bs-toggle="modal" data-bs-target="#advertisementModal"> <i class="fa fa-plus-circle"></i> {{__('Run Ads')}} </a>    -->
            </div>
        </div>
    </div>
</div>   

<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body"> 
            <h4 class="m-0 mt-4">{{ __('My Advertisements') }}</h4> 

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
                <table id="advertisementTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr> 
                            <th>#</th>
                            <th>{{ __('Start Date') }}</th>
                            <th>{{ __('End Date') }}</th> 
                            <th>{{ __('Ads Type') }}</th>       <!-- Store Ads/Product Ads -->
                            <th>{{ __('Image') }}</th>          <!-- if Ads Type is Product Ads - product image display. otherwise N/A -->
                            <th>{{ __('Product ID') }}</th>     <!-- if Ads Type is Product Ads - product ID display. otherwise N/A -->   
                            <th>{{ __('Product Name') }}</th>   <!-- if Ads Type is Product Ads - product Name display. otherwise N/A --> 
                            <th>{{ __('Daily Budget') }}</th>
                            <th>{{ __('Total Views') }}</th>  
                            <th>{{ __('Status') }}</th>         <!-- Active/Completed -->
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div> 
</div>

<!-- MODAL -->
<div class="modal fade" id="advertisementModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="advertisementForm"> @csrf
                <div class="modal-header"> 
                    <h4 class="modal-title">Run Ads</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <input type="hidden" name="ads_type" id="ads_type" value="store">
                <input type="hidden" name="product_id" id="selected_product_id">

                <div class="modal-body">
                    <div class="mb-3 d-flex gap-3">
                        <label class="delivery-card active" id="storeAds"><input type="radio" checked> Store Ads </label>
                        <label class="delivery-card" id="productAds"> <input type="radio"> Product Ads </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Start Date <span class="textdanger text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" class="form-control mb-2"> 
                        </div>
                        <div class="col-md-6">
                            <label>End Date <span class="textdanger text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date" class="form-control mb-2"> 
                        </div>
                        <div class="col-md-6 mb-3"> 
                            <label>Daily Budget</label>
                            <input type="number" name="daily_budget" id="daily_budget" Placeholder="Enter Daily Budget" class="form-control mb-2">
                        </div> 

                        <div class="col-md-6 mb-3"> 
                            <label>Total Budget</label>
                            <input type="text" id="total_budget" Placeholder="Enter Total Budget" class="form-control mb-2" readonly>
                        </div>  
                    </div>                     

                    <div id="productSection" class="d-none mt-5"> 
                        <div class="d-flex"> 
                            <button class="btn btn-outline-secondary border-end-0 rounded-end-0" disabled>
                                <i class="fa fa-search"></i>
                            </button>
                            <input type="text" id="productSearch" class="form-control rounded-start-0" placeholder="Search product ID or name">
                        </div> 
                        <table class="table table-bordered mt-2 datatableCustomCSS">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Image</th>
                                    <th>Price</th>
                                    <th>Select</th>
                                </tr>
                            </thead>
                            <tbody id="productTable"></tbody>
                        </table>
                    </div> 

                    <div id="formError" class="alert alert-danger d-none"></div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="runAdBtn">RUN</button>
                </div>
            </form>
        </div>
    </div>
</div> 
@endsection

@push('scripts') 
    <script>
        $(function () {

            // DataTable 
            $(function() {  
                let startDate = "";
                let endDate = "";

                let table = $('#advertisementTable').DataTable({
                    responsive: true,
                    processing: true,
                    serverSide: true,
                    deferRender: true,

                    ajax: {
                        url: "{{ route('shop.advertisement.index') }}",
                        data: function(d) { 
                            d.startDate = startDate;
                            d.endDate = endDate;
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'start_date', name: 'start_date' },
                        { data: 'end_date', name: 'end_date' },                 
                        { data: 'ads_type', name: 'ads_type' }, 
                        { data: 'product_image', orderable: false },
                        { data: 'product_id', name: 'product_id' },
                        { data: 'product_name', name: 'product_name' },   
                        { data: 'daily_budget', name: 'daily_budget' }, 
                        { data: 'total_views', orderable: false, searchable: false }, 
                        { data: 'status', orderable: false }, 
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
                    table.ajax.reload();
                });
            }); 

            // Toggle Ads Type
            $('#storeAds').click(() => {
                $('#ads_type').val('store');
                $('#productSection').addClass('d-none');
                $('#storeAds').addClass('active');
                $('#productAds').removeClass('active');
            });

            $('#productAds').click(() => {
                $('#ads_type').val('product');
                $('#productSection').removeClass('d-none');
                $('#productAds').addClass('active');
                $('#storeAds').removeClass('active');
                loadProducts();
            });

            // Budget Calculation
            $('#start_date,#end_date,#daily_budget').on('change keyup', function () {
                let s = $('#start_date').val();
                let e = $('#end_date').val();
                let d = $('#daily_budget').val();

                if (s && e && d) {
                    let days = (new Date(e) - new Date(s)) / 86400000 + 1;
                    $('#total_budget').val(days * d);
                }
            });

            // Load Products
            function loadProducts(q = '') {
                $.get("{{ route('shop.advertisement.products') }}", { q }, function (data) {
                    let rows = '';
                    data.forEach(p => {
                        rows += `
                        <tr>
                            <td>${p.id}</td>
                            <td>${p.name}</td>
                            <td><img src="${p.image}" width="40"></td>
                            <td>₹${p.price}</td>
                            <td><input type="radio" name="product_radio" value="${p.id}"></td>
                        </tr>`;
                    });
                    $('#productTable').html(rows);
                });
            }

            $('#productSearch').keyup(function () {
                loadProducts($(this).val());
            });

            $(document).on('change', 'input[name="product_radio"]', function () {
                $('#selected_product_id').val($(this).val());
            });

            // AJAX SUBMIT
            $('#advertisementForm').submit(function (e) {
                e.preventDefault();

                $('#runAdBtn').prop('disabled', true);
                $('#formError').addClass('d-none');

                $.ajax({
                    url: "{{ route('shop.advertisement.store') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function (res) {
                        $('#advertisementModal').modal('hide'); 
                        Toast.fire({
                            icon: 'success',
                            title: res.message
                        });
                        setTimeout(() => location.reload(), 1200); 
                    }, 
                    error: function (xhr) {
                        $('#runAdBtn').prop('disabled', false);
                        $('#formError').removeClass('d-none')
                            .text(xhr.responseJSON.message ?? 'Validation error');
                    }
                });
            });

        });
    </script>
@endpush
