@extends('layouts.app')
@section('header-title', __('Business Categories'))
@section('content')

<div class="d-flex justify-content-between align-items-center px-3 mb-3">
    <h4>{{ __('Business Category List') }}</h4>
    <button class="btn btn-primary" id="addCategoryBtn" data-bs-toggle="modal" data-bs-target="#businessCategoryModal">
        <i class="fa fa-plus"></i> {{ __('Add Business Category') }}
    </button>
</div>

<div class="container-fluid">
    <!-- Search --> 
    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <input type="text" id="search" class="form-control" placeholder="Search Category" value="{{ request('search') }}">
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
                            <th>Name</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
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
                                <button class="btn btn-outline-primary editBtn" data-id="{{ $category->id }}" data-name="{{ $category->name }}"><i class="fa fa-edit"></i></button>
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

<!-- CREATE / EDIT MODAL (SINGLE) -->
<div class="modal fade" id="businessCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="categoryForm" method="POST">@csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Create Business Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Business Category Name</label>
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Cancel </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn"> Submit </button>
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

    // CREATE MODE
    document.getElementById('addCategoryBtn').addEventListener('click', function () {
        document.getElementById('modalTitle').innerText = 'Create Business Category';
        document.getElementById('categoryForm').action = "{{ route('admin.business-category.store') }}";
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('categoryName').value = '';
    });

    // EDIT MODE
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            let id = this.dataset.id;
            let name = this.dataset.name;
            document.getElementById('modalTitle').innerText = 'Edit Business Category';
            document.getElementById('categoryName').value = name;
            document.getElementById('categoryForm').action = `/admin/business-category/${id}/update`;
            document.getElementById('formMethod').value = 'PUT';
            new bootstrap.Modal(
                document.getElementById('businessCategoryModal')
            ).show();
        });
    });
</script>
@endpush
