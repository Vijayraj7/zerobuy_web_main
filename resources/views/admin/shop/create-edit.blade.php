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
    $onlinePaymentConfig = old('online_payment_config', $isEdit ? $shop->online_payment_config ?? [] : []);
    $isShopDocumentUploadEnabled = (bool) ($generaleSetting?->shop_document_upload ?? true);
    $isAdminWhatsappOrderEnabled = (bool) ($generaleSetting?->whatsapp_order_enabled ?? false);
    $shopDocumentIsRequired = $isShopDocumentUploadEnabled && (!$isEdit || !$shop->shop_document);
    $allDistricts = \App\Models\District::query()->select('id', 'name', 'state_id')->orderBy('name')->get();
    // dd($allDistricts);
@endphp
@section('header-title', $pageHeading)
@section('content')
    @if (!$isPublicRegistration)
        <div class="page-title">
            <div class="d-flex gap-2 align-items-center">
                <i class="fa-solid fa-shop"></i> {{ $pageHeading }}
            </div>
        </div>
    @endif

    <style>
        .select-arrow {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 2.2rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M5 7l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 14px;
        }

        .select-arrow::-ms-expand {
            display: none;
        }
    </style>

    <script>
        const SELECTED_DISTRICT_ID = @json(old('district_id', $shop->district_id ?? null));
    </script>

    <form id="shopForm" action="{{ $formAction }}" method="POST" enctype="multipart/form-data" novalidate>@csrf
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
                    <li class="nav-item"><a class="nav-link" data-step="5" href="#">Delivery API</a></li>
                    <li class="nav-item"><a class="nav-link" data-step="6" href="#">Payment Settings</a></li>
                    @if (!$isEdit)
                        <li class="nav-item"><a class="nav-link" data-step="7" href="#">Account Information</a></li>
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
                                        {{-- <div class="col-md-6 mt-3">
                                            <label for="">District <span class="text-danger">*</span></label>
                                            <select name="district_id" id="district_id" class="form-control">
                                                <option value="">-- Select District --</option>
                                            </select>
                                        </div> --}}
                                        <div class="col-md-6 mt-3">
                                            <label for="">District <span class="text-danger">*</span></label>
                                            <select name="district_id" id="district_idx" class="form-control">
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

                                        @if ($isShopDocumentUploadEnabled || ($isEdit && $shop->shop_document))
                                            <div class="col-md-12 mt-4">
                                                @if ($isShopDocumentUploadEnabled)
                                                    <label for="shop_document" class="form-label">
                                                        Shop Licence / GST Document
                                                        @if ($shopDocumentIsRequired)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    <input type="file" class="form-control" name="shop_document" id="shop_document" accept=".pdf,.jpg,.jpeg,.png" {{ $shopDocumentIsRequired ? 'required' : '' }}>
                                                @endif

                                                @if ($isEdit && $shop->shop_document)
                                                    <div class="mt-2">
                                                        <a href="{{ $shop->document }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            View Document
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($isAdminWhatsappOrderEnabled)
                        <div class="card mt-4">
                            <div class="card-body">
                                <label class="form-label d-block">{{ __('Enable WhatsApp Order Sharing') }}</label>
                                <input type="hidden" name="whatsapp_order_enabled" value="0">
                                <div class="form-check form-switch mt-2">
                                    <div style="display: flex; align-items:center;">
                                        <input style="margin-top: 0px !important; margin-right: 10px;"
                                            class="form-check-input" type="checkbox" role="switch"
                                            id="whatsapp_order_enabled" name="whatsapp_order_enabled" value="1"
                                            {{ old('whatsapp_order_enabled', $shop->whatsapp_order_enabled ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                            for="whatsapp_order_enabled">{{ __('Allow users to send this order to seller on WhatsApp') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
                                                <div id="amt-min-display" class="form-control-plaintext"
                                                    aria-hidden="true">1</div>
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
                                            old(
                                                'delivery_state_ids',
                                                $shop->deliverySetting?->selected_state_ids ?? [],
                                            ),
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

                                            <input type="text" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?"
                                                maxlength="10" name="state_charges[{{ $loop->index }}][charge]"
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

                                        <input type="text" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?"
                                            maxlength="10" name="" class="form-control state-charge-input"
                                            placeholder="₹ Delivery Charge">
                                    </div>
                                </template>
                            </div>

                            <!-- Manual -->
                            {{-- <div id="manual_box" class="delivery-box d-none">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="update_when_shipped"
                                        value="1"
                                        {{ old('update_when_shipped', $shop->deliverySetting->update_when_shipped ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold">
                                        Update delivery charge when shipped
                                    </label>
                                </div>
                            </div> --}}

                        </div>
                    </div>

                    <p class="text-danger mt-2" id="deliveryChargeError"></p>

                    <div class="mt-4">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="3">« Previous</button>
                        <button type="button" class="btn btn-primary next-btn float-end" data-next="5">Next
                            &raquo;</button>
                    </div>
                </div>

                <!-- STEP 5 - Delivery API -->
                <div class="step-content step-5 mt-3" style="display:none;">
                    <input type="hidden" name="delivery_api_enabled" value="0">
                    <div class="card mt-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fa-solid fa-plug"></i> {{ __('Delivery API Provider') }}</h5>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="delivery_api_enabled"
                                    name="delivery_api_enabled" value="1"
                                    {{ old('delivery_api_enabled', $shop->deliverySetting?->delivery_api_enabled ?? !empty($shop->deliverySetting?->delivery_provider)) ? 'checked' : '' }}>
                                <label class="form-check-label ms-2" for="delivery_api_enabled">
                                    {{ __('Turn On / Off') }}
                                </label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold mb-1 d-block">{{ __('Select API Provider') }}</label>
                                    <select name="delivery_provider" class="form-control select-arrow">
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
                                <div class="col-md-4 mb-3 provider-key-wrap" style="display:none;">
                                    <label class="fw-bold mb-1 d-block" id="provider_api_key_label">Provider API Key</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="provider_api_key"
                                            id="provider_api_key"
                                            value="{{ old('provider_api_key', $isEdit ? $shop->deliverySetting?->provider_api_key ?? '' : '') }}"
                                            placeholder="Enter provider API key" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary toggle-secret-btn"
                                            data-target="provider_api_key"
                                            aria-label="Toggle provider API key visibility">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3 provider-secret-wrap" style="display:none;">
                                    <label class="fw-bold mb-1 d-block" id="provider_api_secret_label">Provider API Secret</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="provider_api_secret"
                                            id="provider_api_secret"
                                            value="{{ old('provider_api_secret', $isEdit ? $shop->deliverySetting?->provider_api_secret ?? '' : '') }}"
                                            placeholder="Enter provider API secret" autocomplete="new-password">
                                        <button type="button" class="btn btn-outline-secondary toggle-secret-btn"
                                            data-target="provider_api_secret"
                                            aria-label="Toggle provider API secret visibility">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="4">&laquo;
                            Previous</button>
                        <button type="button" class="btn btn-primary next-btn float-end" data-next="6">Next
                            &raquo;</button>
                    </div>
                </div>

                <p class="text-danger mt-2" id="deliveryApiError"></p>

                <!-- STEP 6 - Payment Settings -->
                <div class="step-content step-6 mt-3" style="display:none;">
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
                                            <input style="margin-top: 0px !important; margin-right: 10px;"
                                                class="form-check-input" type="checkbox" role="switch"
                                                id="cash_on_delivery_enabled" name="cash_on_delivery_enabled"
                                                value="1"
                                                {{ old('cash_on_delivery_enabled', $shop->cash_on_delivery_enabled ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="cash_on_delivery_enabled">{{ __('Allow cash on delivery for this shop') }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mt-3">
                                    <label class="form-label d-block">{{ __('Enable Online Payment') }}</label>
                                    <input type="hidden" name="online_payment_enabled" value="0">
                                    <div class="form-check form-switch mt-2">
                                        <div style="display: flex; align-items:center;">
                                            <input style="margin-top: 0px !important; margin-right: 10px;"
                                                class="form-check-input" type="checkbox" role="switch"
                                                id="online_payment_enabled" name="online_payment_enabled" value="1"
                                                {{ old('online_payment_enabled', $shop->online_payment_enabled ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="online_payment_enabled">{{ __('Allow online payment for this shop') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-md-4 mt-3 online-payment-provider-wrap">
                                            <label class="form-label">{{ __('Payment Provider') }}</label>
                                            <select name="online_payment_provider" class="form-control select-arrow">
                                                <option value="">-- Select Provider --</option>
                                                <option value="razorpay"
                                                    {{ old('online_payment_provider', $shop->online_payment_provider ?? '') === 'razorpay' ? 'selected' : '' }}>
                                                    Razorpay
                                                </option>
                                                <option value="cashfree"
                                                    {{ old('online_payment_provider', $shop->online_payment_provider ?? '') === 'cashfree' ? 'selected' : '' }}>
                                                    Cashfree
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mt-3 razorpay-fields">
                                            <label class="form-label">{{ __('Razorpay Key ID') }}</label>
                                            <input type="text" class="form-control" name="razorpay_key_id"
                                                value="{{ old('razorpay_key_id', data_get($onlinePaymentConfig, 'razorpay.key_id', '')) }}"
                                                placeholder="Enter Razorpay key ID" autocomplete="off">
                                        </div>

                                        <div class="col-md-4 mt-3 razorpay-fields">
                                            <label class="form-label">{{ __('Razorpay Key Secret') }}</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="razorpay_key_secret"
                                                    id="razorpay_key_secret"
                                                    value="{{ old('razorpay_key_secret', data_get($onlinePaymentConfig, 'razorpay.key_secret', '')) }}"
                                                    placeholder="{{ $isEdit ? 'Leave empty to keep existing secret' : 'Enter Razorpay key secret' }}"
                                                    autocomplete="new-password">
                                                <button type="button" class="btn btn-outline-secondary toggle-secret-btn"
                                                    data-target="razorpay_key_secret"
                                                    aria-label="Toggle Razorpay secret visibility">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mt-3 cashfree-fields">
                                            <label class="form-label">{{ __('Cashfree App ID') }}</label>
                                            <input type="text" class="form-control" name="cashfree_app_id"
                                                value="{{ old('cashfree_app_id', data_get($onlinePaymentConfig, 'cashfree.app_id', '')) }}"
                                                placeholder="Enter Cashfree app ID" autocomplete="off">
                                        </div>

                                        <div class="col-md-4 mt-3 cashfree-fields">
                                            <label class="form-label">{{ __('Cashfree Secret Key') }}</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="cashfree_secret_key"
                                                    id="cashfree_secret_key"
                                                    value="{{ old('cashfree_secret_key', data_get($onlinePaymentConfig, 'cashfree.secret_key', '')) }}"
                                                    placeholder="{{ $isEdit ? 'Leave empty to keep existing secret' : 'Enter Cashfree secret key' }}"
                                                    autocomplete="new-password">
                                                <button type="button" class="btn btn-outline-secondary toggle-secret-btn"
                                                    data-target="cashfree_secret_key"
                                                    aria-label="Toggle Cashfree secret visibility">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
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
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="5">&laquo;
                            Previous</button>
                        @if (!$isEdit)
                            <button type="button" class="btn btn-primary next-btn float-end" data-next="7">Next
                                &raquo;</button>
                        @endif
                    </div>
                </div>


                <!-- STEP 7 - Account Information -->
                <div class="step-content step-7 mt-3" style="display:none;">
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
                        <button type="button" class="btn btn-secondary prev-btn" data-prev="6">&laquo;
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
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('store_since');
            const pincodeInput = document.querySelector('[name="pincode"]');
            const phoneInput = document.querySelector('[name="phone"]');
            const whatsappInput = document.querySelector('[name="whatsapp_number"]');
            const minOrderAmountInput = document.querySelector('[name="min_order_amount"]');

            if (input) {
                const today = new Date().toISOString().split('T')[0];
                input.setAttribute('max', today);

                // Safety: prevent manual future date typing
                input.addEventListener('change', function() {
                    if (this.value > today) {
                        this.value = today;
                    }
                });
            }

            if (pincodeInput) {
                pincodeInput.setAttribute('inputmode', 'numeric');
                pincodeInput.setAttribute('maxlength', '6');

                pincodeInput.addEventListener('input', function() {
                    let value = (this.value || '').replace(/\D/g, '');
                    if (value.length > 6) {
                        value = value.slice(0, 6);
                    }
                    this.value = value;
                });
            }

            function setupDigitsOnlyInput(el, maxLength) {
                if (!el) return;

                el.setAttribute('inputmode', 'numeric');
                if (maxLength) {
                    el.setAttribute('maxlength', String(maxLength));
                }

                const initialValue = (el.value || '').replace(/\D/g, '');
                const normalizedInitial = maxLength ? initialValue.slice(0, maxLength) : initialValue;
                el.value = normalizedInitial;
                el.dataset.lastValidValue = normalizedInitial;

                el.addEventListener('input', function() {
                    const rawValue = (this.value || '').toString();
                    const isDigitsOnly = /^\d*$/.test(rawValue);

                    if (!isDigitsOnly) {
                        this.value = this.dataset.lastValidValue || '';
                        return;
                    }

                    let value = rawValue;
                    if (maxLength && value.length > maxLength) {
                        value = value.slice(0, maxLength);
                        this.value = value;
                    }

                    this.dataset.lastValidValue = value;
                });
            }

            setupDigitsOnlyInput(phoneInput, 15);
            setupDigitsOnlyInput(whatsappInput, 15);
            setupDigitsOnlyInput(minOrderAmountInput, 8);
        });
    </script>
    <script>
        const IS_EDIT = @json($isEdit);
        const IS_PUBLIC = @json($isPublicRegistration);
        const HAS_PROVIDER_API_KEY = @json(!empty($shop->deliverySetting?->provider_api_key));
        const HAS_PROVIDER_API_SECRET = @json(!empty($shop->deliverySetting?->provider_api_secret));
        const HAS_RAZORPAY_KEY_ID = @json(!empty(data_get($shop->online_payment_config, 'razorpay.key_id')));
        const HAS_RAZORPAY_KEY_SECRET = @json(!empty(data_get($shop->online_payment_config, 'razorpay.key_secret')));
        const HAS_CASHFREE_APP_ID = @json(!empty(data_get($shop->online_payment_config, 'cashfree.app_id')));
        const HAS_CASHFREE_SECRET_KEY = @json(!empty(data_get($shop->online_payment_config, 'cashfree.secret_key')));
        const SHOP_DOCUMENT_UPLOAD_ENABLED = @json($isShopDocumentUploadEnabled);
        const HAS_EXISTING_SHOP_DOCUMENT = @json($isEdit && !empty($shop->shop_document));
        const DELIVERY_PROVIDER_VALIDATE_URL = @json(route('shop.shop.validateDeliveryProvider'));
        const PAYMENT_PROVIDER_VALIDATE_URL = @json(route('shop.shop.validatePaymentProvider'));
        const CURRENT_SHOP_ID = @json($isEdit ? $shop->id : null);
        $(document).ready(function() {

            const strictNumericSelector =
                'input[name="phone"], input[name="whatsapp_number"], input[name="min_order_amount"]';

            function normalizeNumericField($el, maxLen = null) {
                let value = ($el.val() || '').toString().replace(/\D/g, '');
                if (maxLen && value.length > maxLen) {
                    value = value.slice(0, maxLen);
                }
                $el.val(value);
                $el.attr('inputmode', 'numeric');
                $el.attr('pattern', '[0-9]*');
                if (maxLen) {
                    $el.attr('maxlength', String(maxLen));
                }
            }

            $(strictNumericSelector).each(function() {
                const name = ($(this).attr('name') || '').toString();
                const maxLen = name === 'min_order_amount' ? 8 : 15;
                normalizeNumericField($(this), maxLen);
            });

            // Block non-digit key entry (except control/navigation keys)
            $(document).on('keydown', strictNumericSelector, function(e) {
                const allowedKeys = [
                    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                    'Home', 'End'
                ];

                if (allowedKeys.includes(e.key) || e.ctrlKey || e.metaKey) {
                    return;
                }

                if (!/^\d$/.test(e.key)) {
                    e.preventDefault();
                }
            });

            // Prevent non-digit paste
            $(document).on('paste', strictNumericSelector, function(e) {
                const pasted = (e.originalEvent || e).clipboardData?.getData('text') || '';
                if (!/^\d+$/.test(pasted)) {
                    e.preventDefault();
                }
            });

            // Prevent drag-drop text into these inputs
            $(document).on('drop', strictNumericSelector, function(e) {
                e.preventDefault();
            });

            // Final safeguard if browser/mobile IME injects invalid characters
            $(document).on('input', strictNumericSelector, function() {
                const name = ($(this).attr('name') || '').toString();
                const maxLen = name === 'min_order_amount' ? 8 : 15;
                normalizeNumericField($(this), maxLen);
            });

            function toggleOnlinePaymentCredentialFields() {
                const onlineEnabled = $('input[name="online_payment_enabled"]:checked').val() === '1';
                const provider = ($('select[name="online_payment_provider"]').val() || '').toString().trim()
                    .toLowerCase();

                $('.online-payment-provider-wrap').hide();
                $('.razorpay-fields').hide();
                $('.cashfree-fields').hide();

                if (!onlineEnabled) {
                    return;
                }

                $('.online-payment-provider-wrap').show();

                if (provider === 'razorpay') {
                    $('.razorpay-fields').show();
                } else if (provider === 'cashfree') {
                    $('.cashfree-fields').show();
                }
            }

            const $onlinePaymentProvider = $('select[name="online_payment_provider"]');
            const $razorpayKeyId = $('input[name="razorpay_key_id"]');
            const $razorpayKeySecret = $('input[name="razorpay_key_secret"]');
            const $cashfreeAppId = $('input[name="cashfree_app_id"]');
            const $cashfreeSecretKey = $('input[name="cashfree_secret_key"]');

            const paymentProviderCredentials = {
                razorpay: {
                    key_id: '',
                    key_secret: ''
                },
                cashfree: {
                    app_id: '',
                    secret_key: ''
                },
            };

            let activePaymentProvider = ($onlinePaymentProvider.val() || '').toString().trim().toLowerCase();

            function cachePaymentCredentials(provider) {
                if (provider === 'razorpay') {
                    paymentProviderCredentials.razorpay.key_id = ($razorpayKeyId.val() || '').toString();
                    paymentProviderCredentials.razorpay.key_secret = ($razorpayKeySecret.val() || '').toString();
                    return;
                }

                if (provider === 'cashfree') {
                    paymentProviderCredentials.cashfree.app_id = ($cashfreeAppId.val() || '').toString();
                    paymentProviderCredentials.cashfree.secret_key = ($cashfreeSecretKey.val() || '').toString();
                }
            }

            function applyPaymentCredentials(provider) {
                if (provider === 'razorpay') {
                    $razorpayKeyId.val(paymentProviderCredentials.razorpay.key_id || '');
                    $razorpayKeySecret.val(paymentProviderCredentials.razorpay.key_secret || '');
                    return;
                }

                if (provider === 'cashfree') {
                    $cashfreeAppId.val(paymentProviderCredentials.cashfree.app_id || '');
                    $cashfreeSecretKey.val(paymentProviderCredentials.cashfree.secret_key || '');
                }
            }

            if (activePaymentProvider === 'razorpay') {
                cachePaymentCredentials('razorpay');
                $cashfreeAppId.val('');
                $cashfreeSecretKey.val('');
            } else if (activePaymentProvider === 'cashfree') {
                cachePaymentCredentials('cashfree');
                $razorpayKeyId.val('');
                $razorpayKeySecret.val('');
            } else {
                $razorpayKeyId.val('');
                $razorpayKeySecret.val('');
                $cashfreeAppId.val('');
                $cashfreeSecretKey.val('');
            }

            function toggleDeliveryProviderCredentialFields() {
                const provider = ($('select[name="delivery_provider"]').val() || '').toString().trim()
                    .toLowerCase();

                const keyLabel = $('#provider_api_key_label');
                const secretLabel = $('#provider_api_secret_label');

                $('.provider-key-wrap').hide();
                $('.provider-secret-wrap').hide();

                if (!provider) {
                    return;
                }

                let providerName = 'Provider';
                if (provider === 'shiprocket') {
                    providerName = 'Shiprocket';
                } else if (provider === 'delhivery') {
                    providerName = 'Delhivery';
                }

                if (keyLabel.length) {
                    keyLabel.text(providerName + ' API Key');
                }
                if (secretLabel.length) {
                    secretLabel.text(providerName + ' API Secret');
                }

                if (provider === 'delhivery') {
                    $('.provider-key-wrap').show();
                    return;
                }

                // shiprocket and any other provider: show both
                $('.provider-key-wrap').show();
                $('.provider-secret-wrap').show();
            }

            const $deliveryProvider = $('select[name="delivery_provider"]');
            const $deliveryApiKey = $('input[name="provider_api_key"]');
            const $deliveryApiSecret = $('input[name="provider_api_secret"]');

            const deliveryProviderCredentials = {
                shiprocket: {
                    api_key: '',
                    api_secret: ''
                },
                delhivery: {
                    api_key: '',
                    api_secret: ''
                },
            };

            let activeDeliveryProvider = ($deliveryProvider.val() || '').toString().trim().toLowerCase();

            function cacheDeliveryCredentials(provider) {
                if (!provider || !deliveryProviderCredentials[provider]) {
                    return;
                }

                deliveryProviderCredentials[provider].api_key = ($deliveryApiKey.val() || '').toString();
                deliveryProviderCredentials[provider].api_secret = ($deliveryApiSecret.val() || '').toString();
            }

            function applyDeliveryCredentials(provider) {
                if (!provider || !deliveryProviderCredentials[provider]) {
                    $deliveryApiKey.val('');
                    $deliveryApiSecret.val('');
                    return;
                }

                $deliveryApiKey.val(deliveryProviderCredentials[provider].api_key || '');
                $deliveryApiSecret.val(deliveryProviderCredentials[provider].api_secret || '');
            }

            if (activeDeliveryProvider) {
                cacheDeliveryCredentials(activeDeliveryProvider);
            } else {
                $deliveryApiKey.val('');
                $deliveryApiSecret.val('');
            }

            toggleOnlinePaymentCredentialFields();
            toggleDeliveryProviderCredentialFields();

            $(document).on('change', 'select[name="online_payment_provider"]', function() {
                cachePaymentCredentials(activePaymentProvider);
                activePaymentProvider = ($(this).val() || '').toString().trim().toLowerCase();
                applyPaymentCredentials(activePaymentProvider);
                toggleOnlinePaymentCredentialFields();
            });

            $(document).on('change', 'input[name="online_payment_enabled"]', function() {
                cachePaymentCredentials(activePaymentProvider);
                toggleOnlinePaymentCredentialFields();
            });

            $(document).on('change', 'select[name="delivery_provider"]', function() {
                cacheDeliveryCredentials(activeDeliveryProvider);
                activeDeliveryProvider = ($(this).val() || '').toString().trim().toLowerCase();
                applyDeliveryCredentials(activeDeliveryProvider);
                toggleDeliveryProviderCredentialFields();
            });

            $(document).on('change', 'input[name="delivery_api_enabled"]', function() {
                cacheDeliveryCredentials(activeDeliveryProvider);
                toggleDeliveryProviderCredentialFields();
            });

            $(document).on('click', '.toggle-secret-btn', function() {
                const targetId = $(this).data('target');
                const $input = $('#' + targetId);
                const $icon = $(this).find('i');

                if (!$input.length) {
                    return;
                }

                const isPassword = $input.attr('type') === 'password';
                $input.attr('type', isPassword ? 'text' : 'password');

                $icon.toggleClass('fa-eye', !isPassword);
                $icon.toggleClass('fa-eye-slash', isPassword);
            });

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
                    const maxRaw = (maxInput.value || '').toString().trim();
                    const chargeRaw = (chargeInput.value || '').toString().trim();
                    const max = parseFloat(maxRaw);
                    const charge = parseFloat(chargeRaw);

                    /* VALIDATIONS */
                    if (maxRaw === '' || chargeRaw === '' || Number.isNaN(max) || Number.isNaN(charge)) {
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
                    const existingChargeValues = Array.from(document.querySelectorAll(
                            '.amount-card input[name*="[charge]"]'))
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

            // State-wise delivery charge: allow numbers with optional decimal (max 2 digits)
            $(document).on('input', '.state-charge-input', function() {
                let value = ($(this).val() || '').toString().replace(/[^\d.]/g, '');

                const firstDotIndex = value.indexOf('.');
                if (firstDotIndex !== -1) {
                    value = value.slice(0, firstDotIndex + 1) + value.slice(firstDotIndex + 1).replace(
                        /\./g, '');
                }

                const parts = value.split('.');
                if (parts.length > 1) {
                    parts[1] = parts[1].slice(0, 2);
                    value = parts[0] + '.' + parts[1];
                }

                if (value.length > 10) {
                    value = value.slice(0, 10);
                }

                $(this).val(value);
            });

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
                $('#deliveryApiError').text('');
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
                    addError(errors, fieldName,
                        `The ${fieldName.replace('_', ' ')} must be a file of type: jpg, png, jpeg, gif.`);
                }

                if (file.size > maxBytes) {
                    addError(errors, fieldName,
                        `The ${fieldName.replace('_', ' ')} must not be greater than 2 MB.`);
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
                const maxStep = IS_EDIT ? 6 : 7;

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
                    cash_on_delivery_enabled: 6,
                    online_payment_enabled: 6,
                    online_payment_provider: 6,
                    razorpay_key_id: 6,
                    razorpay_key_secret: 6,
                    cashfree_app_id: 6,
                    cashfree_secret_key: 6,
                    profile_photo: 1,
                    shop_logo: 1,
                    shop_banner: 1,
                    bussiness_categories_id: 2,
                    delivery_days: 3,
                    delivery_state_ids: 3,
                    delivery_api_enabled: 5,
                    delivery_provider: 5,
                    provider_api_key: 5,
                    provider_api_secret: 5,
                    email: 7,
                };

                if (field.startsWith('bussiness_categories_id')) return 2;
                if (field.startsWith('delivery_state_ids')) return 3;

                if (field.startsWith('amount_rules') || field.startsWith('state_charges') || field ===
                    'delivery_mode') {
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
                const selectedRadioVal = (name) => ($(`input[name="${name}"]:checked`).val() || '').toString()
                    .trim();
                const checkedCount = (selector) => $(selector + ':checked').length;

                const firstName = textVal('first_name');
                if (!firstName) addError(errors, 'first_name', 'The first name field is required.');
                if (firstName.length > 255) addError(errors, 'first_name',
                    'The first name may not be greater than 255 characters.');

                const phone = textVal('phone');
                if (!phone) {
                    addError(errors, 'phone', 'The phone field is required.');
                } else {
                    if (!/^\d+$/.test(phone)) addError(errors, 'phone',
                        'The phone field must contain numbers only.');
                    if (phone.length < 10) addError(errors, 'phone', 'The phone must be at least 10 characters.');
                    if (phone.length > 15) addError(errors, 'phone',
                        'The phone may not be greater than 15 characters.');
                }

                const shopName = textVal('shop_name');
                if (!shopName) addError(errors, 'shop_name', 'The shop name field is required.');
                if (shopName.length > 100) addError(errors, 'shop_name',
                    'The shop name may not be greater than 100 characters.');

                const storeType = textVal('store_type');
                if (!storeType) addError(errors, 'store_type', 'The store type field is required.');

                const whatsapp = textVal('whatsapp_number');
                if (!whatsapp) {
                    addError(errors, 'whatsapp_number', 'The whatsapp number field is required.');
                } else {
                    if (!/^\d+$/.test(whatsapp)) addError(errors, 'whatsapp_number',
                        'The whatsapp number field must contain numbers only.');
                    if (whatsapp.length < 10) addError(errors, 'whatsapp_number',
                        'The whatsapp number must be at least 10 characters.');
                    if (whatsapp.length > 15) addError(errors, 'whatsapp_number',
                        'The whatsapp number may not be greater than 15 characters.');
                }

                const address = textVal('address');
                if (!address) addError(errors, 'address', 'The address field is required.');
                if (address.length > 150) addError(errors, 'address',
                    'The address may not be greater than 150 characters.');

                if (!textVal('state_id')) addError(errors, 'state_id', 'The state field is required.');
                if (!textVal('district_id')) addError(errors, 'district_id', 'The district field is required.');

                const pincode = textVal('pincode');
                const indiaPincodeRegex = /^[1-9][0-9]{5}$/;
                if (!pincode) {
                    addError(errors, 'pincode', 'The pincode field is required.');
                } else if (!indiaPincodeRegex.test(pincode)) {
                    addError(errors, 'pincode',
                        'Please enter a valid Indian PIN code (6 digits, cannot start with 0).');
                }

                const minOrderAmount = textVal('min_order_amount');
                if (!minOrderAmount) {
                    addError(errors, 'min_order_amount', 'The min order amount field is required.');
                } else if (!/^\d+$/.test(minOrderAmount)) {
                    addError(errors, 'min_order_amount',
                        'The min order amount must contain numbers only (no decimals).');
                }

                const returnPolicy = textVal('return_policy');
                if (!returnPolicy) addError(errors, 'return_policy', 'The return policy field is required.');
                if (returnPolicy.length > 1250) addError(errors, 'return_policy',
                    'The return policy may not be greater than 1250 characters.');

                const description = textVal('description');
                if (description.length > 200) addError(errors, 'description',
                    'The description may not be greater than 200 characters.');

                const onlinePaymentEnabled = $('input[name="online_payment_enabled"]:checked').val() === '1';
                const cashOnDeliveryEnabled = $('input[name="cash_on_delivery_enabled"]:checked').val() === '1';
                const onlinePaymentProvider = textVal('online_payment_provider');

                if (!onlinePaymentEnabled && !cashOnDeliveryEnabled) {
                    addError(errors, 'cash_on_delivery_enabled',
                        'Either Cash on Delivery or Online Payment must be enabled.');
                }

                if (onlinePaymentEnabled) {
                    if (!onlinePaymentProvider) {
                        addError(errors, 'online_payment_provider', 'Please select an online payment provider.');
                    }

                    if (onlinePaymentProvider && !['razorpay', 'cashfree'].includes(onlinePaymentProvider)) {
                        addError(errors, 'online_payment_provider', 'Selected online payment provider is invalid.');
                    }
                }

                if (onlinePaymentEnabled && onlinePaymentProvider === 'razorpay') {
                    const razorpayKeyId = textVal('razorpay_key_id');
                    const razorpayKeySecret = textVal('razorpay_key_secret');
                    const cachedRazorpay = paymentProviderCredentials.razorpay || {
                        key_id: '',
                        key_secret: ''
                    };

                    if (!razorpayKeyId && !cachedRazorpay.key_id) {
                        addError(errors, 'razorpay_key_id',
                            'The Razorpay key ID field is required when Razorpay is selected.');
                    }

                    if (!razorpayKeySecret && !cachedRazorpay.key_secret) {
                        addError(errors, 'razorpay_key_secret',
                            'The Razorpay key secret field is required when Razorpay is selected.');
                    }
                }

                if (onlinePaymentEnabled && onlinePaymentProvider === 'cashfree') {
                    const cashfreeAppId = textVal('cashfree_app_id');
                    const cashfreeSecretKey = textVal('cashfree_secret_key');
                    const cachedCashfree = paymentProviderCredentials.cashfree || {
                        app_id: '',
                        secret_key: ''
                    };

                    if (!cashfreeAppId && !cachedCashfree.app_id) {
                        addError(errors, 'cashfree_app_id',
                            'The Cashfree app ID field is required when Cashfree is selected.');
                    }

                    if (!cashfreeSecretKey && !cachedCashfree.secret_key) {
                        addError(errors, 'cashfree_secret_key',
                            'The Cashfree secret key field is required when Cashfree is selected.');
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

                if (SHOP_DOCUMENT_UPLOAD_ENABLED) {
                    const shopDocumentInput = document.querySelector('[name="shop_document"]');
                    const shopDocumentFile = shopDocumentInput?.files && shopDocumentInput.files[0] ?
                        shopDocumentInput.files[0] :
                        null;

                    if (!shopDocumentFile && !HAS_EXISTING_SHOP_DOCUMENT) {
                        addError(errors, 'shop_document',
                            'The Shop Licence / GST Document field is required.');
                    }

                    if (shopDocumentFile) {
                        const allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                        const fileType = (shopDocumentFile.type || '').toLowerCase();
                        const fileName = (shopDocumentFile.name || '').toLowerCase();
                        const hasAllowedExtension = /\.(pdf|jpg|jpeg|png)$/.test(fileName);
                        const maxBytes = 4096 * 1024;

                        if (!(allowedMimes.includes(fileType) || hasAllowedExtension)) {
                            addError(errors, 'shop_document',
                                'The Shop Licence / GST Document must be a file of type: pdf, jpg, jpeg, png.');
                        }

                        if (shopDocumentFile.size > maxBytes) {
                            addError(errors, 'shop_document',
                                'The Shop Licence / GST Document must not be greater than 4 MB.');
                        }
                    }
                }

                if (!selectedRadioVal('delivery_days')) addError(errors, 'delivery_days',
                    'The delivery days field is required.');

                if (checkedCount('input[name="bussiness_categories_id[]"]') < 1) {
                    addError(errors, 'bussiness_categories_id', 'Please select at least one business category.');
                }

                if (checkedCount('input[name="delivery_state_ids[]"]') < 1) {
                    addError(errors, 'delivery_state_ids', 'Please select at least one delivery state.');
                }

                const deliveryMode = selectedRadioVal('delivery_mode');

                if (deliveryMode === 'amount_based') {
                    const amountRuleCount = $('input[name^="amount_rules["][name$="[min_amount]"]').length;
                    if (amountRuleCount < 1) {
                        addError(errors, 'amount_rules',
                            'Please add at least one amount range for amount based delivery charges.');
                    }
                }

                if (deliveryMode === 'state_wise') {
                    const $stateRows = $('#state_wise_box .state-charge-item');
                    if (!$stateRows.length) {
                        addError(errors, 'state_charges', 'Please add state charges for selected states.');
                    } else {
                        let missingCharge = false;
                        let invalidCharge = false;
                        $stateRows.each(function() {
                            const charge = ($(this).find('.state-charge-input').val() || '').toString()
                                .trim();
                            if (charge === '') {
                                missingCharge = true;
                                $(this).find('.state-charge-input').addClass('is-invalid');
                                return;
                            }

                            if (!/^\d+(\.\d{1,2})?$/.test(charge)) {
                                invalidCharge = true;
                                $(this).find('.state-charge-input').addClass('is-invalid');
                            }
                        });

                        if (missingCharge) {
                            addError(errors, 'state_charges',
                                'Please enter delivery charges for all selected states.');
                        } else if (invalidCharge) {
                            addError(errors, 'state_charges',
                                'State-wise delivery charges must be valid numbers (up to 2 decimals).');
                        }
                    }
                }

                // Validate provider API fields only when API toggle is enabled
                {
                    const deliveryApiEnabled = $('input[name="delivery_api_enabled"]').is(':checked');
                    const deliveryProvider = textVal('delivery_provider');
                    const providerApiKey = textVal('provider_api_key');
                    const providerApiSecret = textVal('provider_api_secret');
                    const allowedProviders = ['shiprocket', 'delhivery'];

                    if (deliveryApiEnabled && deliveryProvider && !allowedProviders.includes(deliveryProvider)) {
                        addError(errors, 'delivery_provider', 'Selected delivery API provider is invalid.');
                    }

                    if (deliveryApiEnabled && !deliveryProvider) {
                        addError(errors, 'delivery_provider', 'Please select a delivery API provider.');
                    }

                    if (deliveryApiEnabled && deliveryProvider) {
                        const cachedDelivery = deliveryProviderCredentials[deliveryProvider] || {
                            api_key: '',
                            api_secret: ''
                        };

                        if (!providerApiKey && !cachedDelivery.api_key) {
                            addError(errors, 'provider_api_key',
                                'The provider API key field is required when an API provider is selected.');
                        }

                        if (deliveryProvider === 'shiprocket' && !providerApiSecret && !cachedDelivery.api_secret) {
                            addError(errors, 'provider_api_secret',
                                'The provider API secret field is required for Shiprocket.');
                        }
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
                    if (password && password.length < 6) addError(errors, 'password',
                        'The password must be at least 6 characters.');

                    if (!passwordConfirmation) {
                        addError(errors, 'password_confirmation', 'The password confirmation field is required.');
                    } else if (passwordConfirmation.length < 6) {
                        addError(errors, 'password_confirmation',
                            'The password confirmation must be at least 6 characters.');
                    }
                } else {
                    if (password && password.length < 6) addError(errors, 'password',
                        'The password must be at least 6 characters.');
                    if (passwordConfirmation && passwordConfirmation.length < 6) {
                        addError(errors, 'password_confirmation',
                            'The password confirmation must be at least 6 characters.');
                    }
                }

                if ((password || passwordConfirmation) && password !== passwordConfirmation) {
                    addError(errors, 'password', 'The password and confirmation password do not match.');
                }

                return errors;
            }

            const initialStateId = $('#state_id').val();

            // const allDistrictOptions = $('#district_id option[data-state-id]').clone();
            const allDistrictOptions = @json($allDistricts);


            loadDistricts(initialStateId || null, SELECTED_DISTRICT_ID);

            function loadDistricts(stateId, selectedDistrictId = null) {

                const $districtSelect = $('#district_idx');
                var districtselect = document.getElementById('district_idx');

                $districtSelect
                    .empty()
                    .append('<option value="">-- Select District --</option>')
                    .prop('disabled', true);

                if (!stateId) {
                    $districtSelect.prop('disabled', false);
                    return;
                }

                const validDistricts = allDistrictOptions.filter(d =>
                    String(d.state_id) === String(stateId)
                );

                if (!validDistricts.length) {
                    $districtSelect
                        .append('<option value="">No districts found</option>')
                        .prop('disabled', false);
                    return;
                }

                $dstr = '';
                validDistricts.forEach(function(district) {

                    const selected =
                        String(district.id) === String(selectedDistrictId) ?
                        'selected' :
                        '';
                    districtselect.innerHTML += '<option value="' + district.id + '" ' + selected + '>' +
                        district.name +
                        '</option>';
                });

                $districtSelect.prop('disabled', false);

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
                    if (field === 'bussiness_categories_id' || field.startsWith(
                            'bussiness_categories_id.')) {
                        $('#businessCategoryError').text(messages[0]);
                        return;
                    }
                    if (field === 'delivery_state_ids' || field.startsWith('delivery_state_ids.')) {
                        $('#deliveryStateError').text(messages[0]);
                        return;
                    }
                    if (field === 'amount_rules' || field.startsWith('amount_rules.') || field ===
                        'state_charges' || field
                        .startsWith('state_charges.') || field === 'delivery_mode') {
                        $('#deliveryChargeError').text(messages[0]);
                        return;
                    }
                    if (field === 'delivery_api_enabled' || field === 'delivery_provider' || field === 'provider_api_key' || field ===
                        'provider_api_secret') {
                        $('#deliveryApiError').text(messages[0]);
                        return;
                    }
                    if (
                        field === 'cash_on_delivery_enabled' ||
                        field === 'online_payment_enabled' ||
                        field === 'online_payment_provider' ||
                        field === 'razorpay_key_id' ||
                        field === 'razorpay_key_secret' ||
                        field === 'cashfree_app_id' ||
                        field === 'cashfree_secret_key'
                    ) {
                        $('#paymentGatewayError').text(messages[0]);
                        return;
                    }
                    let input = $('[name="' + field + '"]');
                    if (!input.length && field.includes('.')) {
                        input = $('[name="' + field.replace(/\.(\d+)\./g, '[$1][').replace(/\.(\d+)/g,
                            '[$1]').replace(/\./g, '[') + ']"]');
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

            function focusFirstErrorField(errors) {
                const firstErrorField = Object.keys(errors || {})[0];
                if (!firstErrorField) {
                    return;
                }

                activateStep(stepForField(firstErrorField));
                const firstField = $('[name="' + firstErrorField + '"], [name="' + firstErrorField +
                    '[]"]:first');
                if (firstField.length) {
                    firstField.trigger('focus');
                }

                if ($('#formErrorBox').length) {
                    $('html, body').animate({
                        scrollTop: $('#formErrorBox').offset().top - 100
                    }, 300);
                }
            }

            function shouldValidateDeliveryProviderBeforeSubmit() {
                const enabled = $('input[name="delivery_api_enabled"]:checked').val() === '1';
                const provider = ($('select[name="delivery_provider"]').val() || '').toString().trim();
                return enabled && provider !== '';
            }

            function validateDeliveryProviderBeforeSubmit() {
                if (!shouldValidateDeliveryProviderBeforeSubmit()) {
                    return $.Deferred().resolve({
                        status: 'skipped'
                    }).promise();
                }

                const payload = new FormData();
                payload.append('_token', $('input[name="_token"]').val() || '');
                payload.append('delivery_api_enabled', '1');
                payload.append('delivery_provider', ($('select[name="delivery_provider"]').val() || '').toString()
                    .trim());
                payload.append('provider_api_key', ($('input[name="provider_api_key"]').val() || '').toString()
                    .trim());
                payload.append('provider_api_secret', ($('input[name="provider_api_secret"]').val() || '').toString()
                    .trim());
                payload.append('pincode', ($('input[name="pincode"]').val() || '').toString().trim());

                if (CURRENT_SHOP_ID) {
                    payload.append('shop_id', String(CURRENT_SHOP_ID));
                }

                return $.ajax({
                    url: DELIVERY_PROVIDER_VALIDATE_URL,
                    method: 'POST',
                    data: payload,
                    processData: false,
                    contentType: false,
                });
            }

            function shouldValidatePaymentProviderBeforeSubmit() {
                const onlineEnabled = $('input[name="online_payment_enabled"]:checked').val() === '1';
                const provider = ($('select[name="online_payment_provider"]').val() || '').toString().trim();
                return onlineEnabled && provider !== '';
            }

            function validatePaymentProviderBeforeSubmit() {
                if (!shouldValidatePaymentProviderBeforeSubmit()) {
                    return $.Deferred().resolve({
                        status: 'skipped'
                    }).promise();
                }

                const payload = new FormData();
                payload.append('_token', $('input[name="_token"]').val() || '');
                payload.append('online_payment_enabled', '1');
                payload.append('online_payment_provider', ($('select[name="online_payment_provider"]').val() || '')
                    .toString().trim());
                payload.append('razorpay_key_id', ($('input[name="razorpay_key_id"]').val() || '').toString()
                    .trim());
                payload.append('razorpay_key_secret', ($('input[name="razorpay_key_secret"]').val() || '')
                    .toString().trim());
                payload.append('cashfree_app_id', ($('input[name="cashfree_app_id"]').val() || '').toString()
                    .trim());
                payload.append('cashfree_secret_key', ($('input[name="cashfree_secret_key"]').val() || '')
                    .toString().trim());

                if (CURRENT_SHOP_ID) {
                    payload.append('shop_id', String(CURRENT_SHOP_ID));
                }

                return $.ajax({
                    url: PAYMENT_PROVIDER_VALIDATE_URL,
                    method: 'POST',
                    data: payload,
                    processData: false,
                    contentType: false,
                });
            }
            let termsModal = null;
            if (!IS_EDIT && !IS_PUBLIC) {
                const termsModalEl = document.getElementById('termsModal');
                termsModal = new bootstrap.Modal(termsModalEl);
            }

            const $ajaxSubmitBtn = $('#ajaxSubmitBtn');
            const submitBtnDefaultHtml = $ajaxSubmitBtn.html();

            function setAjaxSubmitLoading(isLoading) {
                if (!$ajaxSubmitBtn.length) {
                    return;
                }

                if (isLoading) {
                    $ajaxSubmitBtn.prop('disabled', true);
                    $ajaxSubmitBtn.html(
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...'
                    );
                    return;
                }

                $ajaxSubmitBtn.prop('disabled', false);
                $ajaxSubmitBtn.html(submitBtnDefaultHtml);
            }

            if (!IS_PUBLIC) {
                $('#ajaxSubmitBtn').on('click', async function() {
                    clearErrors();
                    setAjaxSubmitLoading(true);
                    const clientErrors = validateShopFormClient();
                    if (Object.keys(clientErrors).length) {
                        showErrors(clientErrors);
                        focusFirstErrorField(clientErrors);
                        setAjaxSubmitLoading(false);
                        return;
                    }

                    try {
                        await validateDeliveryProviderBeforeSubmit();
                    } catch (xhr) {
                        if (xhr && xhr.status === 422 && xhr.responseJSON?.errors) {
                            showErrors(xhr.responseJSON.errors);
                            focusFirstErrorField(xhr.responseJSON.errors);
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: 'Unable to validate delivery API credentials right now. Please try again.'
                            });
                        }

                        setAjaxSubmitLoading(false);
                        return;
                    }

                    try {
                        await validatePaymentProviderBeforeSubmit();
                    } catch (xhr) {
                        if (xhr && xhr.status === 422 && xhr.responseJSON?.errors) {
                            showErrors(xhr.responseJSON.errors);
                            focusFirstErrorField(xhr.responseJSON.errors);
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: 'Unable to validate payment credentials right now. Please try again.'
                            });
                        }

                        setAjaxSubmitLoading(false);
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
                            if (IS_EDIT && res.status ===
                                'success') { //EDIT MODE → direct success
                                setTimeout(() => {
                                    window.location.href = res.redirect;
                                }, 1200);
                                return;
                            }
                            if (!IS_EDIT && res.status ===
                                'terms_required') { //CREATE MODE → show terms modal
                                setAjaxSubmitLoading(false);
                                termsModal.show();
                                return;
                            }
                            if (res.status === 'success') { //FINAL CREATE SUCCESS
                                setTimeout(() => {
                                    window.location.href = res.redirect;
                                }, 1200);
                                return;
                            }

                            setAjaxSubmitLoading(false);
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                showErrors(xhr.responseJSON.errors);
                            }
                            setAjaxSubmitLoading(false);
                        }
                    });
                });

                $('#agreeAndSubmit').on('click', function() {
                    if (!$('#agreeTerms').is(':checked')) {
                        $('#termsError').removeClass('d-none');
                        setAjaxSubmitLoading(false);
                        return;
                    }
                    $('#termsError').addClass('d-none');
                    $('#terms_condition_status').val(1);
                    termsModal.hide();
                    $('#ajaxSubmitBtn').trigger('click');
                });
            }

            if (IS_PUBLIC) {
                $('#shopForm').on('submit', async function(e) {
                    if (this.dataset.deliveryApiValidated === '1') {
                        return;
                    }

                    clearErrors();
                    const clientErrors = validateShopFormClient();
                    if (Object.keys(clientErrors).length) {
                        e.preventDefault();
                        showErrors(clientErrors);
                        focusFirstErrorField(clientErrors);
                        return;
                    }

                    e.preventDefault();

                    try {
                        await validateDeliveryProviderBeforeSubmit();
                    } catch (xhr) {
                        if (xhr && xhr.status === 422 && xhr.responseJSON?.errors) {
                            showErrors(xhr.responseJSON.errors);
                            focusFirstErrorField(xhr.responseJSON.errors);
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: 'Unable to validate delivery API credentials right now. Please try again.'
                            });
                        }
                        return;
                    }

                    try {
                        await validatePaymentProviderBeforeSubmit();
                    } catch (xhr) {
                        if (xhr && xhr.status === 422 && xhr.responseJSON?.errors) {
                            showErrors(xhr.responseJSON.errors);
                            focusFirstErrorField(xhr.responseJSON.errors);
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: 'Unable to validate payment credentials right now. Please try again.'
                            });
                        }
                        return;
                    }

                    this.dataset.deliveryApiValidated = '1';
                    this.submit();
                });
            }
        });
    </script>
@endpush

<!-- custom.js file included -->
<!-- custom.css file included -->
