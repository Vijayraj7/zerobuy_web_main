@extends('layouts.app')
@section('header-title', __('Sub Categories'))

@section('content')
    <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
        <h4>
            {{ __('Sub Categories') }}
        </h4>
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
                                <th>Status</th> 
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
                                    <td>
                                        <label class="switch mb-0">
                                            <a href="javascript:void(0);">
                                                <input type="checkbox" {{ $subCategory->is_active ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </a>
                                        </label>
                                    </td> 
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
        window.location = "{{ route('shop.subcategory.index') }}";
    });
</script>
@endpush
