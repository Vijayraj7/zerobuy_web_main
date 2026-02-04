@extends('layouts.app')
@section('header-title', __('Child Categories'))
@section('content')

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('Child Categories') }}</h4> 
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
                            <th>SL</th>
                            <th>Thumbnail</th>
                            <th>{!! sortLink('Business Category', 'business_name') !!}</th>
                            <th>{!! sortLink('Category', 'category_name') !!}</th>
                            <th>{!! sortLink('Sub Category', 'sub_category_name') !!}</th>
                            <th>{!! sortLink('Child Category', 'child_name') !!}</th> 
                            <th>Product Count</th>
                            <th>Status</th> 
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
                                <td>{{ $childCategory->products_count }} </td>
                                <td class="text-center">
                                    <label class="switch mb-0">
                                        <a href="javascript:void(0)">
                                            <input type="checkbox" {{ $childCategory->status ? 'checked' : '' }}>
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
                {{ $childCategories->withQueryString()->links() }}
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
        window.location = "{{ route('shop.child-category.index') }}";
    }); 
</script> 

@endpush
