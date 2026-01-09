@extends('layouts.app')
@section('content')
    @php
        $isEdit = false;
        if (isset($product)) {
            $isEdit = true;
            // echo $product;
        }
    @endphp
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@section('header-title', $isEdit ? 'Edit Product' : __('Add New Product'))

@if ($isEdit)
    <script>
        window.EXISTING_BULK_PRICES = @json($product->bulkPrices ?? []);
        window.EXISTING_ITEM_DETAILS = @json($product->itemDetails ?? []);
        window.EXISTING_BULK_ITEMS = @json($product->bulkItems ?? []);
        window.EXISTING_VARIANTS = @json($product->variants ?? []);
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.EXISTING_ITEM_DETAILS) return;

        const container = document.getElementById('item-details-list');
        container.innerHTML = '';

        EXISTING_ITEM_DETAILS.forEach((item, i) => {
            container.insertAdjacentHTML('beforeend', `
            <div class="d-flex gap-2 mb-2 item-row">
                <input name="item_details[${i}][id]" type="hidden" value="${item.id}">
                <input name="item_details[${i}][name]" value="${item.item_name}" class="form-control">
                <input name="item_details[${i}][value]" value="${item.item_value}" class="form-control">
                <button type="button" class="btn btn-danger btn-sm remove-item">-</button>
            </div>
        `);
        });
    });
</script>

<div class="page-title">
    <div class="d-flex gap-2 align-items-center">
        {{ $isEdit ? 'Edit Product' : __('Add New Product') }}
    </div>
</div>

<form action="{{ $isEdit ? route('shop.product.update', $product->id) : route('shop.product.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="pricetype" value="null" id="pricetype">

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <button type="submit" class="btn btn-success">Save Product</button>
            </div>
            <ul class="nav nav-tabs mt-3" id="productTabs">
                <li class="nav-item"><a class="nav-link active" data-step="1" href="#">Product Details</a></li>
                <li class="nav-item"><a class="nav-link" data-step="2" href="#" id="priceProductTab">Price</a>
                </li>
                <li class="nav-item"><a class="nav-link" data-step="3" href="#" id="variantProductTab">Product
                        Variants</a></li>
                <li class="nav-item"><a class="nav-link" data-step="4" href="#">Images</a></li>
                <li class="nav-item"><a class="nav-link" data-step="5" href="#">SEO Information</a></li>
                <li class="nav-item"><a class="nav-link" data-step="6" href="#" id="bulkProductTab"> Bulk
                        Products</a></li>
            </ul>

            <!-- STEP 1 -->
            <div class="step-content step-1 mt-3">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input type="radio" name="condition_status" value="New"
                                    {{ old('condition_status', $product->condition_status ?? 'New') === 'New' ? 'checked' : '' }}>
                                <label class="form-check-label">New</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input type="radio" name="condition_status" value="Refurbished"
                                    {{ old('condition_status', $product->condition_status ?? '') === 'Refurbished' ? 'checked' : '' }}>
                                <label class="form-check-label">Refurbished</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <x-input label="Product Name" name="name"
                                value="{{ old('name', $isEdit ? $product->name : '') }}" required />
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <x-select label="Main Category" required name="main_category">
                                    <option value="" selected disabled>Select Main Category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ $isEdit && optional($product->categories->first())->id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </x-select>
                                @if ($isEdit)
                                    @php
                                        $catId = optional($product->categories->first())->id;
                                        $subId = optional($product->subCategories->first())->id;
                                        $childId = optional($product->childCategories->first())->id;
                                    @endphp
                                @endif
                                @error('main_category')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <x-select label="Sub Category" required name="sub_categories[]">
                                    <option value="" selected disabled>Select Sub Category</option>
                                </x-select>
                                @error('sub_categories')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <x-select label="Child Category" id="child-category" name="child_categories[]" multiple>
                                    <option value="" selected disabled>{{ __('Select Child Category') }}</option>
                                </x-select>
                                @error('child_categories')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="row mt-3 g-2">
                            <div class="col-md-6">
                                <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control"
                                    value="{{ old('quantity', $isEdit ? $product->quantity : 0) }}">
                                @error('quantity')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">MOQ <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="min_order_quantity"
                                    placeholder="Enter Minimum Order Quantity" min="1" value="{{ old('min_order_quantity', $isEdit ? $product->min_order_quantity : 0) }}">
                                @error('min_order_quantity')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <x-select label="Return Period" required name="return_period">
                                <option value="" disabled selected>--Select Return Period--</option>
                                @foreach ([0, 2, 5, 7, 10, 15, 30] as $days)
                                    <option value="{{ $days }}"
                                        {{ old('return_period', $product->return_period ?? '') == $days ? 'selected' : '' }}>
                                        {{ $days == 0 ? 'No Returns' : $days . ' Days' }}
                                    </option>
                                @endforeach
                            </x-select>
                            @error('return_period')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="mb-3">
                            <label for="description" class="form-label">Product Description <span
                                    class="text-danger">*</span></label>
                            <textarea name="description" id="description">
{{ old('description', $isEdit ? $product->description : '') }}
</textarea>
                            @error('description')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="row g-2">
                            <div class="col-3">
                                <label class="form-label">Video Type</label>
                                <select name="video_type" class="form-select">
                                    <option value="">None</option>
                                    <option value="youtube"
                                        {{ old('video_type', $product->video_type ?? '') === 'youtube' ? 'selected' : '' }}>
                                        YouTube
                                    </option>
                                    <option value="external"
                                        {{ old('video_type', $product->video_type ?? '') === 'external' ? 'selected' : '' }}>
                                        External
                                    </option>
                                </select>
                            </div>
                            <div class="col-9">
                                <label class="form-label">Video Link</label>
                                <input type="text" name="video_link" class="form-control"
                                    value="{{ old('video_link', $isEdit ? $product->video_link : '') }}">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="form-label">Item Details (Add multiple)</label>
                            <div id="item-details-list">
                                <div class="d-flex gap-2 mb-2 item-row" data-index="0">
                                    <input type="text" name="item_details[0][name]" class="form-control"
                                        placeholder="Item Name">
                                    <input type="text" name="item_details[0][value]" class="form-control"
                                        placeholder="Item Value">
                                    <button type="button" class="btn btn-danger btn-sm remove-item">-</button>
                                </div>

                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="add-item-detail">+ Add
                                More</button>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="2"> Next
                        &raquo;</button>
                </div>
            </div>

            <!-- STEP 2 - PRICE -->
            <div class="step-content step-2 mt-3" style="display:none;">
                <div class="row">
                    <div class="col-md-4">
                        <x-input label="MRP" name="mrp" type="number" step="0.01"
                            value="{{ old('mrp', $isEdit ? $product->price : '') }}" required />
                        @error('mrp')
                            <p class="text text-danger m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <x-input label="Selling Price" name="selling_price" type="number" step="0.01"
                            value="{{ old('selling_price', $isEdit ? $product->discount_price : '') }}" required />
                        @error('selling_price')
                            <p class="text text-danger m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <x-select label="GST(include)" required name="tax_percentage">
                            @foreach ([0, 5, 12, 18, 28, 40] as $tax)
                                <option value="{{ $tax }}"
                                    {{ old('tax_percentage', $product->tax_percentage ?? 0) == $tax ? 'selected' : '' }}>
                                    {{ $tax }}%
                                </option>
                            @endforeach
                        </x-select>
                        @error('tax_percentage')
                            <p class="text text-danger m-0">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label">Bulk Price (optional) - Maximum 5 tier</label>
                    <table class="table table-sm" id="bulk-table">
                        <thead>
                            <tr>
                                <th>Min Qty</th>
                                <th>Max Qty</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input id="bulk-min" readonly class="form-control" placeholder="Min Qty"
                                        type="number" min="1"></td>
                                <td><input id="bulk-max" class="form-control" placeholder="Max Qty" type="number"
                                        min="1"></td>
                                <td><input id="bulk-price" class="form-control" placeholder="Price" type="number"
                                        min="1"></td>
                                <td><button type="button" class="btn btn-primary" id="add-bulk">Add</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Added Bulk Pricing Display Area -->
                <div class="mt-3">
                    <h6>Added Bulk Pricing:</h6>
                    <div id="added-bulkprice-container">
                        <p class="text-muted small" id="no-bulkprice-message">No Bulk Prices added yet.</p>
                        <!-- Bulkprice will be displayed here -->
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="1">&laquo;
                        Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="3">Next
                        &raquo;</button>
                </div>
            </div>

            <!-- STEP 3 - PRODUCT VARIANTS -->
            <div class="step-content step-3 mt-3" style="display:none;">
                <div class="mt-4">
                    <label class="form-label">Product Variant Details</label>
                    <div class="alert alert-info small">Add size-only, color-only, or size+color variants. Each variant
                        row contains price & quantity.</div>
                    <!-- Variant Input Form -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Color (optional)</th>
                                        <th>Size (optional)</th>
                                        <th>Price <span class="text-danger">*</span></th>
                                        <th>Quantity <span class="text-danger">*</span></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <!-- <select id="variant-color" class="form-select">
                                                <option value="">-- Select Color --</option>
                                                @foreach ($colors as $color)
