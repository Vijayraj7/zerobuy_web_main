@extends('layouts.app')
@section('content')
@php
    $isEdit = false;
    if (isset($shop)) {
        $isEdit = true; 
    }
@endphp
@section('header-title', $isEdit ? 'Edit Shop' : __('Add New Shop'))
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            <i class="fa-solid fa-shop"></i> {{ $isEdit ? 'Edit Shop' : __('Add New Shop') }}
        </div>
    </div>

    <form id="shopForm" action="{{ $isEdit ? route('admin.shop.update', $shop->id) : route('admin.shop.store') }}" method="POST" enctype="multipart/form-data">@csrf
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end mt-4"> 
                   <button type="button" class="btn btn-primary py-2 px-5" id="ajaxSubmitBtn">
                        {{ $isEdit ? 'Update' : 'Submit' }}
                    </button>
                </div>            
                <ul class="nav nav-tabs mt-3" id="productTabs">
                    <li class="nav-item"><a class="nav-link active" data-step="1" href="#">Store Details</a></li>
                    <li class="nav-item"><a class="nav-link" data-step="2" href="#">Business Category</a></li>
                    <li class="nav-item"><a class="nav-link" data-step="3" href="#">Shipping Settings</a></li>
                    @if (!$isEdit)
                    <li class="nav-item"><a class="nav-link" data-step="4" href="#">Account Information</a></li> 
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
                                                <option value="wholesale" {{ old('store_type', $shop->store_type ?? '') === 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                                <option value="retail" {{ old('store_type', $shop->store_type ?? '') === 'retail' ? 'selected' : '' }}>Retail</option>
                                                <option value="manufacturer" {{ old('store_type', $shop->store_type ?? '') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
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
                                            <label for="">State <span class="text-danger">*</span></label>
                                            <select name="state_id" id="state_id" class="form-control">
                                                <option value="">-- Select State --</option>
                                                @foreach ($states as $state)
                                                <option value="{{ $state->id }}" {{ old('state_id', $shop->state_id ?? '') == $state->id ? 'selected' : '' }}>
                                                    {{ $state->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-3">
                                            <label for="">District <span class="text-danger">*</span></label>
                                            <select name="district_id" id="district_id" class="form-control">
                                                <option value="">-- Select District --</option>
                                            </select>
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
                                            <x-input type="date" name="store_since" label="Store Since" placeholder="Enter Store Since" value="{{ old('store_since', $isEdit ? $shop->store_since : '') }}" required="true" />
                                        </div>
                                        <div class="col-md-6 mt-3">
                                            <label for="">{{ __('Return Policy') }} <span class="text-danger">*</span></label>
                                            <textarea name="return_policy" class="form-control" id="return_policy" rows="2" placeholder="Enter Return Policy">{{ old('return_policy', $isEdit ? $shop->return_policy : '') }}</textarea>  
                                        </div>
                                        <div class="col-md-6 mt-3">
                                            <label for="">{{ __('Description') }}</label>
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
                                                    <img id="previewProfile" src="{{ old('profile_photo', $isEdit ? $shop->user?->thumbnail ?? asset('default/default.jpg') : 'https://placehold.co/500x500/png') }}" alt="" width="100%">
                                                </div>
                                            </div> 
                                            <x-file name="profile_photo" label="User profile (Ratio 1:1)" preview="previewProfile" :required="!$isEdit" /> 
                                        </div>
                                        <div class="col-md-6 mt-4">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <div class="ratio1x1">
                                                    <img src="{{ old('shop_logo', $isEdit ? $shop->logo ?? asset('default/default.jpg') : 'https://placehold.co/500x500/png') }}" id="previewShopLogo" alt="" width="100%">
                                                </div>
                                            </div>
                                            <x-file name="shop_logo" label="Shop logo(Ratio 1:1)" preview="previewShopLogo" :required="!$isEdit" />
                                        </div>

                                        <div class="col-md-12 mt-4">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <div class="ratio4x1">
                                                    <img src="{{ old('shop_banner', $isEdit ? $shop->banner ?? asset('default/default.jpg') : 'https://placehold.co/2000x500/png') }}" id="shopBanner" alt="" width="100%">
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
                        <button type="button" class="btn btn-primary next-btn float-end" data-next="2"> Next &raquo;</button> 
                    </div>
                </div>

                <!-- STEP 2 - Business Category -->
                <div class="step-content step-2 mt-3" style="display:none;"> 
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="d-flex gap-2 border-bottom pb-2">
                                <i class="fa-solid fa-list-alt"></i>
                                <h5>{{ __('Business Category Information') }}</h5>
                            </div> 
                        </div>
                    </div>  
                    <div class="row g-2 mt-3">
                        @foreach($businessCategories as $category)
                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="category-card w-100">
                                    <input type="checkbox" name="bussiness_categories_id[]" value="{{ $category->id }}" hidden {{ in_array($category->id, old('bussiness_categories_id', $isEdit ? $shop->businessCategories->pluck('id')->toArray() : [])) ? 'checked' : '' }}>
                                    <div class="card category-box text-center">
                                        <img src="{{ $category->thumbnail ?? asset('default/default.jpg') }}" class="category-img">
                                        <div class="category-name">{{ $category->name }}</div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="1">&laquo; Previous</button>
                        <button type="button" class="btn btn-primary next-btn float-end" data-next="3">Next &raquo;</button>
                    </div>
                </div>

                <!-- STEP 3 - Shipping Settings --> 
                <div class="step-content step-3 mt-3" style="display:none;">
                    <div class="card mt-4">
                        <div class="card-header py-3"> 
                            <h5>{{ __('Select Delivery Days') }}</h5>
                        </div> 
                        <div class="card-body">
                            <!-- Shipping Settings --> 
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @php
                                    $deliveryOptions = [
                                        '2 to 7 days' => 'Delivery in 2 to 7 Days',
                                        '4 to 10 days' => 'Delivery in 4 to 10 Days',
                                        '5 to 12 days' => 'Delivery in 5 to 12 Days',
                                        '7 to 14 days' => 'Delivery in 7 to 14 Days',
                                    ];
                                @endphp

                                @foreach($deliveryOptions as $key => $label)
                                    <label class="delivery-card">
                                        <input type="radio" name="delivery_days" value="{{ $key }}" {{ old('delivery_days', $shop->estimated_delivery_time ?? '') == $key ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>  
                        <div class="card-header py-0"> 
                            <h5>{{ __('Delivery Available States') }}</h5>
                        </div> 
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="state-check">
                                    <input type="checkbox" id="selectAllStates">
                                    <span>All Select</span>
                                </label>
                            </div>
                            <div class="state-grid">
                                @php
                                    $selectedStates = collect(
                                        old(
                                            'delivery_state_ids',
                                            $shop->deliverySetting?->selected_state_ids ?? []
                                        )
                                    )->map(fn($v) => (int) $v);
                                @endphp

                                @foreach($states as $state)
                                    <label class="state-check">
                                        <input type="checkbox"
                                            name="delivery_state_ids[]"
                                            value="{{ $state->id }}"
                                            {{ $selectedStates->contains($state->id) ? 'checked' : '' }}>
                                        <span>{{ $state->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>   
                    <div class="mt-5">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="2">&laquo; Previous</button>
                        @if (!$isEdit)
                        <button type="button" class="btn btn-primary next-btn float-end" data-next="4">Next &raquo;</button>
                        @endif
                    </div>
                </div>

                <!-- STEP 4 - Account Information -->
                <div class="step-content step-4 mt-3" style="display:none;"> 
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="d-flex gap-2 border-bottom pb-2">
                                <i class="fa-solid fa-user"></i>
                                <h5>{{ __('Account Information') }}</h5>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <x-input type="email" name="email" label="Email" placeholder="Enter Email Address"  :required="!$isEdit" />
                                </div>
                                <div class="col-md-4 mt-3 mt-md-0">
                                    <x-input type="password" name="password" label="Password" placeholder="Enter Password" :required="!$isEdit" />
                                </div>
                                <div class="col-md-4 mt-3 mt-md-0">
                                    <x-input type="password" name="password_confirmation" label="Confirm Password" placeholder="Enter Confirm Password" :required="!$isEdit" />
                                </div>
                            </div> 
                        </div>
                    </div> 
                    @if (!$isEdit)
                    <input type="hidden" name="terms_condition_status" id="terms_condition_status" value="0">
                    @endif
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="3">&laquo; Previous</button> 
                    </div>
                </div> 
            </div>
        </div>
    </form>

    <!-- Terms & Conditions Modal -->
    @if (!$isEdit)
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y:auto;">
                    @if(!empty($sellerTerms))
                        {!! $sellerTerms->description !!}
                    @else
                        <p class="text-danger">Terms & Conditions not available.</p>
                    @endif 
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="agreeTerms">
                        <label class="form-check-label fw-bold"> I agree to the Terms & Conditions </label>
                    </div>
                    <!-- <p class="text-danger mt-2 d-none" id="termsError"> Please agree to the terms & conditions </p> -->
                </div> 
                <div><p class="text-danger mt-2 d-none" id="termsError"> Please agree to the terms & conditions </p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="agreeAndSubmit">Agree & Submit</button>
                </div>
            </div>
        </div>
    </div>
    @endif 

@endsection

@push('scripts') 
    <script>
        const IS_EDIT = @json($isEdit);
        $(document).ready(function () {
            function clearErrors() {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
            }
            function showErrors(errors) {
                $.each(errors, function (field, messages) {
                    let input = $('[name="'+field+'"]');
                    input.addClass('is-invalid');
                    if (input.next('.invalid-feedback').length === 0) {
                        input.after('<div class="invalid-feedback">'+messages[0]+'</div>');
                    }
                });
            }
            let termsModal = null;
            if (!IS_EDIT) {
                const termsModalEl = document.getElementById('termsModal');
                termsModal = new bootstrap.Modal(termsModalEl);
            }

            $('#ajaxSubmitBtn').on('click', function () {
                clearErrors();
                let formData = new FormData($('#shopForm')[0]);
                $.ajax({
                    url: $('#shopForm').attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function (res) {
                        if (res.message) {  // SHOW TOAST
                            Toast.fire({ icon: 'success', title: res.message });
                        } 
                        if (IS_EDIT && res.status === 'success') {  //EDIT MODE → direct success
                            setTimeout(() => {
                                window.location.href = res.redirect;
                            }, 1200);
                            return;
                        } 
                        if (!IS_EDIT && res.status === 'terms_required') {  //CREATE MODE → show terms modal
                            termsModal.show();
                            return;
                        } 
                        if (res.status === 'success') { //FINAL CREATE SUCCESS
                            setTimeout(() => {
                                window.location.href = res.redirect;
                            }, 1200);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            showErrors(xhr.responseJSON.errors);
                        }
                    }
                });
            }); 

            $('#agreeAndSubmit').on('click', function () {
                if (!$('#agreeTerms').is(':checked')) {
                    $('#termsError').removeClass('d-none');
                    return;
                }
                $('#termsError').addClass('d-none');
                $('#terms_condition_status').val(1);
                termsModal.hide();
                $('#ajaxSubmitBtn').trigger('click');
            });
        });
    </script>

@endpush

<!-- custom.js file included -->
<!-- custom.css file included -->