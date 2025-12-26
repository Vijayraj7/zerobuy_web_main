@extends('layouts.app')
@section('content')
@php
    $isEdit = false;
    if (isset($shop)) {
        $isEdit = true;
        //echo $shop;
    }
@endphp
@section('header-title', $isEdit ? 'Edit Shop' : __('Add New Shop'))

<div class="page-title">
    <div class="d-flex gap-2 align-items-center">
        <i class="fa-solid fa-shop"></i> {{ $isEdit ? 'Edit Shop' : __('Add New Shop') }}
    </div>
</div>

<form action="{{ $isEdit ? route('admin.shop.update', $shop->id) : route('admin.shop.store') }}" method="POST" enctype="multipart/form-data">@csrf
 
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-primary py-2 px-5">
                    {{ __('Submit') }}
                </button>
            </div>            
            <ul class="nav nav-tabs mt-3" id="productTabs">
                <li class="nav-item"><a class="nav-link active" data-step="1" href="#">Store Details</a></li>
                <!-- <li class="nav-item"><a class="nav-link" data-step="2" href="#">Business Category</a></li>
                <li class="nav-item"><a class="nav-link" data-step="3" href="#">Shipping Settings</a></li> -->
                @if (!$isEdit)
                <li class="nav-item"><a class="nav-link" data-step="2" href="#">Account Information</a></li> 
                @endif
            </ul>

            <!-- STEP 1 - Store Details -->
            <div class="step-content step-1 mt-3">
                <div class="card mt-4 mb-4">
                    <div class="card-body">
                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-shop"></i>
                            <h5>{{ __('Shop Information') }}</h5>
                        </div>

                        <div class="row">
                            <div class="col-lg-7">
                                <div class="row mt-3">
                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="shop_name" label="Shop Name" placeholder="Enter Shop Name" value="{{ old('shop_name', $isEdit ? $shop->name : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">Store Type <span class="text-danger">*</span></label> 
                                        <select name="store_type" class="form-control">
                                            <option value="" disabled selected>--Select Store Type--</option>
                                            <option value="wholesale" {{ old('store_type', $shop->store_type ?? '') === 'wholesale' ? 'selected' : '' }}>wholesale</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input label="Shop Owner Name" name="first_name" type="text" placeholder="Enter Shop Owner Name" value="{{ old('first_name', $isEdit ? $shop->user?->name : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input label="Phone Number" name="phone" type="number" placeholder="Enter phone number" value="{{ old('phone', $isEdit ? $shop->user?->phone : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input label="Whatsapp Number" name="whatsapp_number" type="number" placeholder="Enter whatsapp number" value="{{ old('whatsapp_number', $isEdit ? $shop->whatsapp_number : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="address" label="Address" placeholder="Enter Address" value="{{ old('address', $isEdit ? $shop->address : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="state" label="State" placeholder="Enter State" value="{{ old('state', $isEdit ? $shop->state : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="district" label="District" placeholder="Enter District" value="{{ old('district', $isEdit ? $shop->district : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="pincode" label="PIN Code" placeholder="Enter State" value="{{ old('pincode', $isEdit ? $shop->pincode : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="number" name="min_order_amount" label="Minimum Order Amount" placeholder="Enter Minimum Order Amount" value="{{ old('min_order_amount', $isEdit ? $shop->min_order_amount : '') }}" required="true" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="gst_number" label="GST Number" placeholder="Enter GST Number" value="{{ old('gst_number', $isEdit ? $shop->gst_number : '') }}" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <x-input type="date" name="store_since" label="Store Since" placeholder="Enter Store Since" value="{{ old('store_since', $isEdit ? $shop->store_since : '') }}" />
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label for="">{{ __('Return Policy') }} <span class="text-danger">*</span></label>
                                        <textarea name="return_policy" class="form-control" id="return_policy" rows="2" placeholder="Enter Return Policy">{{ old('return_policy', $isEdit ? $shop->return_policy : '') }}</textarea>  
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label for="">
                                            {{ __('Description') }}
                                        </label>
                                        <textarea name="description" class="form-control" id="description" rows="2" placeholder="Enter Description">{{ old('description', $isEdit ? $shop->description : '') }}</textarea> 
                                        <p class="text text-danger m-0" id="descriptionError"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="row mt-3">
                                    <div class="col-lg-6 mt-4">
                                        <div class="mb-2 d-flex align-items-center justify-content-center">
                                            <div class="ratio1x1">
                                                <img id="previewProfile" src="{{ $isEdit ? $shop->user?->thumbnail ?? asset('default/default.jpg') : 'https://placehold.co/500x500/png' }}" alt="" width="100%">
                                            </div>
                                        </div> 
                                        <x-file name="profile_photo" label="User profile (Ratio 1:1)" preview="previewProfile" :required="!$isEdit" /> 
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <div class="ratio1x1">
                                                <img src="{{ $isEdit ? $shop->logo ?? asset('default/default.jpg') : 'https://placehold.co/500x500/png' }}" id="previewShopLogo" alt=""
                                                    width="100%">
                                            </div>
                                        </div>
                                        <x-file name="shop_logo" label="Shop logo(Ratio 1:1)" preview="previewShopLogo" :required="!$isEdit" />
                                    </div>

                                    <div class="col-md-12 mt-4">
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <div class="ratio4x1">
                                                <img src="{{ $isEdit ? $shop->banner ?? asset('default/default.jpg') : 'https://placehold.co/2000x500/png' }}" id="shopBanner" alt=""
                                                    width="100%">
                                            </div>
                                        </div>
                                        <x-file name="shop_banner" label="Shop banner Ratio 4:1 (2000 x 500 px)" preview="shopBanner" :required="!$isEdit" />
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div> 
                
                <div class="mt-3">
                    @if (!$isEdit)
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="2"> Next &raquo;</button>
                    @endif
                </div>
            </div>

            <!-- STEP 2 - Business Category -->
            <!-- <div class="step-content step-2 mt-3" style="display:none;"> 
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-list-alt"></i>
                            <h5>
                                {{ __('Business Category Information') }}
                            </h5>
                        </div> 
                    </div>
                </div> 
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="1">&laquo; Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="3">Next &raquo;</button>
                </div>
            </div> -->

            <!-- STEP 3 - Shipping Settings --> 
            <!-- <div class="step-content step-3 mt-3" style="display:none;">
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-truck"></i>
                            <h5>
                                {{ __('Shipping Information') }}
                            </h5>
                        </div> 
                    </div>
                </div>   
                <div class="mt-5">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="2">&laquo; Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="4">Next &raquo;</button>
                </div>
            </div> -->

            <!-- STEP 4 - Account Information -->
            <div class="step-content step-2 mt-3" style="display:none;"> 
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-user"></i>
                            <h5>
                                {{ __('Account Information') }}
                            </h5>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <x-input type="email" name="email" label="Email" placeholder="Enter Email Address" :required="!$isEdit" />
                            </div>

                            <div class="col-md-4 mt-3 mt-md-0">
                                <x-input type="password" name="password" label="Password" placeholder="Enter Password" :required="!$isEdit" />
                            </div>

                            <div class="col-md-4 mt-3 mt-md-0">
                                <x-input type="password" name="password_confirmation" label="Confirm Password"
                                    placeholder="Enter Confirm Password" :required="!$isEdit" />
                            </div>
                        </div>
                        <!-- <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-primary py-2 px-5">
                                {{ __('Submit') }}
                            </button>
                        </div> -->
                    </div>
                </div> 
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="1">&laquo; Previous</button>
                    <!-- <button type="button" class="btn btn-primary next-btn float-end" data-next="5">Next &raquo;</button>  -->
                </div>
            </div> 
        </div>
    </div>
</form>
@endsection