<option value="{{ $color->id }}">{{ $color->name }}</option>
@endforeach
                                            </select> -->
                                            <select id="variant-color" name="color_id"
                                                class="form-select colorSelect" style="width: 100%">
                                                <option value="">-- Select Color --</option>
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color->id }}"
                                                        data-color="{{ $color->color_code }}">
                                                        {{ $color->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select id="variant-size" class="form-select">
                                                <option value="">-- Select Size --</option>
                                                @foreach ($sizes as $size)
                                                    <option value="{{ $size->id }}">{{ $size->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input id="variant-price" class="form-control" placeholder="Price"
                                                type="number" min="1">
                                        </td>
                                        <td>
                                            <input id="variant-qty" class="form-control" placeholder="Qty"
                                                type="number">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary" id="add-variant">Add
                                                Variant</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Added Variants Display Area -->
                    <div class="mt-3">
                        <h6>Added Variants:</h6>
                        <div id="added-variants-container">
                            <p class="text-muted small" id="no-variants-message">No variants added yet.</p>
                            <!-- Variants will be displayed here -->
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="2">&laquo;
                        Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="4">Next
                        &raquo;</button>
                </div>
            </div>

            <!-- STEP 4 - IMAGES -->
            <div class="step-content step-4 mt-3" style="display:none;">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-body">
                            <h5>Thumbnail <small class="text-muted">(Ratio 1:1 500x500) <span
                                        class="text-danger">*</span></small></h5>
                            <label for="thumbnail" style="cursor:pointer;display:block;">
                                <img height="200"
                                    src="{{ $isEdit ? $product->thumbnail : 'https://placehold.co/500x500' }}"
                                    id="preview">
                            </label>
                            <input id="thumbnail" accept="image/*" type="file" name="thumbnail" class="d-none"
                                onchange="previewFile(event, 'preview')">
                            @error('thumbnail')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5>Additional Thumbnails <span class="text-primary">(Ratio 1:1, 500x500px)<small
                                            class="text-muted">-multiple</small></span></h5>
                                <input type="file" id="additionalInput" name="additional_images[]" multiple
                                    accept="image/*" class="d-none" onchange="handleAddImages(event)">
                                <button type="button" class="btn btn-primary mt-2"
                                    onclick="document.getElementById ('additionalInput').click()"><i
                                        class="fa fa-upload"></i> Choose Images</button>
                                <div id="additionalContainer" class="d-flex flex-wrap gap-3 mt-3">
                                </div>
                                @if ($isEdit)
                                    <script>
                                        function handleAdditionalImages(src) {
                                            const container = document.getElementById('additionalContainer');
                                            const div = document.createElement('div');
                                            div.style.width = '120px';
                                            div.style.height = '120px';
                                            div.classList.add('position-relative');
                                            div.innerHTML =
                                                `<img src="${src}" class="w-100 h-100 rounded border">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="this.parentElement.remove()">×</button>`;
                                            container.appendChild(div);
                                        }
                                    </script>
                                    @foreach ($product->medias as $media)
                                        @php
                                            $source = asset('default/upload.png');
                                            if (Storage::exists($media->src)) {
                                                $source = Storage::url($media->src);
                                            }
                                        @endphp
                                        <script>
                                            handleAdditionalImages('{{ $source }}')
                                        </script>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-12 mt-4">
                        <p class="small text-muted">If you want variant-specific images, you can upload them after saving the product (or in edit flow).</p>
                    </div> -->
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="3">&laquo;
                        Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="5">Next
                        &raquo;</button>
                </div>
            </div>

            <!-- STEP 5 - SEO INFORMATION -->
            <div class="step-content step-5 mt-3" style="display:none;">
                <div class="row">
                    <div class="card mt-4 mb-3">
                        <div class="card-body">
                            <div class="d-flex gap-2 border-bottom pb-2">
                                <i class="fa-solid fa-square-poll-vertical"></i>
                                <h5> {{ __('SEO Information') }} </h5>
                            </div>
                            <div class="mt-3">
                                <label for="uploadType" class="form-label"> {{ __('Meta Title') }} </label>
                                <x-input name="meta_title"
                                    value="{{ old('meta_title', $isEdit ? $product->meta_title : '') }}" />
                            </div>
                            <div class="mt-3">
                                <label for="uploadType" class="form-label"> {{ __('Meta Description') }} </label>
                                <textarea name="meta_description" class="form-control">
{{ old('meta_description', $isEdit ? $product->meta_description : '') }}
</textarea>
                                @error('meta_description')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mt-3">
                                <label for="tags" class="form-label">@lang('Meta Keywords')</label>
                                <select id="tags" name="meta_keywords[]" class="form-control selectTags"
                                    multiple style="width: 100%">
                                    @foreach (old('meta_keywords', $product->meta_keywords ?? []) as $keyword)
                                        <option value="{{ $keyword }}" selected>{{ $keyword }}</option>
                                    @endforeach
                                </select>
                                <small>@lang('Write keywords and Press enter to add new one')</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="4">&laquo;
                        Previous</button>
                </div>
            </div>

            <!-- STEP 6 -->
            <div class="step-content step-6 mt-3" style="display:none;">
                <div class="alert alert-info small">
                    Bulk products can be added only if variants and bulk pricing are NOT used.
                </div>

                <!-- Field Titles -->
                {{-- <div class="row g-2 mb-2 fw-semibold text-muted">
                    <div class="col-md-3">Item Name <span class="text-danger">*</span></div>
                    <div class="col-md-2">Quantity <span class="text-danger">*</span></div>
                    <div class="col-md-2">MOQ <span class="text-danger">*</span></div>
                    <div class="col-md-2">MRP <span class="text-danger">*</span></div>
                    <div class="col-md-2">Selling Price <span class="text-danger">*</span></div>
                    <div class="col-md-1"></div>
                </div>

                <div id="bulk-items-list">
                    <div class="row g-2 mb-2 bulk-item-row">
                        <div class="col-md-3">
                            <input type="text" name="bulk_items[0][name]" class="form-control"
                                placeholder="Item Name">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="bulk_items[0][quantity]" class="form-control"
                                placeholder="Qty">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="bulk_items[0][moq]" class="form-control" placeholder="MOQ">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="bulk_items[0][mrp]" class="form-control" placeholder="MRP">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="bulk_items[0][selling_price]" class="form-control"
                                placeholder="Selling Price">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger remove-bulk-item">-</button>
                            <button type="button" class="btn btn-success add-bulk-item"
                                id="add-bulk-item">+</button>
                        </div>
                    </div>
                </div> --}}

                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>MOQ</th>
                            <th>MRP</th>
                            <th>Selling</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input id="bulkitem-name" class="form-control"></td>
                            <td><input id="bulkitem-qty" class="form-control" type="number"></td>
                            <td><input id="bulkitem-moq" class="form-control" type="number"></td>
                            <td><input id="bulkitem-mrp" class="form-control" type="number"></td>
                            <td><input id="bulkitem-price" class="form-control" type="number"></td>
                            <td>
                                <button type="button" class="btn btn-primary" id="add-bulk-item">
                                    Add
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3">
                    <h6>Added Bulk Items</h6>
                    <div id="added-bulkitems-container">
                        <p class="text-muted small" id="no-bulkitem-message">
                            No bulk items added yet.
                        </p>
                    </div>
                </div>

                <!-- <button type="button" class="btn btn-primary btn-sm mt-2" id="add-bulk-item">
                    + Add More
                </button> -->
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    // Remove +,-,e, and letters from number inputs
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[type="number"]')) {
            // Remove everything that's not a digit
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        }
    });

    // Summernote
    $(document).ready(function() {
        $('#description').summernote();
    });

    // Tag
    $(document).ready(function() {
        $('.selectTags').select2({
            tags: true,
            tokenSeparators: [',', ' '],
            placeholder: 'Enter keywords',
            width: '100%'
        });
    });

    // Tabs navigation
    $(document).ready(function() {
        $('.next-btn').click(function() { // Next Button
            let nextStep = $(this).data('next');
            $('.step-content').hide();
            $('.step-' + nextStep).show();
            $('#productTabs .nav-link').removeClass('active');
            $('#productTabs .nav-link[data-step="' + nextStep + '"]').addClass('active');
        });
        $('.prev-btn').click(function() { // Previous Button
            let prevStep = $(this).data('prev');
            $('.step-content').hide();
            $('.step-' + prevStep).show();
            $('#productTabs .nav-link').removeClass('active');
            $('#productTabs .nav-link[data-step="' + prevStep + '"]').addClass('active');
        });
        $('#productTabs .nav-link').click(function(e) { //Tab Click Navigation
            e.preventDefault();
            let step = $(this).data('step');
            $('.step-content').hide();
            $('.step-' + step).show();
            $('#productTabs .nav-link').removeClass('active');
            $(this).addClass('active');
        });
    });

    // Category-Subcategory Select
    $(document).ready(function() {
        const $main = $('select[name="main_category"]');
        const $sub = $('select[name="sub_categories[]"]');
        const $child = $('select[name="child_categories[]"]');

        // Helper functions
        const resetSub = () => {
            $sub.empty().append('<option selected disabled>Select Sub Category</option>');
        };
        const resetChild = () => {
            $child.empty().append('<option selected disabled>Select Child Category</option>');
        };
        const notAvailable = (select, text) => {
            select.empty().append(
                `<option selected disabled>${text}</option>`
            );
        };


        @if ($isEdit)
            setSubCategory(Number('{{ $catId }}'), Number('{{ $subId }}'));
        @endif

        /* ---------------- Main → Sub ---------------- */
        $main.on('change', function() {
            let categoryId = $(this).val();
            setSubCategory(categoryId, null);
        });

        function setSubCategory(categoryId, subId) {
            resetSub();
            resetChild();

            if (!categoryId) {
                notAvailable($sub, 'Not Available');
                return;
            }

            $.get('/api/sub-categories', {
                category_id: categoryId
            }, function(res) {
                let subs = res?.data?.sub_categories || [];
                console.log(subs);
                console.log(subId);
                if (subs.length === 0) {
                    notAvailable($sub, 'Not Available');
                    notAvailable($child, 'Not Available');
                    return;
                }
                if (subId != null) {
                    subs.forEach(sub => {
                        $sub.append(
                            `<option value="${sub.id}" ${sub.id == subId ? 'selected' : ''}>${sub.name}</option>`
                        );
                    });
                    setchildCategory(subId,
                        @if (isset($childId))
                            Number('{{ $childId }}')
                        @else
                            null
                        @endif )
                } else if (subs.length === 1) {
                    $sub.append(
                        `<option value="${subs[0].id}" selected>${subs[0].name}</option>`
                    ).trigger('change');
                } else {
                    subs.forEach(sub => {
                        $sub.append(`<option value="${sub.id}">${sub.name}</option>`);
                    });
                }
            });
        }

        function setchildCategory(subCategoryId, childId) {
            resetChild();

            if (!subCategoryId) {
                toggleChildRequired(false); // ❌ not required
                notAvailable($child, 'Not Available');
                return;
            }

            $.get('/api/child-categories', {
                sub_category_id: subCategoryId
            }, function(res) {
                // let childs = res?.data || [];
                let childs = res.data?.child_categories || [];
                if (childs.length === 0) {
                    notAvailable($child, 'Not Available');
                    toggleChildRequired(false); // ❌ not required
                    return;
                }
                if (childId != null) {
                    childs.forEach(child => {
                        $child.append(
                            `<option value="${child.id}" ${child.id == childId ? 'selected' : ''}>${child.name}</option>`
                        );
                    });
                } else if (childs.length === 1) {
                    $child.append(
                        `<option value="${childs[0].id}" selected>${childs[0].name}</option>`
                    );
                } else {
                    childs.forEach(child => {
                        $child.append(
                            `<option value="${child.id}">${child.name}</option>`);
                    });
                }

                toggleChildRequired(true); // 🔒 REQUIRED ONLY WHEN DATA EXISTS
            });
        }

        /* ---------------- Sub → Child ---------------- */
        $sub.on('change', function() {
            let subCategoryId = $(this).val();
            setchildCategory(subCategoryId, null)
        });

    });

    function toggleChildRequired(isRequired) {
        const childSelect = document.getElementById('child-category');
        if (!childSelect) return;

        if (isRequired) {
            childSelect.required = true;
            childSelect.disabled = false;
        } else {
            childSelect.required = false;
            childSelect.disabled = true;
            childSelect.innerHTML = '<option value="">Not Available</option>';
        }
    }


    // Item details add-more 
    let itemIndex = 1;
    document.getElementById('add-item-detail').addEventListener('click', function() {
        let container = document.getElementById('item-details-list');

        let div = document.createElement('div');
        div.classList.add('d-flex', 'gap-2', 'mb-2', 'item-row');

        div.innerHTML = `
            <input type="text" name="item_details[${itemIndex}][name]" class="form-control" placeholder="Item Name">
            <input type="text" name="item_details[${itemIndex}][value]" class="form-control" placeholder="Item Value">
            <button type="button" class="btn btn-danger btn-sm remove-item">-</button>
        `;

        container.appendChild(div);
        itemIndex++;
    });
    // remove row
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.item-row').remove();
        }
    });

    // Thumbnail preview
    function previewFile(event, targetId) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // Additional images
    function handleAddImages(event) {
        const files = event.target.files;
        const container = document.getElementById('additionalContainer');
        [...files].forEach((file) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.style.width = '120px';
                div.style.height = '120px';
                div.classList.add('position-relative');
                div.innerHTML =
                    `<img src="${e.target.result}" class="w-100 h-100 rounded border">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="this.parentElement.remove()">×</button>`;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    // Color code
    $(document).ready(function() {
        function formatColorOption(state) {
            if (!state.id) {
                return state.text;
            }

            var colorCode = $(state.element).data('color');

            if (colorCode) {
                return $(
                    '<span>' +
                    '<span style="display:inline-block; width:15px; height:15px; background-color:' +
                    colorCode +
                    '; border-radius:3px; margin-right:8px; border:1px solid #ddd"></span>' +
                    state.text +
                    '</span>'
                );
            }

            return state.text;
        }
        $('.colorSelect').select2({
            templateResult: formatColorOption,
            templateSelection: formatColorOption
        });
    });
</script>

<!-- Product Variants and Bulk Prices -->
<script>
    function createUniqueId() {
        return Date.now() + Math.random().toString(36).substr(2, 9);
    }

    let addedVariants = [];
    let addedBulkRanges = [];

    // Add Variant
    document.getElementById('add-variant').addEventListener('click', function() {
        const colorSelect = document.getElementById('variant-color');
        const sizeSelect = document.getElementById('variant-size');
        const priceInput = document.getElementById('variant-price');
        const qtyInput = document.getElementById('variant-qty');

        const colorId = colorSelect.value;
        const colorName = colorSelect.options[colorSelect.selectedIndex]?.text || '';
        const sizeId = sizeSelect.value;
        const sizeName = sizeSelect.options[sizeSelect.selectedIndex]?.text || '';
        const price = parseFloat(priceInput.value);
        const qty = parseInt(qtyInput.value);

        // Validations
        if (!sizeId && !colorId) {
            showFlash('Please choose either size or color (or both)');
            return;
        }
        if (!price || price <= 0) {
            showFlash('Please enter a valid price');
            return;
        }
        if (!qty || qty < 0) {
            showFlash('Please enter a valid quantity');
            return;
        }
        const variantKey = `${colorId}-${sizeId}`;
        console.log(variantKey);
        console.log(addedVariants);
        if (addedVariants.includes(variantKey)) {
            showFlash('This color and size combination already exists!');
            return;
        }

        // Add to tracking array
        addedVariants.push(variantKey);
        // Hide "no variants" message
        const noVariantMsg = document.getElementById('no-variants-message');
        if (noVariantMsg) noVariantMsg.style.display = 'none';

        // Create display card
        const variantId = createUniqueId();
        const variantCard = document.createElement('div');
        variantCard.className = 'card mb-2 variant-card';
        variantCard.id = `variant-${variantId}`;

        variantCard.innerHTML = `
<div class="card-body py-2">
    <div class="row align-items-center">
        <div class="col-md-10">
            <div class="row g-2">

                            <div class="col-md-3">
                                <label class="small fw-bold">Color</label>
                                <div>${colorName}</div>
                                <input type="hidden" name="variants[${variantId}][color_id]" value="${colorId}">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="small fw-bold">Size</label>
                                <div>${sizeName}</div>
                                <input type="hidden" name="variants[${variantId}][size_id]" value="${sizeId}">
                            </div>
                            

                <div class="col-md-3">
                    <label class="small fw-bold">Price</label>
                    <input type="number"
                        class="form-control form-control-sm"
                        name="variants[${variantId}][price]"
                        value="${price}"
                        min="1"
                        required>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold">Quantity</label>
                    <input type="number"
                        class="form-control form-control-sm"
                        name="variants[${variantId}][quantity]"
                        value="${qty}"
                        min="0"
                        required>
                </div>

            </div>
        </div>

        <div class="col-md-2 text-end">
            <button type="button"
                class="btn btn-sm btn-danger delete-item"
                data-type="variant"
                data-id="${variantId}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
`;


        document.getElementById('added-variants-container').appendChild(variantCard);
        $(colorSelect).val(null).trigger('change');
        // Clear inputs
        colorSelect.value = '';
        sizeSelect.value = '';
        priceInput.value = '';
        qtyInput.value = '';
        colorSelect.focus();
    });

    // Add Bulk Price 
    document.getElementById('add-bulk').addEventListener('click', function() {
        const minInput = document.getElementById('bulk-min');
        const maxInput = document.getElementById('bulk-max');
        const priceInput = document.getElementById('bulk-price');

        const min = parseInt(minInput.value);
        const max = parseInt(maxInput.value);
        const price = parseFloat(priceInput.value);

        // Validation
        if (!min) {
            showFlash('Fill Min Quantity');
            return;
        }
        if (!min || !max || !price) {
            showFlash('Fill Min Quantity, Max Quantity and Price');
            return;
        }

        if (addedBulkRanges.length == 0) {
            if (min != 1) {
                showFlash('First minimum quantity must be 1');
                return;
            }
        }

        if (addedBulkRanges.length > 4) {
            showFlash('Maximum 5 tier allowed');
            return;
        }

        if (min >= max) {
            showFlash('Min quantity must be less than Max quantity');
            return;
        }

        const rangeKey = `${min}-${max}`;
        if (addedBulkRanges.includes(rangeKey)) {
            showFlash('This quantity range already exists!');
            return;
        }

        // Get all existing bulk ranges
        const existingRanges = [];
        document.querySelectorAll('.bulk-card').forEach(card => {
            const existingMin = parseInt(card.querySelector('input[name*="min_qty"]').value);
            const existingMax = parseInt(card.querySelector('input[name*="max_qty"]').value);
            existingRanges.push({
                min: existingMin,
                max: existingMax
            });
        });

        // Sort existing ranges by min quantity
        existingRanges.sort((a, b) => a.min - b.min);

        // Check for continuity and overlapping
        let continuityError = '';

        if (existingRanges.length > 0) {
            // Check if new range fits in continuity
            let prevRange = null;

            for (let i = 0; i < existingRanges.length; i++) {
                const range = existingRanges[i];

                // Check for overlapping with any existing range
                if ((min >= range.min && min <= range.max) ||
                    (max >= range.min && max <= range.max) ||
                    (min <= range.min && max >= range.max)) {
                    showFlash(`This quantity range overlaps with existing range (${range.min}-${range.max})!`);
                    return;
                }

                // Check for gaps in continuity
                if (prevRange) {
                    // There should be no gap between previous max and current min
                    if (prevRange.max + 1 !== range.min) {
                        showFlash(
                            `There's a gap in quantity ranges. After ${prevRange.max}, next range should start at ${prevRange.max + 1}`
                        );
                        return;
                    }
                }

                prevRange = range;
            }

            // Now check where the new range fits
            if (min < existingRanges[0].min) {
                // New range is before all existing ranges
                if (max + 1 !== existingRanges[0].min) {
                    showFlash(
                        `To add before existing range, max should be ${existingRanges[0].min - 1} for continuity`
                    );
                    return;
                }
            } else if (min > existingRanges[existingRanges.length - 1].max) {
                // New range is after all existing ranges
                if (min !== existingRanges[existingRanges.length - 1].max + 1) {
                    showFlash(
                        `To add after existing range, min should be ${existingRanges[existingRanges.length - 1].max + 1} for continuity`
                    );
                    return;
                }
            } else {
                // New range should fit between existing ranges
                let inserted = false;
                for (let i = 0; i < existingRanges.length - 1; i++) {
                    const currentRange = existingRanges[i];
                    const nextRange = existingRanges[i + 1];

                    if (min > currentRange.max && max < nextRange.min) {
                        // Check continuity with both sides
                        if (min !== currentRange.max + 1) {
                            showFlash(
                                `To insert between ranges, min should be ${currentRange.max + 1} for continuity`
                            );
                            return;
                        }
                        if (max + 1 !== nextRange.min) {
                            showFlash(
                                `To insert between ranges, max should be ${nextRange.min - 1} for continuity`
                            );
                            return;
                        }
                        inserted = true;
                        break;
                    }
                }

                if (!inserted) {
                    showFlash('Cannot determine where to insert this range. Please check continuity.');
                    return;
                }
            }
        }

        // 🔒 PRICE MUST DECREASE WITH QUANTITY
        if (existingRanges.length > 0) {

            // Get highest max range (last quantity slab)
            const highestRange = existingRanges.reduce((a, b) => {
                return a.max > b.max ? a : b;
            });

            // Get its price
            const lastPriceInput = Array.from(document.querySelectorAll('.bulk-card'))
                .find(card => {
                    return parseInt(card.querySelector('input[name*="max_qty"]').value) === highestRange
                        .max;
                })
                ?.querySelector('input[name*="price"]');

            const lastPrice = lastPriceInput ? parseFloat(lastPriceInput.value) : null;

            if (lastPrice !== null && price >= lastPrice) {
                showFlash(
                    `Bulk price must be lower than (${lastPrice})`
                );
                return;
            }
        }

        // If no existing ranges, just add the first one
        addedBulkRanges.push(rangeKey);

        // Hide "no bulk price" message
        const noVariantMsg = document.getElementById('no-bulkprice-message');
        if (noVariantMsg) noVariantMsg.style.display = 'none';

        // Create display card
        const bulkId = createUniqueId();
        const bulkCard = document.createElement('div');
        bulkCard.className = 'card mb-2 bulk-card';
        bulkCard.id = `bulk-${bulkId}`;

        bulkCard.innerHTML = `
<div class="card-body py-2">
    <div class="row align-items-center">
        <div class="col-md-10">
            <div class="row g-2">

                <div class="col-md-4">
                    <strong>Min Qty:</strong> ${min}
                    <input type="hidden" name="bulk[${bulkId}][min_qty]" value="${min}">
                </div>

                <div class="col-md-4">
                    <strong>Max Qty:</strong> ${max}
                    <input type="hidden" name="bulk[${bulkId}][max_qty]" value="${max}">
                </div>

                <div class="col-md-4">
                    <strong>Price</strong> ${price}
                    <input type="hidden" name="bulk[${bulkId}][price]" value="${price}">
                </div>

            </div>
        </div>

        <div class="col-md-2 text-end">
            <button type="button"
                class="btn btn-sm btn-danger delete-item"
                data-type="bulk"
                data-id="${bulkId}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
`;


        // Insert in correct position based on min quantity
        const container = document.getElementById('added-bulkprice-container');
        const bulkCards = Array.from(container.querySelectorAll('.bulk-card'));

        if (bulkCards.length === 0) {
            container.appendChild(bulkCard);
        } else {
            let inserted = false;
            for (let i = 0; i < bulkCards.length; i++) {
                const cardMin = parseInt(bulkCards[i].querySelector('input[name*="min_qty"]').value);
                if (min < cardMin) {
                    bulkCards[i].before(bulkCard);
                    inserted = true;
                    break;
                }
            }
            if (!inserted) {
                bulkCards[bulkCards.length - 1].after(bulkCard);
            }
        }

        // Clear inputs and suggest next min quantity
        if (existingRanges.length > 0) {
            const lastMax = existingRanges[existingRanges.length - 1].max;
            minInput.value = Math.max(lastMax + 1, max + 1);
        } else {
            minInput.value = max + 1;
        }
        maxInput.value = '';
        priceInput.value = '';
        minInput.focus();
        updateBulkDeleteButtons();
    });

    // Handle deletion of both variants and bulk prices 
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-item');
        if (!deleteBtn) return;

        const type = deleteBtn.dataset.type;
        const id = deleteBtn.dataset.id;
        const card = document.getElementById(`${type}-${id}`);

        if (card) {
            if (type === 'variant') {
                // Remove variant from tracking
                const colorId = card.querySelector('input[name*="color_id"]').value;
                const sizeId = card.querySelector('input[name*="size_id"]').value;
                const variantKey = `${colorId}-${sizeId}`;
                addedVariants = addedVariants.filter(key => key !== variantKey);

                // Show message if no variants left
                if (document.querySelectorAll('.variant-card').length === 1) {
                    const noVariantMsg = document.getElementById('no-variants-message');
                    if (noVariantMsg) noVariantMsg.style.display = 'none';
                }
            } else if (type === 'bulk') {
                // Remove bulk range from tracking
                const min = card.querySelector('input[name*="min_qty"]').value;
                const max = card.querySelector('input[name*="max_qty"]').value;
                const rangeKey = `${min}-${max}`;
                addedBulkRanges = addedBulkRanges.filter(key => key !== rangeKey);

                // Show message if no bulk prices left
                if (document.querySelectorAll('.bulk-card').length === 1) {
                    const noVariantMsg = document.getElementById('no-bulkprice-message');
                    if (noVariantMsg) noVariantMsg.style.display = 'none';
                }

                // Update min input suggestion after deletion
                updateMinInputSuggestion();
            } else if (type === 'bulkitem') {

                const card = document.getElementById(`bulkitem-${id}`);
                const name = card.querySelector('input[name*="[name]"]').value;

                addedBulkItems = addedBulkItems.filter(n => n !== name.toLowerCase());

                if (document.querySelectorAll('.bulkitem-card').length === 1) {
                    const noVariantMsg = document.getElementById('no-bulkitem-message');
                    if (noVariantMsg) noVariantMsg.style.display = 'block';
                }
            }

            card.remove();
            updateBulkDeleteButtons();
        }
    });

    function updateBulkDeleteButtons() {
        const bulkCards = document.querySelectorAll('.bulk-card');

        bulkCards.forEach((card, index) => {
            const btn = card.querySelector('.delete-item');
            if (!btn) return;

            // Only last card can be deleted
            btn.disabled = index !== bulkCards.length - 1;
            btn.style.opacity = index !== bulkCards.length - 1 ? '0.4' : '1';
        });
    }

    // Helper function to update min input suggestion
    function updateMinInputSuggestion() {
        const bulkCards = document.querySelectorAll('.bulk-card');
        const minInput = document.getElementById('bulk-min');

        if (bulkCards.length === 0) {
            minInput.placeholder = "Min Qty (start with 1)";
            return;
        }
        // Get the last max value
        const lastCard = bulkCards[bulkCards.length - 1];
        const lastMax = parseInt(lastCard.querySelector('input[name*="max_qty"]').value);

        minInput.placeholder = `Min Qty (suggestion: ${lastMax + 1})`;
    }
