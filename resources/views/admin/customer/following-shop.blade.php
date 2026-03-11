@extends('layouts.app')
@section('header-title', __('Following Stores'))
@section('content')

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">  

            <div class="row mb-3 mt-4 g-2">
                <div class="col-md-4">
                    <input type="text" id="search" class="form-control" placeholder="Search Store Name" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button id="resetSearch" class="btn btn-outline-secondary"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table border-left-right table-responsive-lg">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Follow Date</th>
                            <th>Image</th>
                            <th>Store ID</th>
                            <th>Store Name</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($stores as $key => $store)
                            <tr>
                                <td>{{ $stores->firstItem() + $key }}</td>
                                <td>{{ \Carbon\Carbon::parse($store->followed_at)->format('d M Y | h:i A') }}</td>
                                <td>
                                    <img src="{{ $store->logo }}" alt="{{ $store->name }}"
                                        class="rounded" width="44" height="44"
                                        style="object-fit: cover;">
                                </td>
                                <td>{{ $store->shop_code ?? '-' }}</td>
                                <td>{{ $store->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No following stores found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table> 
            </div>
            <div class="mt-3">
                {{ $stores->withQueryString()->links() }}
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
            window.location = `?search=${value}`;
        }, 500);
    });

    $('#resetSearch').on('click', function () {
        window.location = "{{ route('admin.customer.following-shop', $customerId) }}";
    });
</script>
@endpush

