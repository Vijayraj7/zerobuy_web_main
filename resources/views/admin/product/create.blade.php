@extends('layouts.app')
@section('content')
@section('header-title', __('Add New Product'))

<div class="page-title">
    <div class="d-flex gap-2 align-items-center">
        {{ __('Add New Product') }}
    </div>
</div>

<form action="{{ route('admin.product.store') }}" method="POST" enctype="multipart/form-data">@csrf
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <button type="submit" class="btn btn-success">Save Product</button>
            </div>
            <ul class="nav nav-tabs mt-3" id="productTabs">
                <li class="nav-item"><a class="nav-link active" data-step="1" href="#">Product Details</a></li>
                <li class="nav-item"><a class="nav-link" data-step="2" href="#">Price</a></li>
                <li class="nav-item"><a class="nav-link" data-step="3" href="#">Product Variants</a></li>
                <li class="nav-item"><a class="nav-link" data-step="4" href="#">Images</a></li>
            </ul>

            <!-- STEP 1 -->
            <div class="step-content step-1 mt-3">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="mb-3">
                           <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="condition_status" value="New" checked>
                                <label class="form-check-label">New</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="condition_status" value="Refurbished">
                                <label class="form-check-label">Refurbished</label>
                            </div>
                        </div> 
                        <div class="mb-3">
                            <x-input label="Product Name" name="name" id="product_name" type="text" placeholder="Enter Product Name" required="true" />
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <x-select label="Main Category" name="main_category" required="true">
                                    <option value="" selected disabled>{{ __('Select Category') }}</option>
                                    @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </x-select>
                                @error('main_category')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <x-select label="Sub Category" name="sub_categories[]" multiple>
                                    <option value="" selected disabled>{{ __('Select Sub Category') }}</option>
                                </x-select>
                                @error('sub_categories')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <x-select label="Child Category" name="child_categories[]" multiple>
                                    <option value="" disabled>{{ __('Select Child Category') }}</option>
                                    <option value="1">child cat1</option>
                                    <option value="2">child cat2</option>
                                    <option value="3">child cat3</option>
                                </x-select>
                                @error('child_categories')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="row mt-3 g-2">
                            <div class="col-md-6"> 
                                <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="quantity" placeholder="Enter Quantity" min="0" value="0">
                                @error('quantity')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">MOQ <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="min_order_quantity" placeholder="Enter Minimum Order Quantity" min="1" value="1"> 
                                @error('min_order_quantity')
                                    <p class="text text-danger m-0">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <x-select label="Return Period" name="return_period" required="true">
                                <option value="7">7 Days</option>
                                <option value="10">10 Days</option>
                                <option value="15">15 Days</option>
                            </x-select>
                            @error('return_period')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="mb-3">
                            <label for="description" class="form-label">Product Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" id="description" placeholder="Enter Product Description" rows="8" required></textarea>
                            @error('description')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div> 
                        <div class="row g-2">
                            <div class="col-3">
                                <label class="form-label">Video Type</label>
                                <select class="form-select" name="video_type">
                                    <option value="">None</option>
                                    <option value="youtube">YouTube Link</option>
                                    <option value="external">External Link</option>
                                </select>
                            </div>
                            <div class="col-9">
                                <label class="form-label">Video Link</label>
                                <input type="text" class="form-control" name="video_link" 
                                    placeholder="Enter YouTube or external video URL">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="form-label">Item Details (Add multiple)</label>
                            <div id="item-details-list">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" name="item_details[]" class="form-control" placeholder="Item detail">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" onclick="this.closest('.d-flex').remove()">-</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="add-item-detail">+ Add More</button>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="2"> Next &raquo;</button>
                </div>
            </div>

            <!-- STEP 2 - PRICE -->
            <div class="step-content step-2 mt-3" style="display:none;">
                <div class="row">
                    <div class="col-md-4">
                        <x-input label="MRP" name="mrp" type="number" step="0.01" placeholder="₹ Enter MRP" required="true" />
                        @error('mrp')
                            <p class="text text-danger m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <x-input label="Selling Price" name="selling_price" placeholder="₹ Enter Selling Price" type="number" step="0.01" required="true" />
                        @error('selling_price')
                            <p class="text text-danger m-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <x-select label="TAX%" name="tax_percentage" required="true">
                            <option value="0">0%</option>
                            <option value="5">5%</option>
                            <option value="12">12%</option>
                            <option value="18">18%</option>
                        </x-select>
                        @error('tax_percentage')
                            <p class="text text-danger m-0">{{ $message }}</p>
                        @enderror
                    </div>
                </div> 

                <div class="mt-4">
                    <label class="form-label">Bulk Price (optional)</label>
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
                                <td><input id="bulk-min" class="form-control" placeholder="Min Qty" type="number" min="1"></td>
                                <td><input id="bulk-max" class="form-control" placeholder="Max Qty" type="number" min="1"></td>
                                <td><input id="bulk-price" class="form-control" placeholder="Price" type="number" step="0.01"></td>
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
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="1">&laquo; Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="3">Next &raquo;</button>
                </div>
            </div>

            <!-- STEP 3 - PRODUCT VARIANTS --> 
            <div class="step-content step-3 mt-3" style="display:none;"> 
                <div class="mt-4">
                    <label class="form-label">Product Variant Details</label>
                    <div class="alert alert-info small">Add size-only, color-only, or size+color variants. Each variant row contains price & quantity.</div>
                    <!-- Variant Input Form -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Color (optional)</th>
                                        <th>Size (optional)</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
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
                                            <select id="variant-color" name="color_id" class="form-select colorSelect" style="width: 100%">
                                                <option value="">-- Select Color --</option>
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color->id }}" data-color="{{ $color->color_code }}">
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
                                            <input id="variant-price" class="form-control" placeholder="Price" type="number" step="0.01">
                                        </td>
                                        <td>
                                            <input id="variant-qty" class="form-control" placeholder="Qty" type="number">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary" id="add-variant">Add Variant</button>
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
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="2">&laquo; Previous</button>
                    <button type="button" class="btn btn-primary next-btn float-end" data-next="4">Next &raquo;</button>
                </div>
            </div>

            <!-- STEP 4 - IMAGES -->
            <div class="step-content step-4 mt-3" style="display:none;">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-body">
                            <h5>Thumbnail <small class="text-muted">(Ratio 1:1 500x500) *</small></h5>
                            <label for="thumbnail" style="cursor:pointer;display:block;">
                                <img src="https://placehold.co/500x500/f1f5f9/png" id="preview" alt="thumbnail" style="width:100%;max-width:300px">
                            </label>
                            <input id="thumbnail" accept="image/*" type="file" name="thumbnail" class="d-none" onchange="previewFile(event, 'preview')">
                            @error('thumbnail')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5>Additional Thumbnails <span class="text-primary">(Ratio 1:1, 500x500px)<small class="text-muted">-multiple</small></span></h5>
                                <input type="file" id="additionalInput" name="additional_images[]" multiple accept="image/*" class="d-none" onchange="handleAddImages(event)">
                                <button type="button" class="btn btn-primary mt-2" onclick="document.getElementById ('additionalInput').click()"><i class="fa fa-upload"></i> Choose Images</button>
                                <div id="additionalContainer" class="d-flex flex-wrap gap-3 mt-3"></div>
                            </div>
                        </div>
                    </div> 
                    <!-- <div class="col-12 mt-4">
                        <p class="small text-muted">If you want variant-specific images, you can upload them after saving the product (or in edit flow).</p>
                    </div> -->
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary prev-btn" data-prev="3">&laquo; Previous</button> 
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    
    // Flash Message
    function showFlash(message) {
        const flash = document.createElement('div');
        flash.className = 'alert alert-danger';
        flash.innerHTML = `${message} <button class="btn-close btn-sm float-end" onclick="this.parentElement.remove()"></button>`;
        flash.style.cssText = 'position:fixed; top:10px; right:10px; z-index:9999;';
        
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 5000);
    }

    // Summernote
    $(document).ready(function() {
        $('#description').summernote();
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

    // Item details add-more
    document.getElementById('add-item-detail').addEventListener('click', function() {
        let container = document.getElementById('item-details-list');
        let div = document.createElement('div');
        div.classList.add('d-flex', 'gap-2', 'mb-2');
        div.innerHTML =
            `<input type="text" name="item_details[]" class="form-control" placeholder="Item detail">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.d-flex').remove()">-</button>`;
        container.appendChild(div);
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
        $('.colorSelect').select2({
            width: '100%'
        }); 
        $('.colorSelect').each(function() {
            var $select = $(this);
            $select.select2('destroy').select2({
                width: '100%',
                templateResult: function(state) {
                    if (!state.id) return state.text;
                    var color = $(state.element).data('color');
                    if (color) {
                        return $('<span><span style="width:15px;height:15px;background:'+color+';display:inline-block;margin-right:8px;border-radius:2px"></span> ' + state.text + '</span>');
                    }
                    return state.text;
                },
                templateSelection: function(state) {
                    if (!state.id) return state.text;
                    var color = $(state.element).data('color');
                    if (color) {
                        return $('<span><span style="width:15px;height:15px;background:'+color+';display:inline-block;margin-right:8px;border-radius:2px"></span> ' + state.text + '</span>');
                    }
                    return state.text;
                }
            });
        });
    });

</script>
<script>
// Simple function to show color squares
function formatColorOption(state) {
    if (!state.id) {
        return state.text;
    }
    
    var colorCode = $(state.element).data('color');
    
    if (colorCode) {
        return $(
            '<span>' +
                '<span style="display:inline-block; width:15px; height:15px; background-color:' + colorCode + 
                '; border-radius:3px; margin-right:8px; border:1px solid #ddd"></span>' +
                state.text +
            '</span>'
        );
    }
    
    return state.text;
}

$(document).ready(function() {
    $('.colorSelect').select2({
        templateResult: formatColorOption,
        templateSelection: formatColorOption
    });
});
</script>

<script>
    $(document).ready(function() {
        $('select[name="main_category"]').on('change', function() {
            var categoryId = $(this).val();

            if (categoryId) {
                $.ajax({
                    url: '/api/sub-categories?category_id=' + categoryId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        var subCategorySelected = $('select[name="sub_categories[]"]');
                        subCategorySelected.empty();

                        $.each(data.data.sub_categories, function(key, value) {
                            subCategorySelected.append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                        subCategorySelected.trigger('change');
                    },
                    error: function() {
                        console.log('Error retrieving subcategories. Please try again.');
                    }
                });
            } else {
                $('select[name="subCategory[]"]').empty();
            }
        });
    });
</script>

<!-- Add Variants -->
<!-- <script>
    // Variant builder 
    let addedVariants = [];

    document.getElementById('add-variant').addEventListener('click', function() {
        const colorSelect = document.getElementById('variant-color');
        const sizeSelect = document.getElementById('variant-size');
        const priceInput = document.getElementById('variant-price');
        const qtyInput = document.getElementById('variant-qty');
        
        const colorId = colorSelect.value;
        const colorName = colorSelect.options[colorSelect.selectedIndex]?.text || '';
        const sizeId = sizeSelect.value;
        const sizeName = sizeSelect.options[sizeSelect.selectedIndex]?.text || '';
        const price = priceInput.value || 0;
        const qty = qtyInput.value || 0;
        
        // Check if variant already exists
        const variantKey = `${colorId}-${sizeId}`;
        if (addedVariants.includes(variantKey)) {
            showFlash('This color and size combination already exists!');
            return;
        }

        // Validate at least one variant attribute is selected
        if (!sizeId && !colorId) {
            showFlash('Please choose either size or color (or both)');
            return;
        }
        
        // Validate price and quantity
        if (!price || price <= 0) {
            showFlash('Please enter a valid price');
            return;
        }
        
        if (!qty || qty < 0) {
            showFlash('Please enter a valid quantity');
            return;
        }
        
        addedVariants.push(variantKey);
        
        // Create unique ID for the variant
        const variantId = Date.now() + Math.random().toString(36).substr(2, 9);
        
        // Hide "no variants" message if it exists
        const noVariantsMsg = document.getElementById('no-variants-message');
        if (noVariantsMsg) {
            noVariantsMsg.style.display = 'none';
        }
        
        // Create variant display card
        const variantContainer = document.getElementById('added-variants-container');
        const variantCard = document.createElement('div');
        variantCard.className = 'card mb-2 variant-card';
        variantCard.id = 'variant-' + variantId;
        variantCard.setAttribute('data-variant-id', variantId);
        
        variantCard.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Color:</strong> ${colorName || 'N/A'}
                                <input type="hidden" name="variants[${variantId}][color_id]" value="${colorId}">
                            </div>
                            <div class="col-md-3">
                                <strong>Size:</strong> ${sizeName || 'N/A'}
                                <input type="hidden" name="variants[${variantId}][size_id]" value="${sizeId}">
                            </div>
                            <div class="col-md-3">
                                <strong>Price:</strong> $${parseFloat(price).toFixed(2)}
                                <input type="hidden" name="variants[${variantId}][price]" value="${price}">
                            </div>
                            <div class="col-md-3">
                                <strong>Quantity:</strong> ${qty}
                                <input type="hidden" name="variants[${variantId}][quantity]" value="${qty}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-danger delete-variant" data-variant-id="${variantId}">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add to display container
        variantContainer.appendChild(variantCard);
        
        // Clear input fields
        colorSelect.value = '';
        sizeSelect.value = '';
        priceInput.value = '';
        qtyInput.value = '';
        
        // Focus back to color select for quick entry
        colorSelect.focus();
    });

    // Delete variant
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-variant')) {
            const button = e.target.closest('.delete-variant');
            const variantCard = button.closest('.variant-card');
            
            if (variantCard) {
                // Get color and size IDs
                const colorId = variantCard.querySelector('input[name*="color_id"]').value;
                const sizeId = variantCard.querySelector('input[name*="size_id"]').value;
                const variantKey = `${colorId}-${sizeId}`;
                
                // Remove from tracking array
                addedVariants = addedVariants.filter(key => key !== variantKey);
                
                // Remove from page
                variantCard.remove();
            }
        }
    });

    // Optional: Add CSS for better display
    const style = document.createElement('style');
    style.textContent = `
        .variant-card {
            border-left: 4px solid #007bff;
        }
        .variant-card:hover {
            background-color: #f8f9fa;
        }
        #no-variants-message {
            font-style: italic;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    `;
    document.head.appendChild(style);

    // Bulk price
    // document.getElementById('add-bulk').addEventListener('click', function() {
    //     let min = document.getElementById('bulk-min').value;
    //     let max = document.getElementById('bulk-max').value;
    //     let price = document.getElementById('bulk-price').value;
    //     if (!min || !max || !price) {
    //         showFlash('Fill Min Quantity, Max Quantity and Extra Price');
    //         return;
    //     }
    //     let tbody = document.querySelector('#bulk-table tbody');
    //     let row = document.createElement('tr');
    //     row.innerHTML = `
    //             <td><input type="hidden" name="bulk[][min_qty]" value="${min}">${min}</td>
    //             <td><input type="hidden" name="bulk[][max_qty]" value="${max}">${max}</td>
    //             <td><input type="hidden" name="bulk[][price]" value="${price}">${price}</td>
    //             <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">Remove</button></td>
    //         `;
    //     tbody.appendChild(row);
    //     document.getElementById('bulk-min').value = '';
    //     document.getElementById('bulk-max').value = '';
    //     document.getElementById('bulk-price').value = '';
    // });
