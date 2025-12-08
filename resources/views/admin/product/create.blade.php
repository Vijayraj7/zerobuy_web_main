@extends('layouts.app')
@section('content')
@section('header-title', __('Add New Product'))
  {{-- Bootstrap 4 CSS (required for summernote-bs4) --}}
  <!-- <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"> -->

  {{-- Summernote CSS --}}
  <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet">
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            {{ __('Add New Product') }}
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-success mb-3 float-end">Save Product</button>

            <ul class="nav nav-tabs mt-3" id="productTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-step="1" href="#">Product Details</a>
                </li> 
                <li class="nav-item">
                    <a class="nav-link" data-step="2" href="#">Price</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-step="3" href="#">Images</a>
                </li>
            </ul>

            <!-- STEP 1 CONTENT -->
            <div class="step-content step-1 mt-3">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="row">
                            <div class="col-md-12 mt-3"> 

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="condition_status" id="condition_new" value="New" required checked>
                                    <label class="form-check-label" for="condition_new">New</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="condition_status" id="condition_refurbished" value="Refurbished">
                                    <label class="form-check-label" for="condition_refurbished">Refurbished</label>
                                </div>
                            </div>
                        </div>

                        <div class="row"> 
                            <div class="col-md-12 mt-3"> 
                                <x-input label="Product Name" name="name" id="product_name" type="text" placeholder="Enter Product Name" required="true" /> 
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mt-3"> 
                                <x-select label="Main Category" name="category" type="text" placeholder="Select Category" /> 
                            </div>
                            <div class="col-md-4 mt-3"> 
                                <x-select label="Sub Category" name="sub_category" type="text" placeholder="Select Sub Category" />
                            </div>
                            <div class="col-md-4 mt-3"> 
                                <x-select label="Child Category" name="child_category" type="text" placeholder="Select Child Category" /> 
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mt-3">
                                <x-input type="number" name="quantity" label="Quantity" placeholder="Enter Quantity" required="true" />
                            </div>

                            <div class="col-md-6 mt-3">
                                <x-input type="number" name="min_order_quantity" label="MOQ" placeholder="Enter Minimum Order Quantity" value="1" min="1" required="true" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mt-3">
                                <x-select label="Return Period" name="return_period" type="text" placeholder="Select Return Period" /> 
                            </div> 
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mt-3">
                                <x-input label="Size" name="size" type="text" placeholder="Select Size" /> 
                            </div> 
                            <div class="col-md-6 mt-3">
                                <x-input label="Color" name="Color" type="text" placeholder="Select Color" /> 
                            </div>
                            <div class="col-md-2">
                                <label class="form-label invisible">Add Variants</label>
                                <button type="button" class="btn btn-primary btn-sm"> Add Variants </button>
                            </div> 
                        </div> 

                    </div>

                    <div class="col-lg-5">
                        <div class="row"> 
                            <div class="col-md-12 mt-3"> 
                                <label for="description" class="form-label">Product Description *</label>
                                <textarea class="form-control" name="description" id="description" placeholder="Enter Product Description" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mt-3">
                                <x-input label="Video Link" name="video_link" type="text" placeholder="Select Video Link" /> 
                            </div> 
                        </div> 
                        <div class="row">
                            <div class="col-md-5 mt-3">
                                <x-input label="Item Details" name="item_details" type="text" placeholder="Select Item Details" /> 
                            </div>
                            <div class="col-md-5 mt-3">
                                <x-input label="Item Details" name="item_details" type="text" placeholder="Select Item Details" /> 
                            </div>
                            <div class="col-md-2 mt-3">
                                <label class="form-label invisible">Add More</label>
                                <button type="button" class="btn btn-primary btn-sm" id="item_details"> Add More </button>
                            </div>

                        </div> 
                    </div> 
                </div>
                <button type="button" class="btn btn-primary next-btn float-end mt-3" data-next="2">
                    Next &raquo;
                </button>
            </div>

            <!-- STEP 2 CONTENT -->
            <div class="step-content step-2 mt-3" style="display:none;">

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <x-input label="MRP" name="mrp" type="number" step="0.01" placeholder="₹ Enter MRP" required="true"/>
                    </div>
                    <div class="col-md-6 mt-3">
                        <x-input label="Selling Price" name="selling Price" placeholder="₹ Enter Selling Price" type="number" step="0.01"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mt-3">
                        <x-select label="TAX%" name="gst" type="text" placeholder="Select GST" required="true">
                            <option value="5">GST 5%</option>
                            <option value="12">GST 12%</option>
                            <option value="18">GST 18%</option>
                            <option value="28">GST 28%</option>
                            <option value="40">GST 40%</option>
                        </x-select>
                    </div> 
                </div>
                <button type="button" class="btn btn-primary btn-sm mt-3"> Bulk Price </button> <br>

                <button type="button" class="btn btn-secondary prev-btn mt-3" data-prev="1">&laquo; Previous</button>
                <button type="button" class="btn btn-primary next-btn mt-3 float-end" data-next="3">Next &raquo;</button>
            </div>

            <!-- STEP 3 CONTENT -->
            <div class="step-content step-3 mt-3" style="display:none;">

                <!-- <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Main Image</label>
                        <input type="file" name="main_image" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gallery Images</label>
                        <input type="file" name="gallery_images[]" class="form-control" multiple>
                    </div>
                </div> -->

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card card-body h-100">
                            <div class="mb-2">
                                <h5>
                                    {{ __('Thumbnail') }}
                                    <span class="text-primary">{{ __('(Ratio 1:1 (500 x 500 px))') }}</span>
                                    <span class="text-danger">*</span>
                                </h5>
                                @error('thumbnail')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <label for="thumbnail" class="additionThumbnail">
                                <img src="https://placehold.co/500x500/f1f5f9/png" id="preview" alt=""
                                    width="100%">
                            </label>
                            <input id="thumbnail" accept="image/*" type="file" name="thumbnail" class="d-none"
                                onchange="previewFile(event, 'preview')">
                            <small class="text-muted mt-1">{{ __('Supported formats: jpg, jpeg, png') }}</small>
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-2">
                                    <h5>
                                        {{ __('Additional Thumbnail') }}
                                        <span class="text-primary">{{ __('(Ratio 1:1 (500 x 500 px))') }}</span>
                                    </h5>
                                    @error('additionThumbnail')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="d-flex flex-wrap gap-3" id="additionalElements">

                                    <div id="addition">
                                        <label for="additionThumbnail1" class="additionThumbnail">
                                            <img src="https://placehold.co/500x500/f1f5f9/png" id="preview2"
                                                alt="" width="100%" height="100%">
                                            <button onclick="removeThumbnail('addition')" id="removeThumbnail1"
                                                type="button" class="delete btn btn-sm btn-outline-danger circleIcon"
                                                style="display: none">
                                                <img src="{{ asset('assets/icons-admin/trash.svg') }}" loading="lazy"
                                                    alt="trash" />
                                            </button>
                                        </label>
                                        <input id="additionThumbnail1" accept="image/*" type="file"
                                            name="additionThumbnail[]" class="d-none"
                                            onchange="previewAdditionalFile(event, 'preview2', 'removeThumbnail1')">
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-secondary prev-btn mt-3" data-prev="2">&laquo; Previous</button>
                <button type="submit" class="btn btn-success mt-3 float-end">Save Product</button>
            </div>

        </div>
    </div>

