@extends('layouts.app')
@section('header-title', __('Shop Followers'))
@section('content')

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">
            @include('admin.shop.header-nav')

            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">SL</th>
                            <th>Follow Date</th>
                            <th>Profile</th>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($followers as $key => $follower)
                            <tr>
                                <td class="text-center">
                                    {{ $followers->firstItem() + $key }}
                                </td>

                                <td>
                                    {{ $follower->created_at->format('d M Y') }}
                                </td>

                                <td>
                                    <img src="{{ $follower->customer->user->thumbnail ?? asset('default/profile.jpg') }}"
                                        alt="customer-profile" width="40" height="40" class="rounded-circle">
                                </td>

                                <td>
                                    CUST0{{ $follower->customer->id ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $follower->customer->user->name ?? 'N/A' }}
                                    {{ $follower->customer->user->last_name ?? '' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No followers found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $followers->withQueryString()->links() }}
            </div>

        </div>
    </div>
</div>

@endsection