</script>

<!-- Disable Variants or Bulk Prices or Bulk Items -->
<!-- <script>
    function disableVariants(disable = true) {
        $('#variant-color, #variant-size, #variant-price, #variant-qty').prop('disabled', disable);
        $('#add-variant').prop('disabled', disable);

        if (disable && !variantDisabledNotified) {
            showFlash('Bulk pricing is added. Product variants are disabled.');
            variantDisabledNotified = true;
        }

        if (!disable) {
            variantDisabledNotified = false;
        }
    }

    function disableBulkPricing(disable = true) {
        $('#bulk-min, #bulk-max, #bulk-price').prop('disabled', disable);
        $('#add-bulk').prop('disabled', disable);

        if (disable && !bulkDisabledNotified) {
            showFlash('Product variants are added. Bulk pricing is disabled.');
            bulkDisabledNotified = true;
        }

        if (!disable) {
            bulkDisabledNotified = false;
        }
    }

    function hasVariants() {
        return document.querySelectorAll('.variant-card').length > 0;
    }

    function hasBulkPricing() {
        return document.querySelectorAll('.bulk-card').length > 0;
    }

    function syncExclusiveRules() {
        if (hasBulkPricing()) {
            disableVariants(true);
        } else {
            disableVariants(false);
        }

        if (hasVariants()) {
            disableBulkPricing(true);
        } else {
            disableBulkPricing(false);
        }
    }

    // Run on page load (important for edit / validation reload)
    document.addEventListener('DOMContentLoaded', syncExclusiveRules);

    // Re-check after add/remove
    document.addEventListener('click', function(e) {
        if (
            e.target.id === 'add-variant' ||
            e.target.id === 'add-bulk' ||
            e.target.closest('.delete-item')
        ) {
            setTimeout(syncExclusiveRules, 100);
        }
    });