@endsection


@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<script>
$(document).ready(function(){
    // Next Button
    $('.next-btn').click(function () {
        let nextStep = $(this).data('next');
        $('.step-content').hide();
        $('.step-' + nextStep).show();

        $('#productTabs .nav-link').removeClass('active');
        $('#productTabs .nav-link[data-step="'+nextStep+'"]').addClass('active');
    });
    // Previous Button
    $('.prev-btn').click(function () {
        let prevStep = $(this).data('prev');
        $('.step-content').hide();
        $('.step-' + prevStep).show();

        $('#productTabs .nav-link').removeClass('active');
        $('#productTabs .nav-link[data-step="'+prevStep+'"]').addClass('active');
    });
    // ⭐ Tab Click Navigation
    $('#productTabs .nav-link').click(function (e) {
        e.preventDefault();

        let step = $(this).data('step');

        $('.step-content').hide();
        $('.step-' + step).show();

        $('#productTabs .nav-link').removeClass('active');
        $(this).addClass('active');
    });

});
</script>
<script>
    var thumbnailCount = 1;

    const previewAdditionalFile = (event, id, removeId) => {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById(id);
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);

        // increment count
        thumbnailCount++;

        document.getElementById(removeId).style.display = 'block';

        // Create a new box dynamically
        const newThumbnailId = `additionThumbnail${thumbnailCount + 1}`;
        const newPreviewId = `preview${thumbnailCount + 1}`;
        const mainId = 'addition' + thumbnailCount + 1;

        // Add the new box
        const newThumbnailBox = document.createElement('div');
        newThumbnailBox.id = mainId;

        newThumbnailBox.innerHTML = `
        <label for="${newThumbnailId}" class="additionThumbnail">
            <img src="{{ asset('default/upload.png') }}" id="${newPreviewId}" alt="" width="100%" height="100%">
            <button onclick="removeThumbnail('${mainId}')" type="button" id="removeThumbnail${thumbnailCount + 1}" class="delete btn btn-sm btn-outline-danger circleIcon" style="display: none"><img src="{{ asset('assets/icons-admin/trash.svg') }}" loading="lazy" alt="trash" /></button>
            <input id="${newThumbnailId}" accept="image/*" type="file" name="additionThumbnail[]" class="d-none" onchange="previewAdditionalFile(event, '${newPreviewId}', 'removeThumbnail${thumbnailCount +1 }')">
        </label>
    `;

        document.getElementById('additionalElements').insertBefore(newThumbnailBox, document.getElementById(
            'additionalElements').firstChild);

        // get current file
        var inputElement = event.target;
        var newOnchangeFunction = `previewFile(event, '${id}')`;
        // Set the new onchange attribute
        inputElement.setAttribute("onchange", newOnchangeFunction);

    }

    const removeThumbnail = (thumbnailId) => {
        const thumbnailToRemove = document.getElementById(thumbnailId);
        if (thumbnailToRemove) {
            thumbnailToRemove.parentNode.removeChild(thumbnailToRemove);
        }
    }

    const generateCode = () => {
        const code = document.getElementById('barcode');
        code.value = Math.floor(Math.random() * 900000) + 100000;
    }
</script>
<script> 

  $(document).ready(function() {
    // Initialize summernote
    $('#description').summernote({
      height: 200,
      placeholder: 'Enter Product Description',
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['font', ['fontsize', 'forecolor']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'picture']],
        ['view', ['fullscreen', 'codeview']]
      ]
    });
  });
</script>
@endpush

