@extends('layouts.app')

@section('header-title', __('Shop Reports'))

@section('content')
    <div>
        <h4>{{ __('Shop Reports') }}</h4>
    </div>

    <div class="container-fluid mt-3">

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.shop-reports.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">{{ __('Status') }}</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                            <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>{{ __('Reviewed') }}</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="search" class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="{{ __('Search by shop or customer name...') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-responsive-lg border-left-right">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Shop') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th style="min-width: 150px">{{ __('Reason') }}</th>
                                <th style="min-width: 200px">{{ __('Comment') }}</th>
                                <th>{{ __('Images') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($report->shop?->logo)
                                                <img src="{{ $report->shop->logo }}" alt="" width="40" height="40" class="rounded me-2">
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $report->shop?->name ?? 'N/A' }}</div>
                                                <small class="text-muted">ID: {{ $report->shop_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div>{{ $report->customer?->user?->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $report->customer?->user?->email ?? '' }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $report->reason }}</td>
                                    <td>
                                        @if($report->comment)
                                            {{ Str::limit($report->comment, 50, '...') }}
                                        @else
                                            <span class="text-muted">{{ __('No comment') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($report->images && count($report->images) > 0)
                                            <span class="badge bg-info">{{ count($report->images) }} {{ __('images') }}</span>
                                        @else
                                            <span class="text-muted">{{ __('No images') }}</span>
                                        @endif
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <div>{{ $report->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $report->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="{{ route('admin.shop-reports.show', $report->id) }}" class="btn btn-sm btn-primary" title="{{ __('View Details') }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <form action="{{ route('admin.shop-reports.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this report?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="text-center">
                                        {{ __('No reports found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

            </div>
        </div>

        <div class="my-3">
            {{ $reports->links() }}
        </div>

    </div>
@endsection