</script> -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let variantDisabledNotified = false;
        let bulkDisabledNotified = false;
        const bulkTab = document.getElementById('bulkProductTab');

        // HELPERS
        function hasVariants() {
            return document.querySelectorAll('.variant-card').length > 0;
        }

        function hasBulkPricing() {
            return document.querySelectorAll('.bulk-card').length > 0;
        }

        function hasBulkItems() {
            return Array.from(document.querySelectorAll('.bulkitem-card')).some(card => {
                return (
                    card.querySelector('input[name*="[name]"]')?.value &&
                    card.querySelector('input[name*="[quantity]"]')?.value &&
                    card.querySelector('input[name*="[moq]"]')?.value &&
                    card.querySelector('input[name*="[mrp]"]')?.value &&
                    card.querySelector('input[name*="[selling_price]"]')?.value
                );
            });
        }


        // DISABLE FUNCTIONS 
        function disableVariants(disable = true) {
            $('#variant-color, #variant-size, #variant-price, #variant-qty').prop('disabled', disable);
            $('#add-variant').prop('disabled', disable);

            if (disable && !variantDisabledNotified) {
                // showFlash('Bulk pricing or Bulk products are added. Product variants are disabled.');
                variantDisabledNotified = true;
            }

            if (!disable) variantDisabledNotified = false;
        }

        function disableBulkPricing(disable = true) {
            $('#bulk-min, #bulk-max, #bulk-price').prop('disabled', disable);
            $('#add-bulk').prop('disabled', disable);

            if (disable && hasBulkItems() && !bulkDisabledNotified) {
                // showFlash('Product variants or Bulk products are added. Bulk pricing is disabled.');
                bulkDisabledNotified = true;
            }
            if (!disable) bulkDisabledNotified = false;
        }

        // BULK PRODUCTS TAB 
        function toggleBulkTab(disable = true) {
            bulkTab.classList.toggle('disabled', disable);
            bulkTab.style.pointerEvents = disable ? 'none' : 'auto';
            bulkTab.style.opacity = disable ? '0.5' : '1';

            // force redirect if currently inside
            const bulkStep = document.querySelector('.step-6');
            if (disable && bulkStep.style.display === 'block') {
                document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
                document.querySelector('.step-3').style.display = 'block';

                document.querySelectorAll('#productTabs .nav-link').forEach(tab => tab.classList.remove(
                    'active'));
                document.querySelector('#productTabs .nav-link[data-step="3"]').classList.add('active');
            }
        }

        function toggleTab(tabid, tabno, disable = true) {
            const bulkTab = document.getElementById(tabid);
            bulkTab.classList.toggle('disabled', disable);
            bulkTab.style.pointerEvents = disable ? 'none' : 'auto';
            bulkTab.style.opacity = disable ? '0.5' : '1';

            // force redirect if currently inside
            // const bulkStep = document.querySelector('.step-' + tabno);
            // if (disable && bulkStep.style.display === 'block') {
            //     document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
            //     document.querySelector('.step-3').style.display = 'block';

            //     document.querySelectorAll('#productTabs .nav-link').forEach(tab => tab.classList.remove(
            //         'active'));
            //     document.querySelector('#productTabs .nav-link[data-step="3"]').classList.add('active');
            // }
        }

        // MASTER RULE ENGINE 
        window.syncAllExclusiveRules = function() {
            let type = 'null';
            /* Rule 1: Variant ↔ Bulk Pricing */
            if (hasVariants()) {
                type = 'variant';
                toggleTab('bulkProductTab', '6', true);
                toggleTab('variantProductTab', '3', false);
                // toggleTab('priceProductTab', '2', true);
                disableBulkPricing(true);
                // disableBulkPricing(true);
            } else
            if (hasBulkPricing()) {
                type = 'bulkprice';
                // alert(type);
                toggleTab('bulkProductTab', '6', true);
                toggleTab('variantProductTab', '3', true);
                // toggleTab('priceProductTab', '2', false);
                disableBulkPricing(false);
                // disableVariants(true);
            } else
            if (hasBulkItems()) {
                type = 'bulkitem';
                toggleTab('bulkProductTab', '6', false);
                toggleTab('variantProductTab', '3', true);
                // toggleTab('priceProductTab', '2', true);
                disableBulkPricing(true);
            } else {
                toggleTab('bulkProductTab', '6', false);
                toggleTab('variantProductTab', '3', false);
                // toggleTab('priceProductTab', '2', false);
                disableBulkPricing(false);
            }

            if (hasBulkPricing()) {
                const existingRanges = [];
                document.querySelectorAll('.bulk-card').forEach(card => {
                    const existingMin = parseInt(card.querySelector('input[name*="min_qty"]')
                        .value);
                    const existingMax = parseInt(card.querySelector('input[name*="max_qty"]')
                        .value);
                    existingRanges.push({
                        min: existingMin,
                        max: existingMax
                    });
                });
                var lastmax = 0;
                for (let i = 0; i < existingRanges.length; i++) {
                    const range = existingRanges[i];
                    lastmax = range.max;
                }
                const minInput = document.getElementById('bulk-min');
                if (minInput) {
                    minInput.value = lastmax + 1;
                }
            } else {
                const minInput = document.getElementById('bulk-min');
                if (minInput) {
                    minInput.value = 1;
                }
            }

            const priceTypeInput = document.getElementById('pricetype');
            priceTypeInput.value = type;
            console.log('PriceType:', type);
        }

        // INITIAL RUN
        syncAllExclusiveRules();

        // RE-CHECK ON ACTIONS 
        document.addEventListener('click', function(e) {
            console.log(e.target.id);
            if (
                e.target.id === 'add-variant' ||
                e.target.id === 'add-bulk' ||
                e.target.id === 'add-bulk-item' ||
                e.target.closest('.delete-item') ||
                e.target.closest('.remove-bulk-item')
            ) {
                setTimeout(syncAllExclusiveRules, 100);
            }

        });




        // /* INITIAL SET (EDIT / CREATE LOAD) */
        // resolvePriceType();

        /* ALSO WATCH INPUT CHANGES (bulk items typing) */
        document.addEventListener('input', function(e) {
            if (e.target.closest('.bulkitem-card') || e.target.id.startsWith('bulkitem-')) {
                syncAllExclusiveRules();
            }
        });

    });
