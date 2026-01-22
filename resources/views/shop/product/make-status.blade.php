@extends('layouts.app')
@section('header-title', __('Status'))

@section('content')

<div class="d-flex justify-content-between px-3 mb-3">
    <h4>Status List</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
        Add Status
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Start</th>
                    <th>Expire</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statuses as $status)
                <tr>
                    <td>
                        <img src="{{ $status->product->thumbnail }}" width="40" class="rounded">
                        {{ $status->product->name }}
                    </td>

                    <td>{{ optional($status->started_at)->format('d-m-Y | h:i A') }}</td>
                    <td>{{ optional($status->expired_at)->format('d-m-Y | h:i A') }}</td>

                    <td>{{ $status->message }}</td>

                    <td>
                        @if($status->is_active)
                            <span class="badge bg-success">Active</span>
                        @elseif($status->expired_at && $status->expired_at->lte(now()))
                            <span class="badge bg-danger">Expired</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td> 
                    
                    <td>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('shop.productStatus.toggle', $status) }}">
                                @csrf
                                <button
                                    class="btn btn-sm {{ $status->is_active ? 'btn-success' : 'btn-secondary' }}"
                                    {{ $status->expired_at && $status->expired_at->lte(now()) ? 'disabled' : '' }}>
                                    {{ $status->is_active ? 'ON' : 'OFF' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('shop.productStatus.destroy', $status) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="statusModal">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('shop.productStatus.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Add Product Status</h5>
                </div>

                <div class="modal-body">
                    <input type="text" class="form-control mb-2" id="productSearch"
                        placeholder="Search by ID / Name / PRD ID">

                    <div class="table-responsive" style="max-height:300px">
                        <table class="table">
                            @foreach($products as $product)
                            <tr class="product-row">
                                <td>
                                    <input type="radio" name="product_id" value="{{ $product->id }}">
                                </td>
                                <td>
                                    <img src="{{ asset($product->thumbnail ?? 'default.png') }}" width="40" height="40"
                                        class="rounded">

                                </td>
                                <td>
                                    {{ $product->formatted_id }} - {{ $product->name }}
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>

                    <textarea name="message" class="form-control mt-2" placeholder="Message"></textarea>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div> 

@endsection




@push('scripts')

<script>
$('#productSearch').on('keyup', function() {
    let val = $(this).val().toLowerCase();
    $('.product-row').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
    });
});
</script>

@endpush