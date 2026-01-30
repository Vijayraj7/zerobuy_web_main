@extends('layouts.app')
@section('header-title', __('Status'))

@section('content')

<div class="d-flex justify-content-between px-3 mb-3">
    <h4>All Shops Status Lists</h4> 
</div>

<div class="card">
    <div class="card-body">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Shop ID</th>
                    <th>Shop Name</th>
                    <th>Start</th>
                    <th>Expire</th>
                    <th>Message</th>
                    <th>Total Views</th>
                    <th>Status</th>
                    <!-- <th>Action</th> -->
                </tr>
            </thead>
            <tbody>
                @foreach($statuses as $status)
                <tr>
                    <td><img src="{{ $status->product->thumbnail }}" width="40" class="rounded"> {{ $status->product->name }} </td>
                    <td>STR0{{ $status->shop?->id ?? '-' }}</td>
                    <td>{{ $status->shop?->name ?? '-' }}</td>
                    <td>{{ optional($status->started_at)->format('d-m-Y | h:i A') }}</td>
                    <td>{{ optional($status->expired_at)->format('d-m-Y | h:i A') }}</td>
                    <td>{{ $status->message }}</td>
                    <td>{{ $status->views }}</td>
                    <td>
                        @if($status->is_active)
                            <span class="badge bg-success">Active</span>
                        @elseif($status->expired_at && $status->expired_at->lte(now()))
                            <span class="badge bg-danger">Expired</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td> 
                    <!-- <td>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('admin.productStatus.toggle', $status) }}">
                                @csrf
                                <button
                                    class="btn btn-sm {{ $status->is_active ? 'btn-success' : 'btn-secondary' }}"
                                    {{ $status->expired_at && $status->expired_at->lte(now()) ? 'disabled' : '' }}>
                                    {{ $status->is_active ? 'ON' : 'OFF' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.productStatus.destroy', $status) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td> -->

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
 
@endsection




@push('scripts')

@endpush