</script>

<script>
    let addedBulkItems = [];

    document.getElementById('add-bulk-item').addEventListener('click', function() {
        const bulkitemName = document.getElementById('bulkitem-name');
        const bulkitemQty = document.getElementById('bulkitem-qty');
        const bulkitemMoq = document.getElementById('bulkitem-moq');
        const bulkitemMrp = document.getElementById('bulkitem-mrp');
        const bulkitemPrice = document.getElementById('bulkitem-price');

        const name = bulkitemName.value.trim();
        const qty = bulkitemQty.value;
        const moq = bulkitemMoq.value;
        const mrp = bulkitemMrp.value;
        const price = bulkitemPrice.value;

        if (!name || !qty || !moq || !mrp || !price) {
            showFlash('Fill all bulk item fields');
            return;
        }

        if (addedBulkItems.includes(name.toLowerCase())) {
            showFlash('Bulk item name must be unique');
            return;
        }

        addedBulkItems.push(name.toLowerCase());
        const noVariantMsg = document.getElementById('no-bulkitem-message');
        if (noVariantMsg) noVariantMsg.style.display = 'none';

        const id = createUniqueId();

        const card = document.createElement('div');
        card.className = 'card mb-2 bulkitem-card';
        card.id = `bulkitem-${id}`;

        card.innerHTML = `
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">

                <input type="hidden" name="bulk_items[${id}][name]" value="${name}">


                <div class="col-md-3">
                    <input readonly class="form-control form-control-sm"
                        name="bulk_items[${id}][name]" value="${name}">
                </div>

                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm"
                        name="bulk_items[${id}][quantity]" value="${qty}">
                </div>

                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm"
                        name="bulk_items[${id}][moq]" value="${moq}">
                </div>

                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm"
                        name="bulk_items[${id}][mrp]" value="${mrp}">
                </div>

                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm"
                        name="bulk_items[${id}][selling_price]" value="${price}">
                </div>

                <div class="col-md-1 text-end">
                    <button type="button"
                        class="btn btn-sm btn-danger delete-item"
                        data-type="bulkitem"
                        data-id="${id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

            </div>
        </div>
    `;

        document.getElementById('added-bulkitems-container').appendChild(card);

        bulkitemName.value = '';
        bulkitemQty.value = '';
        bulkitemMoq.value = '';
        bulkitemMrp.value = '';
        bulkitemPrice.value = '';
    });