</script> -->

<!-- Add Bulk prices  -->
<!-- <script>
    let addedBulkRanges = [];

    document.getElementById('add-bulk').addEventListener('click', function() {
        const minInput = document.getElementById('bulk-min');
        const maxInput = document.getElementById('bulk-max');
        const priceInput = document.getElementById('bulk-price');
        
        const min = minInput.value;
        const max = maxInput.value;
        const price = priceInput.value;
        
        if (!min || !max || !price) {
            showFlash('Fill Min Quantity, Max Quantity and Price');
            return;
        }
        
        // Validate min < max
        if (parseInt(min) >= parseInt(max)) {
            showFlash('Min quantity must be less than Max quantity');
            return;
        }
        
        // Check for overlapping ranges
        const rangeKey = `${min}-${max}`;
        if (addedBulkRanges.includes(rangeKey)) {
            showFlash('This quantity range already exists!');
            return;
        }
        
        // Check for overlapping with existing ranges
        for (let range of addedBulkRanges) {
            const [existingMin, existingMax] = range.split('-').map(Number);
            if (
                (min >= existingMin && min <= existingMax) ||
                (max >= existingMin && max <= existingMax) ||
                (min <= existingMin && max >= existingMax)
            ) {
                showFlash('This quantity range overlaps with existing range!');
                return;
            }
        }
        
        addedBulkRanges.push(rangeKey);
        
        // Hide "no bulk price" message
        const noBulkMsg = document.getElementById('no-bulkprice-message');
        if (noBulkMsg) {
            noBulkMsg.style.display = 'none';
        }
        
        // Create bulk price display card
        const bulkId = Date.now() + Math.random().toString(36).substr(2, 9);
        const bulkContainer = document.getElementById('added-bulkprice-container');
        const bulkCard = document.createElement('div');
        bulkCard.className = 'card mb-2 bulk-card';
        bulkCard.id = 'bulk-' + bulkId;
        bulkCard.setAttribute('data-bulk-id', bulkId);
        
        bulkCard.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Min Qty:</strong> ${min}
                                <input type="hidden" name="bulk[${bulkId}][min_qty]" value="${min}">
                            </div>
                            <div class="col-md-4">
                                <strong>Max Qty:</strong> ${max}
                                <input type="hidden" name="bulk[${bulkId}][max_qty]" value="${max}">
                            </div>
                            <div class="col-md-4">
                                <strong>Price:</strong> ₹${parseFloat(price).toFixed(2)}
                                <input type="hidden" name="bulk[${bulkId}][price]" value="${price}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-danger delete-bulk" data-bulk-id="${bulkId}">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add to display container
        bulkContainer.appendChild(bulkCard);
        
        // Clear input fields
        minInput.value = '';
        maxInput.value = '';
        priceInput.value = '';
        
        // Focus back to min input for quick entry
        minInput.focus();
    });

    // Delete bulk price
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-bulk')) {
            const button = e.target.closest('.delete-bulk');
            const bulkCard = button.closest('.bulk-card');
            
            if (bulkCard) {
                // Get min and max values
                const min = bulkCard.querySelector('input[name*="min_qty"]').value;
                const max = bulkCard.querySelector('input[name*="max_qty"]').value;
                const rangeKey = `${min}-${max}`;
                
                // Remove from tracking array
                addedBulkRanges = addedBulkRanges.filter(key => key !== rangeKey);
                
                // Remove from page
                bulkCard.remove();
                
                // Show "no bulk price" message if all are deleted
                const bulkCards = document.querySelectorAll('.bulk-card');
                const noBulkMsg = document.getElementById('no-bulkprice-message');
                
                if (bulkCards.length === 0 && noBulkMsg) {
                    noBulkMsg.style.display = 'block';
                }
            }
        }
    });

    // Update CSS to include bulk card styling
    style.textContent += `
        .bulk-card {
            border-left: 4px solid #28a745;
        }
        .bulk-card:hover {
            background-color: #f8f9fa;
        }
        #no-bulkprice-message {
            font-style: italic;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    `;
