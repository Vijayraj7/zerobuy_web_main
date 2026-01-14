@extends('layouts.app')

@section('header-title', __('Advertisements'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h3>{{ __('Advertisements') }}</h3>
            <p class="text-muted">{{ __('Advertisement List') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-info">
                <i class="fas fa-wallet"></i>
                ₹{{ number_format($wallet?->balance ?? 0, 2) }}
            </a>
            <a href="#" class="btn py-2 btn-success"> <i class="fa fa-money">&#xf0d6;</i> {{__('Add Money')}} </a> 
            <a href="{{ route('shop.advertisement.transactions') }}" class="btn btn-warning">
                <i class="fa fa-exchange"></i> {{ __('Transaction History') }}
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#advertisementModal"> {{ __('Run Ads') }} </button>
        </div>
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
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
                <table id="advertisementTable" class="table table-bordered datatableCustomCSS">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Ads Type</th>
                        <th>Image</th>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Daily Budget</th>
                        <th>Total Budget</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>


        </div>
    </div>
</div>

<!-- RUN ADS MODAL -->
<div class="modal fade" id="advertisementModal">
    <div class="modal-dialog modal-lg">
        <form id="advertisementForm">
            @csrf
            <div class="modal-content">

                <div class="modal-header">
                    <h5>{{ __('Run Advertisement') }}</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="ads_type" id="ads_type" value="product">
                    <input type="hidden" name="product_id" id="product_id">

                    <div class="mb-3 d-flex gap-3">
                        <button type="button" id="productAds" class="btn btn-outline-primary active"> Product Ads </button>
                        <button type="button" id="shopAds" class="btn btn-outline-primary"> Shop Ads </button>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-6">
                            <label>End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-6 mt-3">
                            <label>Daily Budget</label>
                            <input type="number" class="form-control" id="daily_budget" value="{{ $setting?->daily_budget ?? 0 }}" readonly>
                        </div>

                        <div class="col-md-6 mt-3">
                            <label>Total Budget</label>
                            <input type="text" id="total_budget" class="form-control" readonly>
                        </div>
                    </div>

                    <div id="productSection" class="d-none mt-4">
                        <input type="text" id="productSearch" class="form-control mb-2" placeholder="Search product">
                        <table class="table table-bordered datatableCustomCSS">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Image</th>
                                    <th>Select</th>
                                </tr>
                            </thead>
                            <tbody id="productTable"></tbody>
                        </table>
                    </div>
                    <div class="alert alert-danger d-none" id="formError"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" id="submitBtn"> RUN </button>
                </div>
            </div>
        </form>
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
        ajax: "{{ route('shop.advertisement.index') }}",
        columns: [
            { data: 'DT_RowIndex', orderable:false },
            { data: 'start_date' },
            { data: 'end_date' },
            { data: 'ads_type' },
            { data: 'product_image', orderable:false },
            { data: 'product_id' },
            { data: 'product_name' },
            { data: 'daily_budget' },
            { data: 'total_budget' },
            { data: 'status', orderable:false }
        ]
    });

    // TOGGLE ADS TYPE
    $('#shopAds').click(function(){
        $('#ads_type').val('store');
        $('#productSection').addClass('d-none');
        $(this).addClass('active');
        $('#productAds').removeClass('active');
    });

    $('#productAds').click(function(){
        $('#ads_type').val('product');
        $('#productSection').removeClass('d-none');
        $(this).addClass('active');
        $('#shopAds').removeClass('active');
        loadProducts();
    });

    // DATE & TOTAL BUDGET
    $('#start_date,#end_date').change(function(){
        let s = $('#start_date').val();
        let e = $('#end_date').val();
        let d = $('#daily_budget').val();

        if(s && e){
            let days = (new Date(e) - new Date(s)) / 86400000 + 1;
            $('#total_budget').val(days * d);
            $('#end_date').attr('min', s);
        }
    });

    // LOAD PRODUCTS
    function loadProducts(q = ''){
        $.get("{{ route('shop.advertisement.products') }}",{q},function(res){
            let html='';
            res.forEach(p=>{
                html+=`
                <tr>
                    <td>${p.id}</td>
                    <td>${p.name}</td>
                    <td><img src="${p.image}" width="40"></td>
                    <td>
                        <input type="radio" name="product"
                               value="${p.id}">
                    </td>
                </tr>`;
            });
            $('#productTable').html(html);
        });
    }

    // AUTO SELECT PRODUCT ADS WHEN MODAL OPENS
    $('#advertisementModal').on('shown.bs.modal', function () {

        $('#productAds').addClass('active');
        $('#shopAds').removeClass('active');

        $('#ads_type').val('product');
        $('#productSection').removeClass('d-none');

        loadProducts(); // auto load products
    });

    $('#productSearch').keyup(function(){
        loadProducts($(this).val());
    });

    $(document).on('change','input[name="product"]',function(){
        $('#product_id').val($(this).val());
    });

    // SUBMIT
    $('#advertisementForm').submit(function(e){
        e.preventDefault();
        $('#submitBtn').prop('disabled',true);
        $('#formError').addClass('d-none');

        $.post("{{ route('shop.advertisement.store') }}",
            $(this).serialize()
        )
        .done(res=>{
            $('#advertisementModal').modal('hide'); 
            Toast.fire({
                icon: 'success',
                title: res.message
            });
            setTimeout(() => location.reload(), 1200); 
        })
        .fail(xhr=>{
            $('#submitBtn').prop('disabled',false);
            $('#formError').removeClass('d-none')
                .text(xhr.responseJSON.message);
        });
    });

});
</script>
@endpush