</script>

<!-- Bulk Items -->
<!-- <script>
    let bulkItemIndex = 1;

    document.getElementById('add-bulk-item').addEventListener('click', function() {
        const container = document.getElementById('bulk-items-list');
        const rows = container.querySelectorAll('.bulk-item-row');
        const lastRow = rows[rows.length - 1];

        let isValid = true;
        lastRow.querySelectorAll('input').forEach(input => {
            if (input.value.trim() === '') isValid = false;
        });

        if (!isValid) {
            showFlash('Please fill Item Name, Quantity, MOQ, MRP and Selling Price before adding more.');
            return;
        }

        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 bulk-item-row';

        row.innerHTML = `
            <div class="col-md-3">
                <input type="text" name="bulk_items[${bulkItemIndex}][name]" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="number" name="bulk_items[${bulkItemIndex}][quantity]" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="number" name="bulk_items[${bulkItemIndex}][moq]" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="number" name="bulk_items[${bulkItemIndex}][mrp]" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="number" name="bulk_items[${bulkItemIndex}][selling_price]" class="form-control">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger remove-bulk-item">-</button>
            </div>
        `;

        container.appendChild(row);
        bulkItemIndex++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-bulk-item')) {
            const rows = document.querySelectorAll('.bulk-item-row');
            if (rows.length > 1) {
                e.target.closest('.bulk-item-row').remove();
            }
        }
    });
