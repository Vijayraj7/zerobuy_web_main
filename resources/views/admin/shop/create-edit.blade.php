@extends($layout ?? 'layouts.app')
@php
    $isEdit = isset($shop);
    $isPublicRegistration = $isPublicRegistration ?? false;
    $shop = $shop ?? new \App\Models\Shop();
    $setting = $setting ?? new \App\Models\DeliverySetting();
    $setting->setRelation('amountRules', $setting->amountRules ?? collect());
    $setting->setRelation('stateCharges', $setting->stateCharges ?? collect());
    $shop->setRelation('deliverySetting', $shop->deliverySetting ?? new \App\Models\DeliverySetting());
    $formAction = $formAction ?? ($isEdit ? route('shop.shop.update', $shop->id) : route('shop.shop.store'));
    $pageHeading = $isEdit ? __('Edit Shop') : ($isPublicRegistration ? __('Register as Seller') : __('Add New Shop'));
    $onlinePaymentConfig = old('online_payment_config', $isEdit ? ($shop->online_payment_config ?? []) : []);
@endphp
@section('header-title', $pageHeading)
@section('content')
@if (! $isPublicRegistration)
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            <i class="fa-solid fa-shop"></i> {{ $pageHeading }}
        </div>
    </div>
@endif

<script>
    const SELECTED_DISTRICT_ID = @json(old('district_id', $shop->district_id ?? null));
</script>

