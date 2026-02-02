@extends('layouts.app')
@section('header-title', __('Child Categories'))
@section('content')

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('Child Categories') }}</h4>
    @hasPermission('admin.child-category.create')
    <button class="btn btn-primary" id="addChildCategoryBtn"><i class="fa fa-plus"></i> Add Child Category</button>
    @endhasPermission
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body"> 
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <input type="text" id="search" class="form-control" placeholder="Search Business/Main/Sub/Child Categories" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button id="resetSearch" class="btn btn-outline-secondary"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <!-- <th class="text-center">SL</th>
                            <th>Thumbnail</th>
                            <th>Business Category</th>
                            <th>Category</th>
                            <th>Sub Category</th>
                            <th>Child Category</th> -->
                            <th>SL</th>
                            <th>Thumbnail</th>
                            <th>{!! sortLink('Business Category', 'business_name') !!}</th>
                            <th>{!! sortLink('Category', 'category_name') !!}</th>
                            <th>{!! sortLink('Sub Category', 'sub_category_name') !!}</th>
                            <th>{!! sortLink('Child Category', 'child_name') !!}</th>
                            <th>Product Count</th>
                            @hasPermission('admin.child-category.toggle')
                            <th>Status</th>
                            @endhasPermission
                            @hasPermission('admin.child-category.edit')
                            <th class="text-center">Action</th>
                            @endhasPermission
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($childCategories as $key => $childCategory)
                            <tr>
                                <td class="text-center">{{ $childCategories->firstItem() + $key }}</td>
                                <td><img src="{{ $childCategory->thumbnail }}" width="50"></td>
                                <td>{{ $childCategory->businessCategory?->name ?? 'N/A' }}</td>
                                <td>{{ $childCategory->category?->name ?? 'N/A' }}</td>
                                <td>{{ $childCategory->subCategory?->name ?? 'N/A' }}</td>
                                <td>{{ $childCategory->name }}</td>
                                <td>{{ $childCategory->products_count }}</td>
                                @hasPermission('admin.child-category.toggle')
                                <td class="text-center"> 
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $childCategory->id }}" {{ $childCategory->status ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                @endhasPermission
                                @hasPermission('admin.child-category.edit')
                                <td class="text-center"> 
                                    <a href="javascript:;" class="btn btn-outline-primary editChildCategoryButton" data-id="{{$childCategory->id}}"><i class="fa fa-edit"></i></a>
                                </td>
                                @endhasPermission
                            </tr>
                        @empty
                            <tr><td colspan="100%" class="text-center">No Data Found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $childCategories->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="childCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="childCategoryForm" method="POST" enctype="multipart/form-data">@csrf 
                <input type="hidden" name="child_cat_id" id="child_cat_id">
                <div class="modal-header"> 
                    <h4 class="modal-title" id="childCategoryModelHeading"></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Business Category</label>
                        <select id="business_category" name="business_category_id" class="form-control" required>
                            <option value="">Select Business Category</option>
                            @foreach($businessCategories as $bc)
                            <option value="{{ $bc->id }}">{{ $bc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <select id="category" name="category_id" class="form-control" required></select>
                    </div>
                    <div class="mb-3">
                        <label>Sub Category</label>
                        <select id="sub_category" name="sub_category_id" class="form-control" required></select>
                    </div>
                    <div class="mb-3">
                        <label>Child Category Name</label>
                        <input type="text" name="name" id="child_name" class="form-control" required>
                    </div>
                    <div class="mt-3 d-flex align-items-center justify-content-center">
                        <div class="ratio1x1">
                            <img id="previewProfile" src="https://placehold.co/500x500/f1f5f9/png" alt="" width="100%">
                        </div>
                    </div>
                    <div class="mt-3"> 
                        <label>Thumbnail (Ratio 1:1)</label>
                        <input type="file" name="thumbnail" class="form-control">
                    </div>
                </div>
                <div class="modal-footer"> 
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="saveChildCategoryButton">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts') 
<script>
    // Search
    let timer;
    $('#search').on('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            let value = $(this).val();
            window.location = `?search=${value}`;
        }, 500);
    });
    $('#resetSearch').on('click', function () {
        window.location = "{{ route('admin.child-category.index') }}";
    });

    // Add/Edit child-category
    $('#addChildCategoryBtn').click(function() {
        resetChildCategoryForm();
        // $('#childCategoryForm').trigger("reset");
        // $('#previewProfile').attr('src', 'https://placehold.co/500x500/f1f5f9/png'); // reset image
        // $('#saveChildCategoryButton').val("create-post");
        // $('#child_cat_id').val('');
        $('#childCategoryModelHeading').html("Create New Child Category");
        $('#childCategoryModal').modal('show');
        $('#childCategoryForm').data('mode', 'create');
    });
    function resetChildCategoryForm() {
        $('#childCategoryForm')[0].reset();
        $('#child_cat_id').val('');
        $('#category').html('<option value="">Select Category</option>');
        $('#sub_category').html('<option value="">Select Sub Category</option>');
        $('#previewProfile').attr('src', 'https://placehold.co/500x500/f1f5f9/png');
        $('.textdanger').remove();
    }

    $('#saveChildCategoryButton').click(function (e) {
        e.preventDefault();
        let id = $('#child_cat_id').val();
        let formData = new FormData($('#childCategoryForm')[0]);
        let url = "{{ route('admin.child-category.store') }}";
        let method = "POST";
        if (id) {
            url = `/admin/child-category/${id}/update`;
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#childCategoryModal').modal('hide'); 
                Toast.fire({
                    icon: 'success',
                    title: res.message
                });
                setTimeout(() => location.reload(), 1200); 
            },
            error: function (xhr) {
                $('.textdanger').remove();
                $.each(xhr.responseJSON.errors, function (key, value) {
                    $('[name="'+key+'"]').after(
                        `<span class="textdanger text-danger">${value}</span>`
                    );
                });
            }
        });
    }); 
    
    $('body').on('click', '.editChildCategoryButton', function() {
        let id = $(this).data('id');
        $('#childCategoryForm')[0].reset();
        $('#category, #sub_category').html('<option>Loading...</option>');

        $.get(`/admin/child-category/${id}/edit`, function (data) {
            const child = data.childCategory;
            const categories = data.categories;
            const subCategories = data.subCategories;

            $('#childCategoryModelHeading').html("Edit Child Category");
            $('#childCategoryModal').modal('show');

            $('#child_cat_id').val(child.id);
            $('#child_name').val(child.name);
            $('#previewProfile').attr('src', data.thumbnail ?? 'https://placehold.co/500x500/f1f5f9/png');
            // Business Category
            $('#business_category').val(child.business_category_id);
            // Populate Category dropdown
            let categoryHtml = '<option value="">Select Category</option>';
            categories.forEach(cat => {
                categoryHtml += `<option value="${cat.id}" ${cat.id == child.category_id ? 'selected' : ''}>${cat.name}</option>`;
            });
            $('#category').html(categoryHtml);
            // Populate Sub Category dropdown
            let subHtml = '<option value="">Select Sub Category</option>';
            subCategories.forEach(sub => {
                subHtml += `<option value="${sub.id}" ${sub.id == child.sub_category_id ? 'selected' : ''}>${sub.name}</option>`;
            });
            $('#sub_category').html(subHtml);
        });
    });

    // Image Preview
    $('input[name="thumbnail"]').on('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        // validate image type
        if (!file.type.startsWith('image/')) {
            showFlash('Please select an image file');
            $(this).val('');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#previewProfile').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
    });

    // Dropdowns
    $('#business_category').on('change', function () {
        let id = this.value;
        $('#category').html('<option>Loading...</option>');
        $('#sub_category').html('<option>Select Sub Category</option>');

        if (id) {
            $.get(`/admin/get-categories/${id}`, res => {
                $('#category').html('<option value="">Select Category</option>');
                res.forEach(i => $('#category').append(`<option value="${i.id}">${i.name}</option>`));
            });
        }
    });

    $('#category').on('change', function () {
        let id = this.value;
        $('#sub_category').html('<option>Loading...</option>');

        if (id) {
            $.get(`/admin/get-subcategories/${id}`, res => {
                $('#sub_category').html('<option value="">Select Sub Category</option>');
                res.forEach(i => $('#sub_category').append(`<option value="${i.id}">${i.name}</option>`));
            });
        }
    });

    // Status Toggle(Ajax)
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            fetch(`/admin/child-category/${this.dataset.id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        });
    });
</script> 

@endpush
