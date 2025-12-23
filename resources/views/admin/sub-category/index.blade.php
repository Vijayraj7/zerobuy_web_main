@extends('layouts.app')
@section('header-title', __('Sub Categories'))
@section('content')

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('Sub Categories') }}</h4>
    @hasPermission('admin.subcategory.create')
    <button class="btn btn-primary" id="addSubCategoryBtn"><i class="fa fa-plus"></i> Add Sub Category</button>
    @endhasPermission
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body"> 
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <input type="text" id="search" class="form-control" placeholder="Search Business/Main/Sub Categories" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button id="resetSearch" class="btn btn-outline-secondary"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Thumbnail</th> 
                            <th>{!! sortLink('Business Category', 'business_name') !!}</th>
                            <th>{!! sortLink('Category', 'category_name') !!}</th>
                            <th>{!! sortLink('Sub Category', 'sub_category_name') !!}</th> 
                            @hasPermission('admin.subcategory.toggle')
                            <th>Status</th>
                            @endhasPermission
                            @hasPermission('admin.subcategory.edit')
                            <th class="text-center">Action</th>
                            @endhasPermission
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($subCategories as $key => $subCategory)
                            <tr>
                                <td class="text-center">{{ $subCategories->firstItem() + $key }}</td>
                                <td><img src="{{ $subCategory->thumbnail }}" width="50"></td>
                                <td>{{ $subCategory->businessCategory?->name ?? 'N/A' }}</td>
                                <td>{{ $subCategory->category?->name ?? 'N/A' }}</td> 
                                <td>{{ $subCategory->name }}</td>
                                @hasPermission('admin.subcategory.toggle')
                                <td class="text-center"> 
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $subCategory->id }}" {{ $subCategory->is_active ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                @endhasPermission
                                @hasPermission('admin.subcategory.edit')
                                <td class="text-center"> 
                                    <a href="javascript:;" class="btn btn-outline-primary editSubCategoryButton" data-id="{{$subCategory->id}}"><i class="fa fa-edit"></i></a>
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
                {{ $subCategories->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="subCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="subCategoryForm" method="POST" enctype="multipart/form-data">@csrf 
                <input type="hidden" name="sub_cat_id" id="sub_cat_id">
                <div class="modal-header"> 
                    <h4 class="modal-title" id="subCategoryModelHeading"></h4>
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
                        <label>Sub Category Name</label>
                        <input type="text" name="name" id="sub_name" class="form-control" required>
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
                    <button class="btn btn-primary" id="saveSubCategoryButton">Submit</button>
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
        window.location = "{{ route('admin.subcategory.index') }}";
    });

    // Add/Edit subcategory
    $('#addSubCategoryBtn').click(function() {
        resetSubCategoryForm(); 
        $('#subCategoryModelHeading').html("Create New Sub Category");
        $('#subCategoryModal').modal('show');
        $('#subCategoryForm').data('mode', 'create');
    });
    function resetSubCategoryForm() {
        $('#subCategoryForm')[0].reset();
        $('#sub_cat_id').val('');
        $('#category').html('<option value="">Select Category</option>'); 
        $('#previewProfile').attr('src', 'https://placehold.co/500x500/f1f5f9/png');
        $('.textdanger').remove();
    }

    $('#saveSubCategoryButton').click(function (e) {
        e.preventDefault();
        let id = $('#sub_cat_id').val();
        let formData = new FormData($('#subCategoryForm')[0]);
        let url = "{{ route('admin.subcategory.store') }}";
        let method = "POST";
        if (id) {
            url = `/admin/subcategory/${id}/update`;
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#subCategoryModal').modal('hide'); 
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
    
    $('body').on('click', '.editSubCategoryButton', function() {
        let id = $(this).data('id');
        $('#subCategoryForm')[0].reset();
        $('#category').html('<option>Loading...</option>');

        $.get(`/admin/subcategory/${id}/edit`, function (data) {
            const sub = data.subCategory;
            const categories = data.categories;
            const subCategories = data.subCategories;

            $('#subCategoryModelHeading').html("Edit Sub Category");
            $('#subCategoryModal').modal('show');

            $('#sub_cat_id').val(sub.id);
            $('#sub_name').val(sub.name);
            $('#previewProfile').attr('src', data.thumbnail ?? 'https://placehold.co/500x500/f1f5f9/png');
            // Business Category
            $('#business_category').val(sub.business_category_id);
            // Populate Category dropdown
            let categoryHtml = '<option value="">Select Category</option>';
            categories.forEach(cat => {
                categoryHtml += `<option value="${cat.id}" ${cat.id == sub.category_id ? 'selected' : ''}>${cat.name}</option>`;
            });
            $('#category').html(categoryHtml); 
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
        if (id) {
            $.get(`/admin/get-categories/${id}`, res => {
                $('#category').html('<option value="">Select Category</option>');
                res.forEach(i => $('#category').append(`<option value="${i.id}">${i.name}</option>`));
            });
        }
    }); 

    // Status Toggle(Ajax)
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            fetch(`/admin/subcategory/${this.dataset.id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        });
    });
</script> 

@endpush