<form id="shopForm" action="{{ $formAction }}"
    method="POST" enctype="multipart/form-data" novalidate>@csrf
    @if ($errors->any())
        <div class="alert alert-danger" id="formErrorBox">
            <strong>{{ __('Please fix the following errors:') }}</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="alert alert-danger d-none" id="formErrorBox">
            <strong>{{ __('Please fix the following errors:') }}</strong>
            <ul class="mb-0 mt-2"></ul>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mt-4">
                <button type="{{ $isPublicRegistration ? 'submit' : 'button' }}" class="btn btn-primary py-2 px-5"
                    id="ajaxSubmitBtn">
                    {{ $isEdit ? 'Update' : ($isPublicRegistration ? 'Register' : 'Submit') }}
                </button>
            </div>
            <ul class="nav nav-tabs mt-3" id="productTabs">
                <li class="nav-item"><a class="nav-link active" data-step="1" href="#">Store Details</a></li>
                <li class="nav-item"><a class="nav-link" data-step="2" href="#">Business Category</a></li>
                <li class="nav-item"><a class="nav-link" data-step="3" href="#">Shipping Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-step="4" href="#">Delivery Charges</a></li>
                <li class="nav-item"><a class="nav-link" data-step="5" href="#">Payment Settings</a></li>
                @if (!$isEdit)
                    <li class="nav-item"><a class="nav-link" data-step="6" href="#">Account Information</a></li>
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
                                        <x-input type="text" name="shop_name" label="Shop Name"
                                            placeholder="Enter Shop Name"
                                            value="{{ old('shop_name', $isEdit ? $shop->name : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">Store Type <span class="text-danger">*</span></label>
                                        <select name="store_type" class="form-control">
                                            <option value="" disabled selected>--Select Store Type--</option>
                                            <option value="wholesale"
                                                {{ old('store_type', $shop->store_type ?? '') === 'wholesale' ? 'selected' : '' }}>
                                                Wholesale</option>
                                            <option value="retail"
                                                {{ old('store_type', $shop->store_type ?? '') === 'retail' ? 'selected' : '' }}>
                                                Retail</option>
                                            <option value="manufacturer"
                                                {{ old('store_type', $shop->store_type ?? '') === 'manufacturer' ? 'selected' : '' }}>
                                                Manufacturer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input label="Shop Owner Name" name="first_name" type="text"
                                            placeholder="Enter Shop Owner Name"
                                            value="{{ old('first_name', $isEdit ? $shop->user?->name : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input label="Phone Number" name="phone" type="number"
                                            placeholder="Enter phone number"
                                            value="{{ old('phone', $isEdit ? $shop->user?->phone : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input label="Whatsapp Number" name="whatsapp_number" type="number"
                                            placeholder="Enter whatsapp number"
                                            value="{{ old('whatsapp_number', $isEdit ? $shop->whatsapp_number : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="address" label="Address"
                                            placeholder="Enter Address"
                                            value="{{ old('address', $isEdit ? $shop->address : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label for="">State <span class="text-danger">*</span></label>
                                        <select name="state_id" id="state_id" class="form-control">
                                            <option value="">-- Select State --</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}"
                                                    {{ old('state_id', $shop->state_id ?? '') == $state->id ? 'selected' : '' }}>
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
                                        <x-input type="text" name="pincode" label="PIN Code"
                                            placeholder="Enter Pin Code"
                                            value="{{ old('pincode', $isEdit ? $shop->pincode : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input type="number" name="min_order_amount" label="Minimum Order Amount"
                                            placeholder="Enter Minimum Order Amount"
                                            value="{{ old('min_order_amount', $isEdit ? $shop->min_order_amount : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input type="text" name="gst_number" label="GST Number"
                                            placeholder="Enter GST Number"
                                            value="{{ old('gst_number', $isEdit ? $shop->gst_number : '') }}" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <x-input type="date" name="store_since" label="Store Since"
                                            placeholder="Enter Store Since"
                                            value="{{ old('store_since', $isEdit ? $shop->store_since : '') }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label for="">{{ __('Return Policy') }} <span
                                                class="text-danger">*</span></label>
                                        <textarea name="return_policy" class="form-control" id="return_policy" rows="2"
                                            placeholder="Enter Return Policy">{{ old('return_policy', $isEdit ? $shop->return_policy : '') }}</textarea>
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
                                                <img id="previewProfile"
                                                    src="{{ old('profile_photo', $isEdit ? $shop->user?->thumbnail ?? asset('default/default.jpg') : 'https://placehold.co/500x500/png') }}"
                                                    alt="" width="100%">
                                            </div>
                                        </div>
                                        <x-file name="profile_photo" label="User profile (Ratio 1:1)"
                                            preview="previewProfile" :required="!$isEdit" />
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <div class="ratio1x1">
                                                <img src="{{ old('shop_logo', $isEdit ? $shop->logo ?? asset('default/default.jpg') : 'https://placehold.co/500x500/png') }}"
                                                    id="previewShopLogo" alt="" width="100%">
                                            </div>
                                        </div>
                                        <x-file name="shop_logo" label="Shop logo(Ratio 1:1)"
                                            preview="previewShopLogo" :required="!$isEdit" />
                                    </div>

                                    <div class="col-md-12 mt-4">
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <div class="ratio4x1">
                                                <img src="{{ old('shop_banner', $isEdit ? $shop->banner ?? asset('default/default.jpg') : 'https://placehold.co/2000x500/png') }}"
                                                    id="shopBanner" alt="" width="100%">
                                            </div>
                                        </div>
                                        <x-file name="shop_banner" label="Shop banner Ratio 4:1 (2000 x 500 px)"
                                            preview="shopBanner" :required="!$isEdit" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="2"> Next
                        &raquo;</button>
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
                    @foreach ($businessCategories as $category)
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="category-card w-100">
                                <input type="checkbox" name="bussiness_categories_id[]" value="{{ $category->id }}"
                                    hidden
                                    {{ in_array($category->id, old('bussiness_categories_id', $isEdit ? $shop->businessCategories->pluck('id')->toArray() : [])) ? 'checked' : '' }}>
                                <div class="card category-box text-center">
                                    <img src="{{ $category->thumbnail ?? asset('default/default.jpg') }}"
                                        class="category-img">
                                    <div class="category-name">{{ $category->name }}</div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <p class="text-danger mt-2" id="businessCategoryError"></p>
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="1">&laquo;
                        Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="3">Next
                        &raquo;</button>
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

                            @foreach ($deliveryOptions as $key => $label)
                                <label class="delivery-card">
                                    <input type="radio" name="delivery_days" value="{{ $key }}"
                                        {{ old('delivery_days', $shop->estimated_delivery_time ?? '') == $key ? 'checked' : '' }}>
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
                                    old('delivery_state_ids', $shop->deliverySetting?->selected_state_ids ?? []),
                                )->map(fn($v) => (int) $v);
                            @endphp

                            @foreach ($states as $state)
                                <label class="state-check">
                                    <input type="checkbox" name="delivery_state_ids[]" value="{{ $state->id }}"
                                        {{ $selectedStates->contains($state->id) ? 'checked' : '' }}>
                                    <span>{{ $state->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <p class="text-danger mt-2" id="deliveryStateError"></p>
                <div class="mt-5">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="2">&laquo;
                        Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="4">Next
                        &raquo;</button>
                </div>
            </div>

            <!-- STEP 4 - Delivery Charges -->
            <div class="step-content step-4 mt-3" style="display:none;">
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-truck"></i> Delivery Charges</h5>
                    </div>

                    <div class="card-body">
                        <!-- Delivery Mode -->
                        <div class="mb-4">
                            <label class="fw-bold mb-2 d-block">Choose Delivery Charge Method</label>

                            <div class="btn-group" role="group">

                                <input type="radio" class="btn-check" id="delivery_amount" name="delivery_mode"
                                    value="amount_based"
                                    {{ old('delivery_mode', $shop->deliverySetting->delivery_mode ?? 'amount_based') === 'amount_based' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="delivery_amount">
                                    Amount Based
                                </label>

                                <input type="radio" class="btn-check" id="delivery_state" name="delivery_mode"
                                    value="state_wise"
                                    {{ old('delivery_mode', $shop->deliverySetting->delivery_mode ?? '') === 'state_wise' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="delivery_state">
                                    State Wise
                                </label>

                                <input type="radio" class="btn-check" id="delivery_manual" name="delivery_mode"
                                    value="manual"
                                    {{ old('delivery_mode', $shop->deliverySetting->delivery_mode ?? '') === 'manual' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="delivery_manual">
                                    Manual
                                </label>

                                <input type="radio" class="btn-check" id="delivery_provider_api" name="delivery_mode"
                                    value="provider_api"
                                    {{ old('delivery_mode', $shop->deliverySetting->delivery_mode ?? '') === 'provider_api' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="delivery_provider_api">
                                    API Provider
                                </label>

                            </div>
                        </div>

                        <!-- Amount Based -->
                        <div id="amount_based_box" class="delivery-box">

                            <div class="d-flex justify-content-between mb-2">
                                <h6>Amount Ranges</h6>
                            </div>

                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Min ₹</th>
                                        <th>Max ₹</th>
                                        <th>Charge ₹</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div id="amt-min-display" class="form-control-plaintext" aria-hidden="true">1</div>
                                        </td>
                                        <td><input id="amt-max" class="form-control" type="number"></td>
                                        <td><input id="amt-charge" class="form-control" type="number"></td>
                                        <td>
                                            <button type="button" class="btn btn-primary" id="addAmountRow">
                                                Add
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="text-danger mt-1 mb-0" id="amountRangeInlineError"></p>

                            <div class="mt-3">
                                <h6>Added Amount Ranges</h6>
                                <div id="added-amount-container">
                                    {{-- <p class="text-muted small" id="no-amount-msg">
                                        No amount ranges added yet.
                                    </p> --}}
                                    @php
                                        $amountRules = old('amount_rules', $setting->amountRules ?? []);
                                    @endphp

                                    @if (count($amountRules))
                                        @foreach ($amountRules as $i => $rule)
                                            <div class="card mb-2 amount-card" id="amount-{{ $i }}">
                                                <div class="card-body py-2">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-9">
                                                            <strong>₹
                                                                {{ $rule['min_amount'] ?? ($rule->min_amount ?? '') }}
                                                                – ₹
                                                                {{ $rule['max_amount'] ?? ($rule->max_amount ?? '') }}</strong>
                                                            → ₹
                                                            {{ $rule['charge'] }}
                                                            <input type="hidden"
                                                                name="amount_rules[{{ $i }}][min_amount]"
                                                                value="{{ $rule['min_amount'] ?? ($rule->min_amount ?? '') }}">
                                                            <input type="hidden"
                                                                name="amount_rules[{{ $i }}][max_amount]"
                                                                value="{{ $rule['max_amount'] ?? ($rule->max_amount ?? '') }}">
                                                            <input type="hidden"
                                                                name="amount_rules[{{ $i }}][charge]"
                                                                value="{{ $rule['charge'] ?? ($rule->charge ?? '') }}">
                                                        </div>
                                                        <div class="col-md-3 text-end">
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger delete-item"
                                                                data-type="amount" data-id="{{ $i }}">
                                                                ✕
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>


                        <!-- State Wise -->
                        <div id="state_wise_box" class="delivery-box d-none">
                            <div class="row">
                                @php
                                    $selectedStates = collect(
                                        old('delivery_state_ids', $shop->deliverySetting?->selected_state_ids ?? []),
                                    )->map(fn($v) => (int) $v);
                                @endphp

                                @foreach ($states->whereIn('id', $selectedStates) as $state)
                                    @php
                                        $stateCharge = $setting->stateCharges->firstWhere('state_id', $state->id);
                                        $charge = $stateCharge?->charge;

                                    @endphp

                                    <div class="col-md-4 mb-3 state-charge-item" data-state-id="{{ $state->id }}">

                                        <label class="fw-bold">{{ $state->name }}</label>

                                        <input type="hidden" name="state_charges[{{ $loop->index }}][state]"
                                            value="{{ $state->id }}" class="state-id-input">

                                        <input type="number" step="0.01"
                                            name="state_charges[{{ $loop->index }}][charge]"
                                            value="{{ old("state_charges.$loop->index.charge", $charge) }}"
                                            class="form-control state-charge-input" placeholder="₹ Delivery Charge">
                                    </div>
                                @endforeach
                            </div>
                            {{-- STATE ROW TEMPLATE --}}
                            <template id="stateChargeTemplate">
                                <div class="col-md-4 mb-3 state-charge-item" data-state-id="">
                                    <label class="fw-bold state-name"></label>

                                    <input type="hidden" name="" class="state-id-input">

                                    <input type="number" step="0.01" name=""
                                        class="form-control state-charge-input" placeholder="₹ Delivery Charge">
                                </div>
                            </template>
                        </div>

                        <!-- Manual -->
                        <div id="manual_box" class="delivery-box d-none">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="update_when_shipped"
                                    value="1"
                                    {{ old('update_when_shipped', $shop->deliverySetting->update_when_shipped ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold">
                                    Update delivery charge when shipped
                                </label>
                            </div>
                        </div>

                        <div id="provider_api_box" class="delivery-box d-none">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold mb-1 d-block">Delivery API Provider</label>
                                    <select name="delivery_provider" class="form-control">
                                        <option value="">-- Select Provider --</option>
                                        <option value="shiprocket"
                                            {{ old('delivery_provider', $shop->deliverySetting->delivery_provider ?? '') === 'shiprocket' ? 'selected' : '' }}>
                                            Shiprocket
                                        </option>
                                        <option value="delhivery"
                                            {{ old('delivery_provider', $shop->deliverySetting->delivery_provider ?? '') === 'delhivery' ? 'selected' : '' }}>
                                            Delhivery
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold mb-1 d-block">Provider API Key</label>
                                    <input type="text" class="form-control" name="provider_api_key"
                                        placeholder="Enter provider API key" autocomplete="off">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold mb-1 d-block">Provider API Secret</label>
                                    <input type="password" class="form-control" name="provider_api_secret"
                                        placeholder="Enter provider API secret" autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-danger mt-2" id="deliveryChargeError"></p>

                <div class="mt-4">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="3">« Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="5">Next
                        &raquo;</button>
                </div>
            </div>

            <!-- STEP 5 - Payment Settings -->
            <div class="step-content step-5 mt-3" style="display:none;">
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-credit-card"></i>
                            <h5>{{ __('Payment Settings') }}</h5>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4 mt-3">
                                <label class="form-label d-block">{{ __('Enable Cash on Delivery') }}</label>
                                <input type="hidden" name="cash_on_delivery_enabled" value="0">
                                <div class="form-check form-switch mt-2">
                                <div style="display: flex; align-items:center;">
                                    <input style="margin-top: 0px !important; margin-right: 10px;" class="form-check-input" type="checkbox" role="switch"
                                        id="cash_on_delivery_enabled" name="cash_on_delivery_enabled" value="1"
                                        {{ old('cash_on_delivery_enabled', $shop->cash_on_delivery_enabled ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cash_on_delivery_enabled">{{ __('Allow cash on delivery for this shop') }}</label>
                               </div>
                                </div>
                            </div>

                            <div class="col-md-4 mt-3">
                                <label class="form-label d-block">{{ __('Enable Online Payment') }}</label>
                                <input type="hidden" name="online_payment_enabled" value="0">
                                <div class="form-check form-switch mt-2">
                                <div style="display: flex; align-items:center;">
                                    <input style="margin-top: 0px !important; margin-right: 10px;" class="form-check-input" type="checkbox" role="switch"
                                        id="online_payment_enabled" name="online_payment_enabled" value="1"
                                        {{ old('online_payment_enabled', $shop->online_payment_enabled ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="online_payment_enabled">{{ __('Allow online payment for this shop') }}</label>
                                </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="row">
                                    <div class="col-md-4 mt-3">
                                        <label class="form-label">{{ __('Payment Provider') }}</label>
                                        <select name="online_payment_provider" class="form-control">
                                            <option value="">-- Select Provider --</option>
                                            <option value="razorpay"
                                                {{ old('online_payment_provider', $shop->online_payment_provider ?? '') === 'razorpay' ? 'selected' : '' }}>
                                                Razorpay
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mt-3">
                                        <label class="form-label">{{ __('Razorpay Key ID') }}</label>
                                        <input type="text" class="form-control" name="razorpay_key_id"
                                            value="{{ old('razorpay_key_id', data_get($onlinePaymentConfig, 'razorpay.key_id', '')) }}"
                                            placeholder="Enter Razorpay key ID" autocomplete="off">
                                    </div>

                                    <div class="col-md-4 mt-3">
                                        <label class="form-label">{{ __('Razorpay Key Secret') }}</label>
                                        <input type="password" class="form-control" name="razorpay_key_secret"
                                            value="{{ old('razorpay_key_secret') }}"
                                            placeholder="{{ $isEdit ? 'Leave empty to keep existing secret' : 'Enter Razorpay key secret' }}"
                                            autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <p class="text-danger mb-0" id="paymentGatewayError"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="4">&laquo; Previous</button>
                    @if (!$isEdit)
                        <button type="button" class="btn btn-primary next-btn float-end" data-next="6">Next
                            &raquo;</button>
                    @endif
                </div>
            </div>


            <!-- STEP 6 - Account Information -->
            <div class="step-content step-6 mt-3" style="display:none;">
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-user"></i>
                            <h5>{{ __('Account Information') }}</h5>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <x-input type="email" name="email" label="Email"
                                    placeholder="Enter Email Address" :required="!$isEdit" />
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <x-input type="password" name="password" label="Password"
                                    placeholder="Enter Password" :required="!$isEdit" />
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <x-input type="password" name="password_confirmation" label="Confirm Password"
                                    placeholder="Enter Confirm Password" :required="!$isEdit" />
                            </div>
                        </div>
                    </div>
                </div>
                @if (!$isEdit)
                    <input type="hidden" name="terms_condition_status" id="terms_condition_status" value="0">
                @endif
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="5">&laquo;
                        Previous</button>
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
                    @if (!empty($sellerTerms))
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
                <div>
                    <p class="text-danger mt-2 d-none" id="termsError"> Please agree to the terms & conditions </p>
                </div>
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
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('store_since');
    const pincodeInput = document.querySelector('[name="pincode"]');

    if (input) {
        const today = new Date().toISOString().split('T')[0];
        input.setAttribute('max', today);

        // Safety: prevent manual future date typing
        input.addEventListener('change', function () {
            if (this.value > today) {
                this.value = today;
            }
        });
    }

    if (pincodeInput) {
        pincodeInput.setAttribute('inputmode', 'numeric');
        pincodeInput.setAttribute('maxlength', '6');

        pincodeInput.addEventListener('input', function () {
            let value = (this.value || '').replace(/\D/g, '');
            if (value.length > 6) {
                value = value.slice(0, 6);
            }
            this.value = value;
        });
    }
});
</script>
<script>
    const IS_EDIT = @json($isEdit);
    const IS_PUBLIC = @json($isPublicRegistration);
    const HAS_PROVIDER_API_KEY = @json(!empty($shop->deliverySetting?->provider_api_key));
    const HAS_PROVIDER_API_SECRET = @json(!empty($shop->deliverySetting?->provider_api_secret));
    const HAS_RAZORPAY_KEY_ID = @json(!empty(data_get($shop->online_payment_config, 'razorpay.key_id')));
    const HAS_RAZORPAY_KEY_SECRET = @json(!empty(data_get($shop->online_payment_config, 'razorpay.key_secret')));
    var DISTRICTS_URL_TEMPLATE = "{{ route('shop.get-districts', ['stateId' => 'STATE_ID']) }}";
    $(document).ready(function() {

        function setAmountRangeError(message = '') {
            $('#amountRangeInlineError').text(message || '');
        }

        function uid() {
            return Date.now() + Math.random().toString(36).slice(2);
        }

        let addedAmountRanges = [];
        let amountIndex = {{ count($amountRules) }};

        // Populate existing ranges (server-rendered) into addedAmountRanges
        document.querySelectorAll('.amount-card').forEach(function(card) {
            const cMinEl = card.querySelector('[name*="[min_amount]"]');
            const cMaxEl = card.querySelector('[name*="[max_amount]"]');
            if (cMinEl && cMaxEl) {
                const cMin = cMinEl.value;
                const cMax = cMaxEl.value;
                if (cMin && cMax) addedAmountRanges.push(`${cMin}-${cMax}`);
            }
        });

        function computeNextMin() {
            const cards = document.querySelectorAll('.amount-card');
            if (!cards || cards.length === 0) return 1;
            // get last card's max_amount
            const lastCard = cards[cards.length - 1];
            const maxEl = lastCard.querySelector('[name*="[max_amount]"]');
            const lastMax = maxEl ? parseFloat(maxEl.value || 0) : 0;
            return lastMax ? (lastMax + 1) : 1;
        }

        function updateAmtMinField() {
            const disp = document.getElementById('amt-min-display');
            if (!disp) return;
            disp.innerText = computeNextMin();
        }

        // initialize min field on load
        updateAmtMinField();

        document.addEventListener('click', function(e) {

            /* ADD AMOUNT RANGE */
            if (e.target.id === 'addAmountRow') {
                setAmountRangeError('');

                const maxInput = document.getElementById('amt-max');
                const chargeInput = document.getElementById('amt-charge');

                const min = computeNextMin();
                const max = parseFloat(maxInput.value);
                const charge = parseFloat(chargeInput.value);

                /* VALIDATIONS */
                if (!min || !max || !charge) {
                    setAmountRangeError('Fill Min, Max and Charge');
                    return;
                }

                if (min >= max) {
                    setAmountRangeError('Min amount must be less than Max amount');
                    return;
                }

                /* 🔒 FIRST MIN MUST BE 1 */
                const existingCards = document.querySelectorAll('.amount-card');
                if (existingCards.length === 0 && min !== 1) {
                    setAmountRangeError('First minimum amount must be 1');
                    return;
                }

                const key = `${min}-${max}`;
                if (addedAmountRanges.includes(key)) {
                    setAmountRangeError('This amount range already exists');
                    return;
                }

                // Prevent duplicate charge values across ranges
                const existingChargeValues = Array.from(document.querySelectorAll('.amount-card input[name*="[charge]"]'))
                    .map(el => parseFloat(el.value || 0));
                if (existingChargeValues.includes(charge)) {
                    setAmountRangeError('This charge amount is already used in another range');
                    return;
                }

                /* CHECK OVERLAP */
                for (const card of document.querySelectorAll('.amount-card')) {
                    const cMin = parseFloat(card.querySelector('[name*="[min_amount]"]').value);
                    const cMax = parseFloat(card.querySelector('[name*="[max_amount]"]').value);

                    if (
                        (min >= cMin && min <= cMax) ||
                        (max >= cMin && max <= cMax) ||
                        (min <= cMin && max >= cMax)
                    ) {
                        setAmountRangeError(`Range overlaps with ${cMin} - ${cMax}`);
                        return;
                    }
                }

                addedAmountRanges.push(key);

                const id = uid();
                const card = document.createElement('div');
                card.className = 'card mb-2 amount-card';
                card.id = `amount-${id}`;

                card.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <strong>₹ ${min} – ₹ ${max}</strong> → ₹ ${charge}
                            <input type="hidden" name="amount_rules[${amountIndex}][min_amount]" value="${min}">
                            <input type="hidden" name="amount_rules[${amountIndex}][max_amount]" value="${max}">
                            <input type="hidden" name="amount_rules[${amountIndex}][charge]" value="${charge}">
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button"
                                class="btn btn-sm btn-danger delete-item"
                                data-type="amount"
                                data-id="${id}">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            `;

                const container = document.getElementById('added-amount-container');
                const emptyMsg = document.getElementById('no-amount-msg');
                if (emptyMsg) emptyMsg.style.display = 'none';
                amountIndex++;
                container.appendChild(card);
                // after adding, update next min and clear inputs
                updateAmtMinField();

                /* CLEAR INPUTS */
                maxInput.value = '';
                chargeInput.value = '';
                maxInput.focus();
                setAmountRangeError('');
            }

            /* DELETE AMOUNT RANGE */
            if (e.target.closest('.delete-item')?.dataset.type === 'amount') {

                const btn = e.target.closest('.delete-item');
                const id = btn.dataset.id;
                const card = document.getElementById(`amount-${id}`);

                const min = card.querySelector('[name*="[min_amount]"]').value;
                const max = card.querySelector('[name*="[max_amount]"]').value;

                addedAmountRanges = addedAmountRanges.filter(k => k !== `${min}-${max}`);
                card.remove();

                if (!document.querySelector('.amount-card')) {
                    const noMsg = document.getElementById('no-amount-msg');
                    if (noMsg) noMsg.style.display = 'block';
                }

                // after deletion, update min field to next required min
                updateAmtMinField();
            }
        });



        function updateStateWiseCharges() {
            const container = $('#state_wise_box .row');
            const template = $('#stateChargeTemplate').html();

            // ✅ collect existing charges from BOTH blade + js rows
            let existingCharges = {};
            container.find('.state-charge-item').each(function() {
                const stateId = $(this).data('state-id');
                const charge = $(this).find('.state-charge-input').val();
                if (stateId) {
                    existingCharges[stateId] = charge;
                }
            });

            container.empty();

            let index = 0;

            $('input[name="delivery_state_ids[]"]:checked').each(function() {
                const stateId = $(this).val();
                const stateName = $(this).siblings('span').text();
                const charge = existingCharges[stateId] ?? '';

                let $row = $(template);

                $row.attr('data-state-id', stateId);
                $row.find('.state-name').text(stateName);

                $row.find('.state-id-input')
                    .attr('name', `state_charges[${index}][state]`)
                    .val(stateId);

                $row.find('.state-charge-input')
                    .attr('name', `state_charges[${index}][charge]`)
                    .val(charge);

                container.append($row);
                index++;
            });

            if (index === 0) {
                container.append(
                    `<p class="text-muted ms-2">No states selected.</p>`
                );
            }
        }


        // Initial load (edit mode)
        // updateStateWiseCharges();

        // Live updates
        $(document).on('change', 'input[name="delivery_state_ids[]"]', updateStateWiseCharges);

        // Select all support
        $('#selectAllStates').on('change', function() {
            $('input[name="delivery_state_ids[]"]').prop('checked', this.checked);
            updateStateWiseCharges();
        });


        function toggleDeliveryBoxes() {
            let mode = $('input[name="delivery_mode"]:checked').val();
            $('.delivery-box').addClass('d-none');
            $('#' + mode + '_box').removeClass('d-none');
        }

        toggleDeliveryBoxes();
        $('input[name="delivery_mode"]').on('change', toggleDeliveryBoxes);



        //


        function clearErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#amountRangeInlineError').text('');
            $('#businessCategoryError').text('');
            $('#deliveryStateError').text('');
            $('#deliveryChargeError').text('');
            $('#paymentGatewayError').text('');
            $('#termsError').addClass('d-none');
            $('#formErrorBox').addClass('d-none');
            $('#formErrorBox ul').empty();
        }

        function addError(errors, field, message) {
            if (!errors[field]) {
                errors[field] = [];
            }
            if (!errors[field].includes(message)) {
                errors[field].push(message);
            }
        }

        function validateImageFile(fieldName, requiredOnCreate, errors) {
            const input = document.querySelector(`[name="${fieldName}"]`);
            if (!input) return;

            const file = input.files && input.files[0] ? input.files[0] : null;
            const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxBytes = 2048 * 1024;

            if (!IS_EDIT && requiredOnCreate && !file) {
                addError(errors, fieldName, `The ${fieldName.replace('_', ' ')} field is required.`);
                return;
            }

            if (!file) return;

            if (!allowed.includes((file.type || '').toLowerCase())) {
                addError(errors, fieldName, `The ${fieldName.replace('_', ' ')} must be a file of type: jpg, png, jpeg, gif.`);
            }

            if (file.size > maxBytes) {
                addError(errors, fieldName, `The ${fieldName.replace('_', ' ')} must not be greater than 2 MB.`);
            }
        }

        function activateStep(step) {
            $('.step-content').hide();
            $('.step-' + step).show();
            $('#productTabs .nav-link').removeClass('active');
            $('#productTabs .nav-link[data-step="' + step + '"]').addClass('active');
        }

        function getInitialStepFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const stepParam = parseInt(params.get('step') || '1', 10);
            const maxStep = IS_EDIT ? 5 : 6;

            if (Number.isNaN(stepParam) || stepParam < 1 || stepParam > maxStep) {
                return 1;
            }

            return stepParam;
        }

        function stepForField(field) {
            const stepMap = {
                shop_name: 1,
                store_type: 1,
                first_name: 1,
                phone: 1,
                whatsapp_number: 1,
                address: 1,
                state_id: 1,
                district_id: 1,
                pincode: 1,
                min_order_amount: 1,
                gst_number: 1,
                store_since: 1,
                return_policy: 1,
                description: 1,
                cash_on_delivery_enabled: 5,
                online_payment_enabled: 5,
                online_payment_provider: 5,
                razorpay_key_id: 5,
                razorpay_key_secret: 5,
                profile_photo: 1,
                shop_logo: 1,
                shop_banner: 1,
                bussiness_categories_id: 2,
                delivery_days: 3,
                delivery_state_ids: 3,
                email: 6,
                password: 6,
                password_confirmation: 6,
                delivery_provider: 4,
                provider_api_key: 4,
                provider_api_secret: 4,
            };

            if (field.startsWith('bussiness_categories_id')) return 2;
            if (field.startsWith('delivery_state_ids')) return 3;

            if (field.startsWith('amount_rules') || field.startsWith('state_charges') || field === 'delivery_mode') {
                return 4;
            }

            if (field in stepMap) return stepMap[field];

            return 1;
        }

        activateStep(getInitialStepFromUrl());

        function validateShopFormClient() {
            const errors = {};
            const today = new Date().toISOString().split('T')[0];

            const textVal = (name) => ($(`[name="${name}"]`).val() || '').toString().trim();
            const selectedRadioVal = (name) => ($(`input[name="${name}"]:checked`).val() || '').toString().trim();
            const checkedCount = (selector) => $(selector + ':checked').length;

            const firstName = textVal('first_name');
            if (!firstName) addError(errors, 'first_name', 'The first name field is required.');
            if (firstName.length > 255) addError(errors, 'first_name', 'The first name may not be greater than 255 characters.');

            const phone = textVal('phone');
            if (!phone) {
                addError(errors, 'phone', 'The phone field is required.');
            } else {
                if (phone.length < 10) addError(errors, 'phone', 'The phone must be at least 10 characters.');
                if (phone.length > 15) addError(errors, 'phone', 'The phone may not be greater than 15 characters.');
            }

            const shopName = textVal('shop_name');
            if (!shopName) addError(errors, 'shop_name', 'The shop name field is required.');
            if (shopName.length > 100) addError(errors, 'shop_name', 'The shop name may not be greater than 100 characters.');

            const storeType = textVal('store_type');
            if (!storeType) addError(errors, 'store_type', 'The store type field is required.');

            const whatsapp = textVal('whatsapp_number');
            if (!whatsapp) {
                addError(errors, 'whatsapp_number', 'The whatsapp number field is required.');
            } else {
                if (whatsapp.length < 10) addError(errors, 'whatsapp_number', 'The whatsapp number must be at least 10 characters.');
                if (whatsapp.length > 15) addError(errors, 'whatsapp_number', 'The whatsapp number may not be greater than 15 characters.');
            }

            const address = textVal('address');
            if (!address) addError(errors, 'address', 'The address field is required.');
            if (address.length > 150) addError(errors, 'address', 'The address may not be greater than 150 characters.');

            if (!textVal('state_id')) addError(errors, 'state_id', 'The state field is required.');
            if (!textVal('district_id')) addError(errors, 'district_id', 'The district field is required.');

            const pincode = textVal('pincode');
            const indiaPincodeRegex = /^[1-9][0-9]{5}$/;
            if (!pincode) {
                addError(errors, 'pincode', 'The pincode field is required.');
            } else if (!indiaPincodeRegex.test(pincode)) {
                addError(errors, 'pincode', 'Please enter a valid Indian PIN code (6 digits, cannot start with 0).');
            }

            if (!textVal('min_order_amount')) addError(errors, 'min_order_amount', 'The min order amount field is required.');

            const returnPolicy = textVal('return_policy');
            if (!returnPolicy) addError(errors, 'return_policy', 'The return policy field is required.');
            if (returnPolicy.length > 1250) addError(errors, 'return_policy', 'The return policy may not be greater than 1250 characters.');

            const description = textVal('description');
            if (description.length > 200) addError(errors, 'description', 'The description may not be greater than 200 characters.');

            const onlinePaymentEnabled = $('input[name="online_payment_enabled"]:checked').val() === '1';
            const cashOnDeliveryEnabled = $('input[name="cash_on_delivery_enabled"]:checked').val() === '1';
            const onlinePaymentProvider = textVal('online_payment_provider');

            if (!onlinePaymentEnabled && !cashOnDeliveryEnabled) {
                addError(errors, 'cash_on_delivery_enabled', 'Either Cash on Delivery or Online Payment must be enabled.');
            }

            if (onlinePaymentEnabled) {
                if (!onlinePaymentProvider) {
                    addError(errors, 'online_payment_provider', 'Please select an online payment provider.');
                }

                if (onlinePaymentProvider && !['razorpay'].includes(onlinePaymentProvider)) {
                    addError(errors, 'online_payment_provider', 'Selected online payment provider is invalid.');
                }
            }

            if (onlinePaymentEnabled && onlinePaymentProvider === 'razorpay') {
                const razorpayKeyId = textVal('razorpay_key_id');
                const razorpayKeySecret = textVal('razorpay_key_secret');

                if (!razorpayKeyId && !HAS_RAZORPAY_KEY_ID) {
                    addError(errors, 'razorpay_key_id', 'The Razorpay key ID field is required when Razorpay is selected.');
                }

                if (!razorpayKeySecret && !HAS_RAZORPAY_KEY_SECRET) {
                    addError(errors, 'razorpay_key_secret', 'The Razorpay key secret field is required when Razorpay is selected.');
                }
            }

            const storeSince = textVal('store_since');
            if (!storeSince) {
                addError(errors, 'store_since', 'The store since field is required.');
            } else if (storeSince > today) {
                addError(errors, 'store_since', 'Store since date cannot be in the future.');
            }

            validateImageFile('profile_photo', true, errors);
            validateImageFile('shop_logo', true, errors);
            validateImageFile('shop_banner', true, errors);

            if (!selectedRadioVal('delivery_days')) addError(errors, 'delivery_days', 'The delivery days field is required.');

            if (checkedCount('input[name="bussiness_categories_id[]"]') < 1) {
                addError(errors, 'bussiness_categories_id', 'Please select at least one business category.');
            }

            if (checkedCount('input[name="delivery_state_ids[]"]') < 1) {
                addError(errors, 'delivery_state_ids', 'Please select at least one delivery state.');
            }

            const deliveryMode = selectedRadioVal('delivery_mode');

            if (deliveryMode === 'manual' && onlinePaymentEnabled) {
                addError(errors, 'delivery_mode', 'Manual delivery mode cannot be used when online payment is enabled. Please change one of these settings.');
            }

            if (deliveryMode === 'amount_based') {
                const amountRuleCount = $('input[name^="amount_rules["][name$="[min_amount]"]').length;
                if (amountRuleCount < 1) {
                    addError(errors, 'amount_rules', 'Please add at least one amount range for amount based delivery charges.');
                }
            }

            if (deliveryMode === 'state_wise') {
                const $stateRows = $('#state_wise_box .state-charge-item');
                if (!$stateRows.length) {
                    addError(errors, 'state_charges', 'Please add state charges for selected states.');
                } else {
                    let missingCharge = false;
                    $stateRows.each(function() {
                        const charge = ($(this).find('.state-charge-input').val() || '').toString().trim();
                        if (charge === '') {
                            missingCharge = true;
                            $(this).find('.state-charge-input').addClass('is-invalid');
                        }
                    });

                    if (missingCharge) {
                        addError(errors, 'state_charges', 'Please enter delivery charges for all selected states.');
                    }
                }
            }

            if (deliveryMode === 'provider_api') {
                const deliveryProvider = textVal('delivery_provider');
                const providerApiKey = textVal('provider_api_key');
                const providerApiSecret = textVal('provider_api_secret');
                const allowedProviders = ['shiprocket', 'delhivery'];

                if (!deliveryProvider) {
                    addError(errors, 'delivery_provider', 'Please select a delivery API provider.');
                } else if (!allowedProviders.includes(deliveryProvider)) {
                    addError(errors, 'delivery_provider', 'Selected delivery API provider is invalid.');
                }

                if (!providerApiKey && !HAS_PROVIDER_API_KEY) {
                    addError(errors, 'provider_api_key', 'The provider API key field is required for API delivery mode.');
                }

                if (deliveryProvider === 'shiprocket' && !providerApiSecret && !HAS_PROVIDER_API_SECRET) {
                    addError(errors, 'provider_api_secret', 'The provider API secret field is required for API delivery mode.');
                }
            }

            const email = textVal('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!IS_EDIT) {
                if (!email) {
                    addError(errors, 'email', 'The email field is required.');
                } else if (!emailRegex.test(email)) {
                    addError(errors, 'email', 'The email must be a valid email address.');
                }
            } else if (email && !emailRegex.test(email)) {
                addError(errors, 'email', 'The email must be a valid email address.');
            }

            const password = textVal('password');
            const passwordConfirmation = textVal('password_confirmation');

            if (!IS_EDIT) {
                if (!password) addError(errors, 'password', 'The password field is required.');
                if (password && password.length < 6) addError(errors, 'password', 'The password must be at least 6 characters.');

                if (!passwordConfirmation) {
                    addError(errors, 'password_confirmation', 'The password confirmation field is required.');
                } else if (passwordConfirmation.length < 6) {
                    addError(errors, 'password_confirmation', 'The password confirmation must be at least 6 characters.');
                }
            } else {
                if (password && password.length < 6) addError(errors, 'password', 'The password must be at least 6 characters.');
                if (passwordConfirmation && passwordConfirmation.length < 6) {
                    addError(errors, 'password_confirmation', 'The password confirmation must be at least 6 characters.');
                }
            }

            if ((password || passwordConfirmation) && password !== passwordConfirmation) {
                addError(errors, 'password', 'The password and confirmation password do not match.');
            }

            return errors;
        }

        const initialStateId = $('#state_id').val();

        if (initialStateId) {
            loadDistricts(initialStateId, SELECTED_DISTRICT_ID);
        }

        function loadDistricts(stateId, selectedDistrictId = null) {
            $('#district_id').prop('disabled', true);

            if (!stateId) {
                $('#district_id').html('<option value="">-- Select District --</option>');
                return;
            }

            const districtsUrl = DISTRICTS_URL_TEMPLATE.replace('STATE_ID', stateId);

            $.get(districtsUrl)
                .done(function(response) {

                    let districts = [];

                    // ✅ SAFELY extract districts
                    if (Array.isArray(response)) {
                        districts = response;
                    } else if (response && Array.isArray(response.data)) {
                        districts = response.data;
                    } else if (response && response.data && Array.isArray(response.data.data)) {
                        districts = response.data.data;
                    }

                    districts = districts.filter(function(district) {
                        return district && typeof district === 'object' && district.id && district.name;
                    });

                    // 🛑 HARD GUARD
                    if (!districts.length) {
                        $('#district_id').html('<option value="">No districts found</option>');
                        return;
                    }

                    let options = '<option value="">-- Select District --</option>';

                    districts.forEach(function(district) {
                        options += `
                    <option value="${district.id}"
                        ${district.id == selectedDistrictId ? 'selected' : ''}>
                        ${district.name}
                    </option>`;
                    });

                    $('#district_id')
                        .html(options)
                        .prop('disabled', false);
                })
                .fail(function() {
                    $('#district_id').html('<option value="">Failed to load districts</option>');
                });
        }

        $('#state_id').on('change', function() {
            loadDistricts(this.value, null);
        });

        function showErrors(errors) {
            let errorList = [];
            $.each(errors, function(field, messages) {
                if (Array.isArray(messages) && messages.length) {
                    errorList.push(messages[0]);
                }
                if (field === 'bussiness_categories_id' || field.startsWith('bussiness_categories_id.')) {
                    $('#businessCategoryError').text(messages[0]);
                    return;
                }
                if (field === 'delivery_state_ids' || field.startsWith('delivery_state_ids.')) {
                    $('#deliveryStateError').text(messages[0]);
                    return;
                }
                if (field === 'amount_rules' || field.startsWith('amount_rules.') || field === 'state_charges' || field
                    .startsWith('state_charges.') || field === 'delivery_mode' || field === 'delivery_provider' || field === 'provider_api_key' || field === 'provider_api_secret') {
                    $('#deliveryChargeError').text(messages[0]);
                    return;
                }
                if (
                    field === 'cash_on_delivery_enabled' ||
                    field === 'online_payment_enabled' ||
                    field === 'online_payment_provider' ||
                    field === 'razorpay_key_id' ||
                    field === 'razorpay_key_secret'
                ) {
                    $('#paymentGatewayError').text(messages[0]);
                    return;
                }
                let input = $('[name="' + field + '"]');
                if (!input.length && field.includes('.')) {
                    input = $('[name="' + field.replace(/\.(\d+)\./g, '[$1][').replace(/\.(\d+)/g, '[$1]').replace(/\./g, '[') + ']"]');
                }
                if (!input.length) {
                    input = $('[name="' + field + '[]"]');
                }
                input.addClass('is-invalid');
                if (input.next('.invalid-feedback').length === 0) {
                    input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                }
            });

            if (errorList.length) {
                const $box = $('#formErrorBox');
                const $list = $box.find('ul');
                $list.empty();
                errorList.forEach(function(message) {
                    $list.append('<li>' + message + '</li>');
                });
                $box.removeClass('d-none');
            }
        }
        let termsModal = null;
        if (!IS_EDIT && !IS_PUBLIC) {
            const termsModalEl = document.getElementById('termsModal');
            termsModal = new bootstrap.Modal(termsModalEl);
        }

        if (!IS_PUBLIC) {
            $('#ajaxSubmitBtn').on('click', function() {
                clearErrors();
                const clientErrors = validateShopFormClient();
                if (Object.keys(clientErrors).length) {
                    showErrors(clientErrors);
                    const firstErrorField = Object.keys(clientErrors)[0];
                    activateStep(stepForField(firstErrorField));
                    const firstField = $('[name="' + firstErrorField + '"], [name="' + firstErrorField + '[]"]:first');
                    if (firstField.length) {
                        firstField.trigger('focus');
                    }
                    $('html, body').animate({
                        scrollTop: $('#formErrorBox').offset().top - 100
                    }, 300);
                    return;
                }

                let formData = new FormData($('#shopForm')[0]);
                $.ajax({
                    url: $('#shopForm').attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        console.log(res);
                        if (res.message) { // SHOW TOAST
                            Toast.fire({
                                icon: 'success',
                                title: res.message
                            });
                        }
                        if (IS_EDIT && res.status === 'success') { //EDIT MODE → direct success
                            setTimeout(() => {
                                window.location.href = res.redirect;
                            }, 1200);
                            return;
                        }
                        if (!IS_EDIT && res.status ===
                            'terms_required') { //CREATE MODE → show terms modal
                            termsModal.show();
                            return;
                        }
                        if (res.status === 'success') { //FINAL CREATE SUCCESS
                            setTimeout(() => {
                                window.location.href = res.redirect;
                            }, 1200);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            showErrors(xhr.responseJSON.errors);
                        }
                    }
                });
            });

            $('#agreeAndSubmit').on('click', function() {
                if (!$('#agreeTerms').is(':checked')) {
                    $('#termsError').removeClass('d-none');
                    return;
                }
                $('#termsError').addClass('d-none');
                $('#terms_condition_status').val(1);
                termsModal.hide();
                $('#ajaxSubmitBtn').trigger('click');
            });
        }

        if (IS_PUBLIC) {
            $('#shopForm').on('submit', function(e) {
                clearErrors();
                const clientErrors = validateShopFormClient();
                if (Object.keys(clientErrors).length) {
                    e.preventDefault();
                    showErrors(clientErrors);
                    const firstErrorField = Object.keys(clientErrors)[0];
                    activateStep(stepForField(firstErrorField));
                    const firstField = $('[name="' + firstErrorField + '"], [name="' + firstErrorField + '[]"]:first');
                    if (firstField.length) {
                        firstField.trigger('focus');
                    }
                    $('html, body').animate({
                        scrollTop: $('#formErrorBox').offset().top - 100
                    }, 300);
                }
            });
        }
    });
</script>
@endpush

<!-- custom.js file included -->
<!-- custom.css file included -->