</script> -->
<script>
    let bulkItemIndex = 1;
    document.addEventListener('click', function(e) {

        /* ADD */
        if (e.target.classList.contains('add-bulk-item')) {

            const container = document.getElementById('bulk-items-list');
            const rows = container.querySelectorAll('.bulk-item-row');
            const lastRow = rows[rows.length - 1];

            // let isValid = true;
            // lastRow.querySelectorAll('input').forEach(input => {
            //     if (!input.value.trim()) isValid = false;
            // });

            let isValid = true;

            lastRow.querySelectorAll('input').forEach(input => {
                if (!input.value.trim()) isValid = false;
            });

            // 🔒 CHECK DUPLICATE NAME
            const nameInput = lastRow.querySelector('input[name*="[name]"]');
            if (isDuplicateBulkItemName(nameInput)) {
                showFlash('Bulk item name must be unique');
                nameInput.focus();
                return;
            }


            if (!isValid) {
                showFlash('Please fill all fields before adding more.');
                return;
            }

            /* Hide + from all rows */
            rows.forEach(row => {
                row.querySelector('.add-bulk-item')?.classList.add('d-none');
                row.querySelector('.remove-bulk-item').disabled = false;
            });

            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 bulk-item-row';

            row.innerHTML = `
                <div class="col-md-3">
                    <input type="text" name="bulk_items[${bulkItemIndex}][name]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[${bulkItemIndex}][quantity]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[${bulkItemIndex}][moq]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[${bulkItemIndex}][mrp]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[${bulkItemIndex}][selling_price]" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger remove-bulk-item">-</button>
                    <button type="button" class="btn btn-success add-bulk-item">+</button>
                </div>
            `;

            container.appendChild(row);
            bulkItemIndex++;
        }

        /* REMOVE */
        if (e.target.classList.contains('remove-bulk-item')) {

            const container = document.getElementById('bulk-items-list');
            const row = e.target.closest('.bulk-item-row');
            row.remove();

            let rows = container.querySelectorAll('.bulk-item-row');

            /* 🟢 IF ALL ROWS REMOVED → ADD EMPTY ROW */
            if (rows.length === 0) {
                container.insertAdjacentHTML('beforeend', `
            <div class="row g-2 mb-2 bulk-item-row">
                <div class="col-md-3">
                    <input type="text" name="bulk_items[0][name]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[0][quantity]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[0][moq]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[0][mrp]" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="number" name="bulk_items[0][selling_price]" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger remove-bulk-item">-</button>
                    <button type="button" class="btn btn-success add-bulk-item">+</button>
                </div>
            </div>
        `);

                bulkItemIndex = 1;
                return;
            }

            /* 🔁 SHOW + ONLY ON LAST ROW */
            rows = container.querySelectorAll('.bulk-item-row');
            rows.forEach(r => r.querySelector('.add-bulk-item')?.classList.add('d-none'));
            rows[rows.length - 1]
                .querySelector('.add-bulk-item')
                ?.classList.remove('d-none');
        }

    });
