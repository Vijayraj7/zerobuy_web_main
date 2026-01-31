@extends('layouts.app')

@section('header-title', __('Shop Report Details'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>{{ __('Shop Report Details') }}</h4>
        <a href="{{ route('admin.shop-reports.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-2"></i>{{ __('Back to Reports') }}
        </a>
    </div>

    <div class="container-fluid mt-3">

        <div class="row">
            <!-- Report Information -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Report Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 fw-semibold">{{ __('Report ID:') }}</div>
                            <div class="col-md-9">#{{ $report->id }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-semibold">{{ __('Status:') }}</div>
                            <div class="col-md-9">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'reviewed' => 'info',
                                        'resolved' => 'success',
                                        'rejected' => 'danger',
                                    ];
                                    $color = $statusColors[$report->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ ucfirst($report->status) }}</span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-semibold">{{ __('Reason:') }}</div>
                            <div class="col-md-9">{{ $report->reason }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-semibold">{{ __('Comment:') }}</div>
                            <div class="col-md-9">
                                @if($report->comment)
                                    {{ $report->comment }}
                                @else
                                    <span class="text-muted">{{ __('No comment provided') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 fw-semibold">{{ __('Submitted Date:') }}</div>
                            <div class="col-md-9">{{ $report->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Shop Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Reported Shop') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($report->shop)
                            <div class="d-flex align-items-start">
                                @if($report->shop->logo)
                                    <img src="{{ $report->shop->logo }}" alt="{{ $report->shop->name }}" width="80" height="80" class="rounded me-3">
                                @endif
                                <div>
                                    <h5>{{ $report->shop->name }}</h5>
                                    <p class="mb-1"><strong>{{ __('Shop ID:') }}</strong> {{ $report->shop->id }}</p>
                                    <p class="mb-1"><strong>{{ __('Phone:') }}</strong> {{ $report->shop->phone_number ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>{{ __('Address:') }}</strong> {{ $report->shop->address ?? 'N/A' }}</p>
                                    <a href="{{ route('admin.shop.show', $report->shop->id) }}" class="btn btn-sm btn-primary mt-2">
                                        {{ __('View Shop Details') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">{{ __('Shop information not available') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Reported By') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($report->customer && $report->customer->user)
                            <div class="d-flex align-items-start">
                                @if($report->customer->user->image)
                                    <img src="{{ $report->customer->user->image }}" alt="{{ $report->customer->user->name }}" width="60" height="60" class="rounded-circle me-3">
                                @endif
                                <div>
                                    <h5>{{ $report->customer->user->name }}</h5>
                                    <p class="mb-1"><strong>{{ __('Email:') }}</strong> {{ $report->customer->user->email }}</p>
                                    <p class="mb-1"><strong>{{ __('Phone:') }}</strong> {{ $report->customer->user->phone ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>{{ __('Customer ID:') }}</strong> {{ $report->customer->id }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">{{ __('Customer information not available') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Uploaded Images -->
                @if($report->images && count($report->images) > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('Uploaded Images') }} ({{ count($report->images) }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($report->images as $image)
                                    <div class="col-md-6">
                                        <a href="{{ asset('storage/' . $image) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $image) }}" alt="Report Image" class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: cover;">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Actions Sidebar -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.shop-reports.update-status', $report->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">{{ __('Update Status') }}</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="pending" {{ $report->status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                    <option value="reviewed" {{ $report->status == 'reviewed' ? 'selected' : '' }}>{{ __('Reviewed') }}</option>
                                    <option value="resolved" {{ $report->status == 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                                    <option value="rejected" {{ $report->status == 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fa fa-save me-2"></i>{{ __('Update Status') }}
                            </button>
                        </form>

                        <hr>

                        <form action="{{ route('admin.shop-reports.destroy', $report->id) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this report?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fa fa-trash me-2"></i>{{ __('Delete Report') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Report Statistics (Optional) -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Quick Stats') }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>{{ __('Total Reports for this Shop:') }}</strong><br>
                            {{ \App\Models\ShopReport::where('shop_id', $report->shop_id)->count() }}
                        </p>
                        <p class="mb-0">
                            <strong>{{ __('Reports by this Customer:') }}</strong><br>
                            {{ \App\Models\ShopReport::where('customer_id', $report->customer_id)->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
