@extends('layouts.app')
@section('header-title', __('Categories'))
@section('content')

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('Categories') }}</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark" id="reorderCategoryAlphabetBtn"><i class="fa fa-sort-alpha-asc"></i> Reorder A-Z</button>
        @hasPermission('admin.category.create')
        <button class="btn btn-primary" id="addCategoryBtn"><i class="fa fa-plus"></i> Add Category</button>
        @endhasPermission
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body"> 
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <select id="filter_business_category_id" class="form-control">
                        <option value="">All Business Categories</option>
                        @foreach($businessCategories as $bc)
                            <option value="{{ $bc->id }}" {{ (string) request('business_category_id') === (string) $bc->id ? 'selected' : '' }}>{{ $bc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" id="search" class="form-control" placeholder="Search Business/Main Categories" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button id="resetSearch" class="btn btn-outline-secondary"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">SL</th>
                            <th>Thumbnail</th>
                            <th>{!! sortLink('Business Category', 'business_name') !!}</th>
                            <th>{!! sortLink('Category', 'category_name') !!}</th>
                            <th>Product Count</th>
                            @hasPermission('admin.category.toggle')
                            <th>Status</th>
                            @endhasPermission
                            <th>Move</th>
                            @hasPermission('admin.category.edit')
                            <th class="text-center">Action</th>
                            @endhasPermission
                        </tr>
                    </thead>

                    <tbody id="sortable-categories">
                        @forelse($Categories as $key => $Category)
                            <tr data-id="{{ $Category->id }}" data-order="{{ $Category->sort_order }}">
                                <td class="text-center">{{ $Categories->firstItem() + $key }}</td>
                                <td><img src="{{ $Category->thumbnail }}" width="50"></td>
                                <td>{{ $Category->businessCategory?->name ?? 'N/A' }}</td> 
                                <td>{{ $Category->name }}</td>
                                <td>{{ $Category->products_count }}</td>
                                @hasPermission('admin.category.toggle')
                                <td class="text-center"> 
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $Category->id }}" {{ $Category->status ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                @endhasPermission
                                <td class="drag-handle" style="cursor: grab;"><i class="fa fa-bars"></i></td>
                                @hasPermission('admin.category.edit')
                                <td class="text-center"> 
                                    <a href="{{ route('admin.product.index', ['approve' => 'true', 'business_category' => $Category->business_category_id, 'category' => $Category->id]) }}"
                                        class="btn btn-outline-secondary" title="View Accepted Products">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="javascript:;" class="btn btn-outline-primary editCategoryButton" data-id="{{$Category->id}}"><i class="fa fa-edit"></i></a>
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
                {{ $Categories->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="CategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="CategoryForm" method="POST" enctype="multipart/form-data">@csrf 
                <input type="hidden" name="cat_id" id="cat_id">
                <div class="modal-header"> 
                    <h4 class="modal-title" id="CategoryModelHeading"></h4>
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
                        <label>Category Name</label>
                        <input type="text" name="name" id="cat_name" class="form-control" required>
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
                    <button class="btn btn-primary" id="saveCategoryButton">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts') 
<script src="{{ asset('assets/scripts/Sortable.min.js') }}"></script>
<script>
    const indexRoute = "{{ route('admin.category.index') }}";

    function applyListFilters() {
        const params = new URLSearchParams(window.location.search);
        const search = $('#search').val().trim();
        const businessId = $('#filter_business_category_id').val();

        if (search) {
            params.set('search', search);
        } else {
            params.delete('search');
        }

        if (businessId) {
            params.set('business_category_id', businessId);
        } else {
            params.delete('business_category_id');
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
    $('#filter_business_category_id').on('change', applyListFilters);
    $('#resetSearch').on('click', function () {
        window.location = indexRoute;
    });

    // Add/Edit category
    $('#addCategoryBtn').click(function() {
        resetSubCategoryForm(); 
        $('#CategoryModelHeading').html("Create New Category");
        $('#CategoryModal').modal('show');
        $('#CategoryForm').data('mode', 'create');
    });
    function resetSubCategoryForm() {
        $('#CategoryForm')[0].reset();
        $('#cat_id').val('');
        $('#previewProfile').attr('src', 'https://placehold.co/500x500/f1f5f9/png');
        $('.textdanger').remove();
    }

    $('#saveCategoryButton').click(function (e) {
        e.preventDefault();
        let id = $('#cat_id').val();
        let formData = new FormData($('#CategoryForm')[0]);
        let url = "{{ route('admin.category.store') }}";
        let method = "POST";
        if (id) {
            url = `/admin/category/${id}/update`;
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#CategoryModal').modal('hide'); 
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
    
    $('body').on('click', '.editCategoryButton', function() {
        let id = $(this).data('id');
        $('#CategoryForm')[0].reset();
        $.get(`/admin/category/${id}/edit`, function (data) { 
            $('#CategoryModelHeading').html("Edit Category");
            $('#CategoryModal').modal('show');

            $('#cat_id').val(data.id);
            $('#cat_name').val(data.name);
            $('#previewProfile').attr('src', data.thumbnail ?? 'https://placehold.co/500x500/f1f5f9/png');
            // Business Category
            $('#business_category').val(data.business_category_id); 
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

    // Status Toggle(Ajax)
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            fetch(`/admin/category/${this.dataset.id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        });
    });

    const sortableCategoryBody = document.getElementById('sortable-categories');
    if (sortableCategoryBody && typeof Sortable !== 'undefined') {
        new Sortable(sortableCategoryBody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                const rows = Array.from(sortableCategoryBody.querySelectorAll('tr[data-id]'));
                const ids = rows.map(row => Number(row.dataset.id));
                const orders = rows.map(row => Number(row.dataset.order || 0)).filter(order => order > 0);
                const base = orders.length ? Math.min(...orders) : 1;

                fetch("{{ route('admin.category.reorder') }}", {
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

    $('#reorderCategoryAlphabetBtn').on('click', function () {
        Swal.fire({
            title: 'Apply Alphabetic Order?',
            text: 'This will reorder current category list from A to Z.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, reorder',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch("{{ route('admin.category.reorder-alphabetic') }}", {
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
