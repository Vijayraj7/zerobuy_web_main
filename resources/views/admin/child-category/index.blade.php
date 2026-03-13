@extends('layouts.app')
@section('header-title', __('Child Categories'))
@section('content')

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('Child Categories') }}</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark" id="reorderChildCategoryAlphabetBtn"><i class="fa fa-sort-alpha-asc"></i> Reorder A-Z</button>
        @hasPermission('admin.child-category.create')
        <button class="btn btn-primary" id="addChildCategoryBtn"><i class="fa fa-plus"></i> Add Child Category</button>
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
                <div class="col-md-2">
                    <select id="filter_sub_category_id" class="form-control">
                        <option value="">All Sub Categories</option>
                        @foreach($subCategories as $sub)
                            <option value="{{ $sub->id }}" {{ (string) request('sub_category_id') === (string) $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
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
                            <th>Move</th>
                            @hasPermission('admin.child-category.edit')
                            <th class="text-center">Action</th>
                            @endhasPermission
                        </tr>
                    </thead>

                    <tbody id="sortable-child-categories">
                        @forelse($childCategories as $key => $childCategory)
                            <tr data-id="{{ $childCategory->id }}" data-order="{{ $childCategory->sort_order }}">
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
                                <td class="drag-handle" style="cursor: grab;"><i class="fa fa-bars"></i></td>
                                @hasPermission('admin.child-category.edit')
                                <td class="text-center"> 
                                    <a href="{{ route('admin.product.index', ['approve' => 'true', 'business_category' => $childCategory->business_category_id, 'category' => $childCategory->category_id, 'sub_category' => $childCategory->sub_category_id, 'child_category' => $childCategory->id]) }}"
                                        class="btn btn-outline-secondary" title="View Accepted Products">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="javascript:;" class="btn btn-outline-primary editChildCategoryButton" data-id="{{$childCategory->id}}"><i class="fa fa-edit"></i></a>
                                    @hasPermission('admin.child-category.destroy')
                                        @if((int) $childCategory->products_count === 0)
                                            <form action="{{ route('admin.child-category.destroy', $childCategory->id) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this child category?');">
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
<script src="{{ asset('assets/scripts/Sortable.min.js') }}"></script>
<script>
    const indexRoute = "{{ route('admin.child-category.index') }}";

    function applyListFilters() {
        const params = new URLSearchParams(window.location.search);
        const search = $('#search').val().trim();
        const businessId = $('#filter_business_category_id').val();
        const categoryId = $('#filter_category_id').val();
        const subCategoryId = $('#filter_sub_category_id').val();

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
            params.delete('sub_category_id');
        }

        if (categoryId && businessId) {
            params.set('category_id', categoryId);
        } else {
            params.delete('category_id');
            params.delete('sub_category_id');
        }

        if (subCategoryId && categoryId) {
            params.set('sub_category_id', subCategoryId);
        } else {
            params.delete('sub_category_id');
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
        const subCategorySelect = $('#filter_sub_category_id');

        categorySelect.html('<option value="">All Main Categories</option>');
        subCategorySelect.html('<option value="">All Sub Categories</option>');

        if (!businessId) {
            applyListFilters();
            return;
        }

        $.get(`/admin/get-categories/${businessId}`, res => {
            res.forEach(item => categorySelect.append(`<option value="${item.id}">${item.name}</option>`));
            applyListFilters();
        });
    });

    $('#filter_category_id').on('change', function () {
        const categoryId = this.value;
        const subCategorySelect = $('#filter_sub_category_id');
        subCategorySelect.html('<option value="">All Sub Categories</option>');

        if (!categoryId) {
            applyListFilters();
            return;
        }

        $.get(`/admin/get-subcategories/${categoryId}`, res => {
            res.forEach(item => subCategorySelect.append(`<option value="${item.id}">${item.name}</option>`));
            applyListFilters();
        });
    });

    $('#filter_sub_category_id').on('change', applyListFilters);
    $('#resetSearch').on('click', function () {
        window.location = indexRoute;
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

    const sortableChildBody = document.getElementById('sortable-child-categories');
    if (sortableChildBody && typeof Sortable !== 'undefined') {
        new Sortable(sortableChildBody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                const rows = Array.from(sortableChildBody.querySelectorAll('tr[data-id]'));
                const ids = rows.map(row => Number(row.dataset.id));
                const orders = rows.map(row => Number(row.dataset.order || 0)).filter(order => order > 0);
                const base = orders.length ? Math.min(...orders) : 1;

                fetch("{{ route('admin.child-category.reorder') }}", {
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

    $('#reorderChildCategoryAlphabetBtn').on('click', function () {
        Swal.fire({
            title: 'Apply Alphabetic Order?',
            text: 'This will reorder current child category list from A to Z.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, reorder',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch("{{ route('admin.child-category.reorder-alphabetic') }}", {
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
