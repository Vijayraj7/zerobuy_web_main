@extends('layouts.app')

@section('header-title', __('Subscription History'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper">
        <div>
            <h3>Subscription History</h3>
            <p class="text-muted">
                Store: <strong>{{ $shop->name }}</strong>
                (STR0{{ $shop->shop_code }})
            </p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>#</th>
                <th>Activation Date</th>
                <th>Subscription Plan</th>
                <th>Validity</th>
                <th>Remaining Days</th>
                <th>Expiry Date</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($subscriptions as $i => $sub)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $sub['activation_date'] }}</td>
                    <td>{{ $sub['plan'] }}</td>
                    <td>{{ $sub['validity'] }}</td>
                    <td>{{ $sub['remaining_days'] }}</td>
                    <td>{{ $sub['expiry_date'] }}</td>
                    <td>{{ $sub['amount'] }}</td>
                    <td>{{ $sub['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No subscription history found</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <a href="{{ route('admin.subscription-plan.subscription.all-list') }}"
           class="btn btn-secondary mt-3">
            ← Back to Subscription Stores
        </a>
    </div>
</div>

@endsection
