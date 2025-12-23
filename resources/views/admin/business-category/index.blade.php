@extends('layouts.app')
@section('header-title', __('Business Categories'))
@section('content')

<div class="d-flex justify-content-between align-items-center px-3 mb-3">
    <h4>{{ __('Business Category List') }}</h4> 
    <button class="btn btn-primary" id="addBusinessCategoryBtn"><i class="fa fa-plus"></i> Add Business Category </button>
</div>

<div class="container-fluid">
    <!-- Search --> 
    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <input type="text" id="search" class="form-control" placeholder="Search Business Categories" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <button id="resetSearch" class="btn btn-outline-secondary"><i class="fa fa-refresh"></i></button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Thumbnail</th>
                            <th>{!! sortLink('Business Category', 'name') !!}</th>
                            <!-- <th>Name</th> -->
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><img src="{{ $category->thumbnail }}" width="50"></td>
                            <td>{{ $category->name }}</td>
                            <!-- Status -->
                            <td>
                                <label class="switch">
                                    <input type="checkbox" class="status-toggle" data-id="{{ $category->id }}" {{ $category->status ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <!-- Action -->
                            <td class="text-center"> 
                                <a href="javascript:;" class="btn btn-outline-primary editBusinessCategoryButton" data-id="{{$category->id}}"><i class="fa fa-edit"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No business categories found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        </div>
    </div>
</div> 

{{-- MODAL --}}
<div class="modal fade" id="BusinessCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="BusinessCategoryForm" method="POST" enctype="multipart/form-data">@csrf 
                <input type="hidden" name="business_id" id="business_id">
                <div class="modal-header"> 
                    <h4 class="modal-title" id="BusinessCategoryModelHeading"></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"> 
                    <div class="mb-3">
                        <label>Business Category Name</label>
                        <input type="text" name="name" id="business_name" class="form-control" required>
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
                    <button class="btn btn-primary" id="saveBusinessCategoryButton">Submit</button>
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
        window.location = "{{ route('admin.business-category.index') }}";
    });

    // STATUS TOGGLE (AJAX)
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            fetch(`/admin/business-category/${this.dataset.id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        });
    });

    // Add/Edit category
    $('#addBusinessCategoryBtn').click(function() {
        resetBusinessCategoryForm(); 
        $('#BusinessCategoryModelHeading').html("Create New Business Category");
        $('#BusinessCategoryModal').modal('show');
        $('#BusinessCategoryForm').data('mode', 'create');
    });
    function resetBusinessCategoryForm() {
        $('#BusinessCategoryForm')[0].reset();
        $('#business_id').val('');
        $('#previewProfile').attr('src', 'https://placehold.co/500x500/f1f5f9/png');
        $('.textdanger').remove();
    }

    $('#saveBusinessCategoryButton').click(function (e) {
        e.preventDefault();
        let id = $('#business_id').val();
        let formData = new FormData($('#BusinessCategoryForm')[0]);
        let url = "{{ route('admin.business-category.store') }}";
        let method = "POST";
        if (id) {
            url = `/admin/business-category/${id}/update`;
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#BusinessCategoryModal').modal('hide'); 
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
    
    $('body').on('click', '.editBusinessCategoryButton', function() {
        let id = $(this).data('id');
        $('#BusinessCategoryForm')[0].reset();
        $.get(`/admin/business-category/${id}/edit`, function (data) { 
            $('#BusinessCategoryModelHeading').html("Edit Business Category");
            $('#BusinessCategoryModal').modal('show');

            $('#business_id').val(data.id);
            $('#business_name').val(data.name);
            $('#previewProfile').attr('src', data.thumbnail ?? 'https://placehold.co/500x500/f1f5f9/png'); 
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
</script>
@endpush
