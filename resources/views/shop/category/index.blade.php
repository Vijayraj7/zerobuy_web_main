@extends('layouts.app')
@section('header-title', __('Category List'))
@section('content')
    <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
        <h4>
            {{ __('Category List') }}
        </h4>
    </div> 

    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-body"> 
                <div class="row mb-3 g-2">
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
                                <th>Status</th> 
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($Categories as $key => $Category)
                                <tr>
                                    <td class="text-center">{{ $Categories->firstItem() + $key }}</td>
                                    <td><img src="{{ $Category->thumbnail }}" width="50"></td>
                                    <td>{{ $Category->businessCategory?->name ?? 'N/A' }}</td> 
                                    <td>{{ $Category->name }}</td>
                                    <td>{{ $Category->products_count }}</td>
                                    <td>
                                        <label class="switch mb-0">
                                            <a href="javascript:void(0)">
                                                <input type="checkbox" {{ $Category->status ? 'checked' : '' }}>
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
                    {{ $Categories->withQueryString()->links() }}
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
        window.location = "{{ route('shop.category.index') }}";
    });
</script>
@endpush