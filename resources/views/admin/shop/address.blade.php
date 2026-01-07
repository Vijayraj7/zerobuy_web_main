@extends('layouts.app')
@section('header-title', __('Shop Followers'))
@section('content')

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">
            @include('admin.shop.header-nav')

           <div class="card mt-3">
                <div class="card-header d-flex align-items-center justify-content-start gap-2 py-3">
                    <i class="fa fa-address-card fz-18"></i>
                    <h4 class="fz-20 m-0"> {{ __('Address Information') }}</h4> 
                </div>
                <div class="card-body"> 
                    <div class="mt-2">
                        <span class="fw-medium">{{ __('Shop Address') }} : </span>
                        <span><b>{{ $shop->address }}</b></span>
                    </div> 
                    <div class="mt-2">
                        <span class="fw-medium">{{ __('State') }} : </span>
                        <span><b>{{ $shop->state }}</b></span>
                    </div> 
                    <div class="mt-2">
                        <span class="fw-medium">{{ __('District') }} : </span>
                        <span><b>{{ $shop->district }}</b></span>
                    </div> 
                    <div class="mt-2">
                        <span class="fw-medium">{{ __('PINCODE') }} : </span>
                        <span><b>{{ $shop->pincode }}</b></span>
                    </div> 
                </div>
            </div>

        </div>
    </div>
</div>

@endsection