</script> -->

<script>  
    function createUniqueId() {
        return Date.now() + Math.random().toString(36).substr(2, 9);
    }
    
    // Variants Management
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
        
        // Validation
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
        if (addedVariants.includes(variantKey)) {
            showFlash('This color and size combination already exists!');
            return;
        }
        
        // Add to tracking array
        addedVariants.push(variantKey);
        // Hide "no variants" message
        document.getElementById('no-variants-message').style.display = 'none';
        // Create display card
        const variantId = createUniqueId();
        const variantCard = document.createElement('div');
        variantCard.className = 'card mb-2 variant-card';
        variantCard.id = `variant-${variantId}`;
        
        variantCard.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Color:</strong> ${colorName || 'N/A'}
                                <input type="hidden" name="variants[${variantId}][color_id]" value="${colorId}">
                            </div>
                            <div class="col-md-3">
                                <strong>Size:</strong> ${sizeName || 'N/A'}
                                <input type="hidden" name="variants[${variantId}][size_id]" value="${sizeId}">
                            </div>
                            <div class="col-md-3">
                                <strong>Price:</strong> ₹${price.toFixed(2)}
                                <input type="hidden" name="variants[${variantId}][price]" value="${price}">
                            </div>
                            <div class="col-md-3">
                                <strong>Quantity:</strong> ${qty}
                                <input type="hidden" name="variants[${variantId}][quantity]" value="${qty}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-danger delete-item" data-type="variant" data-id="${variantId}">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('added-variants-container').appendChild(variantCard);
        
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
        if (!min || !max || !price) {
            showFlash('Fill Min Quantity, Max Quantity and Price');
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
            existingRanges.push({ min: existingMin, max: existingMax });
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
                        showFlash(`There's a gap in quantity ranges. After ${prevRange.max}, next range should start at ${prevRange.max + 1}`);
                        return;
                    }
                }
                
                prevRange = range;
            }
            
            // Now check where the new range fits
            if (min < existingRanges[0].min) {
                // New range is before all existing ranges
                if (max + 1 !== existingRanges[0].min) {
                    showFlash(`To add before existing range, max should be ${existingRanges[0].min - 1} for continuity`);
                    return;
                }
            } else if (min > existingRanges[existingRanges.length - 1].max) {
                // New range is after all existing ranges
                if (min !== existingRanges[existingRanges.length - 1].max + 1) {
                    showFlash(`To add after existing range, min should be ${existingRanges[existingRanges.length - 1].max + 1} for continuity`);
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
                            showFlash(`To insert between ranges, min should be ${currentRange.max + 1} for continuity`);
                            return;
                        }
                        if (max + 1 !== nextRange.min) {
                            showFlash(`To insert between ranges, max should be ${nextRange.min - 1} for continuity`);
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
        
        // If no existing ranges, just add the first one
        addedBulkRanges.push(rangeKey);
        
        // Hide "no bulk price" message
        document.getElementById('no-bulkprice-message').style.display = 'none';
        
        // Create display card
        const bulkId = createUniqueId();
        const bulkCard = document.createElement('div');
        bulkCard.className = 'card mb-2 bulk-card';
        bulkCard.id = `bulk-${bulkId}`;
        
        bulkCard.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Min Qty:</strong> ${min}
                                <input type="hidden" name="bulk[${bulkId}][min_qty]" value="${min}">
                            </div>
                            <div class="col-md-4">
                                <strong>Max Qty:</strong> ${max}
                                <input type="hidden" name="bulk[${bulkId}][max_qty]" value="${max}">
                            </div>
                            <div class="col-md-4">
                                <strong>Price:</strong> ₹${price.toFixed(2)}
                                <input type="hidden" name="bulk[${bulkId}][price]" value="${price}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-danger delete-item" data-type="bulk" data-id="${bulkId}">
                            <i class="fas fa-trash"></i> Remove
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
                    document.getElementById('no-variants-message').style.display = 'block';
                }
            } else if (type === 'bulk') {
                // Remove bulk range from tracking
                const min = card.querySelector('input[name*="min_qty"]').value;
                const max = card.querySelector('input[name*="max_qty"]').value;
                const rangeKey = `${min}-${max}`;
                addedBulkRanges = addedBulkRanges.filter(key => key !== rangeKey);
                
                // Show message if no bulk prices left
                if (document.querySelectorAll('.bulk-card').length === 1) {
                    document.getElementById('no-bulkprice-message').style.display = 'block';
                }
                
                // Update min input suggestion after deletion
                updateMinInputSuggestion();
            }
            
            card.remove();
        }
    });

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

@endpush