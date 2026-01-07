@extends('layouts.app')
@section('header-title', __('Business Categories'))
@section('content')

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body"> 
            @include('admin.shop.header-nav')

            <div class="row mb-3 mt-4 g-2">
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
                            <th>Business Category</th>
                            <th>Category</th>
                            <th class="text-center">Total Products</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($Categories as $businessCategory)

                            @if($businessCategory->categories->isEmpty())
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $businessCategory->name }}</td>
                                    <td class="text-muted">No categories</td>
                                    <td class="text-center">0</td>
                                </tr>
                            @else
                                @foreach($businessCategory->categories as $category)
                                    <tr>
                                        <td class="text-center">{{ $loop->parent->iteration }}</td>
                                        <td>{{ $businessCategory->name }}</td>
                                        <td>{{ $category->name }}</td>
                                        <td class="text-center">{{ $category->products_count }}</td>
                                    </tr>
                                @endforeach
                            @endif

                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No data found</td>
                        </tr>
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
    let timer;

    $('#search').on('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            let value = $(this).val();
            let url = "{{ route('admin.shop.category', $shop->id) }}";
            window.location = `${url}?search=${value}`;
        }, 500);
    });

    $('#resetSearch').on('click', function () {
        window.location = "{{ route('admin.shop.category', $shop->id) }}";
    });
</script>
@endpush