</script>
<script>
    function isDuplicateBulkItemName(currentInput) {
        const currentValue = currentInput.value.trim().toLowerCase();
        if (!currentValue) return false;

        let count = 0;

        document.querySelectorAll('.bulk-item-row input[name*="[name]"]').forEach(input => {
            if (input.value.trim().toLowerCase() === currentValue) {
                count++;
            }
        });

        return count > 1;
    }

    document.addEventListener('blur', function(e) {
        if (!e.target.matches('.bulk-item-row input[name*="[name]"]')) return;

        const input = e.target;
        const value = input.value.trim();

        if (!value) return;

        if (isDuplicateBulkItemName(input)) {
            showFlash('Bulk item name must be unique');
            input.focus();
        }
    }, true); // 👈 CAPTURE MODE REQUIRED
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        if (!window.EXISTING_BULK_ITEMS || !EXISTING_BULK_ITEMS.length) return;

        const container = document.getElementById('added-bulkitems-container');
        const emptyMsg = document.getElementById('no-bulkitem-message');

        container.innerHTML = '';
        if (emptyMsg) emptyMsg.style.display = 'none';

        bulkItemIndex = 0;

        EXISTING_BULK_ITEMS.forEach((item, index) => {

            const id = createUniqueId();

            const card = document.createElement('div');
            card.className = 'card mb-2 bulkitem-card';
            card.id = `bulkitem-${id}`;

            card.innerHTML = `
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">

                    <input type="hidden" name="bulk_items[${id}][id]" value="${item.id ?? ''}">

                    <div class="col-md-3">
                        <input type="text" readonly
                            class="form-control form-control-sm"
                            name="bulk_items[${id}][name]"
                            value="${item.name ?? ''}">
                    </div>

                    <div class="col-md-2">
                        <input type="number"
                            class="form-control form-control-sm"
                            name="bulk_items[${id}][quantity]"
                            value="${item.quantity ?? ''}">
                    </div>

                    <div class="col-md-2">
                        <input type="number"
                            class="form-control form-control-sm"
                            name="bulk_items[${id}][moq]"
                            value="${item.moq ?? ''}">
                    </div>

                    <div class="col-md-2">
                        <input type="number"
                            class="form-control form-control-sm"
                            name="bulk_items[${id}][mrp]"
                            value="${item.mrp ?? ''}">
                    </div>

                    <div class="col-md-2">
                        <input type="number"
                            class="form-control form-control-sm"
                            name="bulk_items[${id}][selling_price]"
                            value="${item.selling_price ?? ''}">
                    </div>

                    <div class="col-md-1 text-end">
                        <button type="button"
                            class="btn btn-sm btn-danger delete-item"
                            data-type="bulkitem"
                            data-id="${id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                </div>
            </div>
        `;

            container.appendChild(card);
            bulkItemIndex++;
        });

        syncAllExclusiveRules();
    });
</script>

</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        if (!window.EXISTING_VARIANTS || !EXISTING_VARIANTS.length) return;

        const container = document.getElementById('added-variants-container');
        if (!container) return;

        container.innerHTML = '';

        const noVariantMsg = document.getElementById('no-variants-message');
        if (noVariantMsg) noVariantMsg.style.display = 'none';

        EXISTING_VARIANTS.forEach((variant) => {

            const colorId = variant.color_id ?? '';
            const sizeId = variant.size_id ?? '';

            const variantKey = `${colorId}-${sizeId}`;
            addedVariants.push(variantKey);

            const id = createUniqueId();

            const card = document.createElement('div');
            card.className = 'card mb-2 variant-card';
            card.id = `variant-${id}`;

            card.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-10">
                        <div class="row g-2">

                            <div class="col-md-3">
                                <label class="small fw-bold">Color</label>
                                <div>${variant.color?.name ?? 'N/A'}</div>
                                <input type="hidden" name="variants[${id}][id]" value="${variant.id}">
                                <input type="hidden" name="variants[${id}][color_id]" value="${colorId}">
                            </div>

                            <div class="col-md-3">
                                <label class="small fw-bold">Size</label>
                                <div>${variant.size?.name ?? 'N/A'}</div>
                                <input type="hidden" name="variants[${id}][size_id]" value="${sizeId}">
                            </div>

                            <div class="col-md-3">
                                <label class="small fw-bold">Price</label>
                                <input type="number"
                                    class="form-control form-control-sm"
                                    name="variants[${id}][price]"
                                    value="${variant.price}"
                                    min="1"
                                    required>
                            </div>

                            <div class="col-md-3">
                                <label class="small fw-bold">Quantity</label>
                                <input type="number"
                                    class="form-control form-control-sm"
                                    name="variants[${id}][quantity]"
                                    value="${variant.quantity}"
                                    min="0"
                                    required>
                            </div>

                        </div>
                    </div>

                    <div class="col-md-2 text-end">
                        <button type="button"
                            class="btn btn-sm btn-danger delete-item"
                            data-type="variant"
                            data-id="${id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

            container.appendChild(card);
        });
        syncAllExclusiveRules();
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        if (!window.EXISTING_BULK_PRICES || !EXISTING_BULK_PRICES.length) return;

        // console.log('window.EXISTING_BULK_PRICES');
        // console.log(window.EXISTING_BULK_PRICES);
        const container = document.getElementById('added-bulkprice-container');
        if (!container) return;

        container.innerHTML = '';
        const noVariantMsg = document.getElementById('no-bulkprice-message');
        if (noVariantMsg) noVariantMsg.style.display = 'none';

        EXISTING_BULK_PRICES.forEach(price => {

            const rangeKey = `${price.min_qty}-${price.max_qty}`;
            addedBulkRanges.push(rangeKey);

            const bulkId = createUniqueId();
            const card = document.createElement('div');

            card.className = 'card mb-2 bulk-card';
            card.id = `bulk-${bulkId}`;

            card.innerHTML = `
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-10">
                    <div class="row g-2">

                        <input type="hidden" name="bulk[${bulkId}][id]" value="${price.id}">

                        <div class="col-md-4">
                            <strong>Min Qty:</strong> ${price.min_qty}
                            <input type="hidden" name="bulk[${bulkId}][min_qty]" value="${price.min_qty}">
                        </div>

                        <div class="col-md-4">
                            <strong>Max Qty:</strong> ${price.max_qty}
                            <input type="hidden" name="bulk[${bulkId}][max_qty]" value="${price.max_qty}">
                        </div>

                <div class="col-md-4">
                    <strong>Price</strong> ${price.price}
                    <input type="hidden" name="bulk[${bulkId}][price]" value="${price.price}">
                </div>

                    </div>
                </div>

                <div class="col-md-2 text-end">
                    <button type="button"
                        class="btn btn-sm btn-danger delete-item"
                        data-type="bulk"
                        data-id="${bulkId}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        `;

            container.appendChild(card);
        });
        syncAllExclusiveRules();
        updateBulkDeleteButtons();
    });
</script>
@endpush
