@extends('layouts.app')
@section('header-title', __('Sub Categories'))
@section('content')

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('Sub Categories') }}</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark" id="reorderSubCategoryAlphabetBtn"><i class="fa fa-sort-alpha-asc"></i> Reorder A-Z</button>
        @hasPermission('admin.subcategory.create')
        <button class="btn btn-primary" id="addSubCategoryBtn"><i class="fa fa-plus"></i> Add Sub Category</button>
        @endhasPermission
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body"> 
            <div class="row mb-3 g-2">
                <div class="col-md-3">
                    <select id="filter_business_category_id" class="form-control">
                        <option value="">All Business Categories</option>
                        @foreach($businessCategories as $bc)
                            <option value="{{ $bc->id }}" {{ (string) request('business_category_id') === (string) $bc->id ? 'selected' : '' }}>{{ $bc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filter_category_id" class="form-control">
                        <option value="">All Main Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
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
                            <th>Product Count</th>
                            @hasPermission('admin.subcategory.toggle')
                            <th>Status</th>
                            @endhasPermission
                            <th>Move</th>
                            @hasPermission('admin.subcategory.edit')
                            <th class="text-center">Action</th>
                            @endhasPermission
                        </tr>
                    </thead>

                    <tbody id="sortable-subcategories">
                        @forelse($subCategories as $key => $subCategory)
                            <tr data-id="{{ $subCategory->id }}" data-order="{{ $subCategory->sort_order }}">
                                <td class="text-center">{{ $subCategories->firstItem() + $key }}</td>
                                <td><img src="{{ $subCategory->thumbnail }}" width="50"></td>
                                <td>{{ $subCategory->businessCategory?->name ?? 'N/A' }}</td>
                                <td>{{ $subCategory->category?->name ?? 'N/A' }}</td> 
                                <td>{{ $subCategory->name }}</td>
                                <td> {{ $subCategory->products_count }} </td>
                                @hasPermission('admin.subcategory.toggle')
                                <td class="text-center"> 
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $subCategory->id }}" {{ $subCategory->is_active ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                @endhasPermission
                                <td class="drag-handle" style="cursor: grab;"><i class="fa fa-bars"></i></td>
                                @hasPermission('admin.subcategory.edit')
                                <td class="text-center"> 
                                    <a href="{{ route('admin.product.index', ['approve' => 'true', 'business_category' => $subCategory->business_category_id, 'category' => $subCategory->category_id, 'sub_category' => $subCategory->id]) }}"
                                        class="btn btn-outline-secondary" title="View Accepted Products">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="javascript:;" class="btn btn-outline-primary editSubCategoryButton" data-id="{{$subCategory->id}}"><i class="fa fa-edit"></i></a>
                                    @hasPermission('admin.subcategory.destroy')
                                        @if((int) $subCategory->products_count === 0)
                                            <form action="{{ route('admin.subcategory.destroy', $subCategory->id) }}" method="POST" class="d-inline deleteConfirmForm">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-outline-danger" title="Cannot delete, products are linked" disabled>
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    @endhasPermission
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
<script src="{{ asset('assets/scripts/Sortable.min.js') }}"></script>
<script>
    const indexRoute = "{{ route('admin.subcategory.index') }}";

    function applyListFilters() {
        const params = new URLSearchParams(window.location.search);
        const search = $('#search').val().trim();
        const businessId = $('#filter_business_category_id').val();
        const categoryId = $('#filter_category_id').val();

        if (search) {
            params.set('search', search);
        } else {
            params.delete('search');
        }

        if (businessId) {
            params.set('business_category_id', businessId);
        } else {
            params.delete('business_category_id');
            params.delete('category_id');
        }

        if (categoryId && businessId) {
            params.set('category_id', categoryId);
        } else {
            params.delete('category_id');
        }

        window.location = `${indexRoute}?${params.toString()}`;
    }

    // Search
    let timer;
    $('#search').on('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            applyListFilters();
        }, 500);
    });
    $('#filter_business_category_id').on('change', function () {
        const businessId = this.value;
        const categorySelect = $('#filter_category_id');
        categorySelect.html('<option value="">All Main Categories</option>');

        if (!businessId) {
            applyListFilters();
            return;
        }

        $.get(`/admin/get-categories/${businessId}`, res => {
            res.forEach(item => categorySelect.append(`<option value="${item.id}">${item.name}</option>`));
            applyListFilters();
        });
    });
    $('#filter_category_id').on('change', applyListFilters);
    $('#resetSearch').on('click', function () {
        window.location = indexRoute;
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

    const sortableSubBody = document.getElementById('sortable-subcategories');
    if (sortableSubBody && typeof Sortable !== 'undefined') {
        new Sortable(sortableSubBody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                const rows = Array.from(sortableSubBody.querySelectorAll('tr[data-id]'));
                const ids = rows.map(row => Number(row.dataset.id));
                const orders = rows.map(row => Number(row.dataset.order || 0)).filter(order => order > 0);
                const base = orders.length ? Math.min(...orders) : 1;

                fetch("{{ route('admin.subcategory.reorder') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids, base })
                }).then(() => {
                    Toast.fire({ icon: 'success', title: 'Order saved' });
                    setTimeout(() => {
                        window.location.reload();
                    }, 700);
                });
            }
        });
    }

    $('#reorderSubCategoryAlphabetBtn').on('click', function () {
        Swal.fire({
            title: 'Apply Alphabetic Order?',
            text: 'This will reorder current sub category list from A to Z.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, reorder',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch("{{ route('admin.subcategory.reorder-alphabetic') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => {
                Toast.fire({ icon: 'success', title: 'Reordered A-Z' });
                setTimeout(() => {
                    window.location.reload();
                }, 700);
            });
        });
    });
</script> 

@endpush
