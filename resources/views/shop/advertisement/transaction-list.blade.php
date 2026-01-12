@extends('layouts.app')

@section('header-title','Advertisement Transactions')

@section('content') 

<div class="d-flex align-items-center justify-content-between px-3">
    <h4>{{ __('All Transactions') }}</h4> 
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $txn)
                        <tr>
                            <td>{{ $txn->created_at->format('d-m-Y') }}</td>
                            <td>{{ $txn->transaction_id }}</td>
                            <td>₹{{ $txn->amount }}</td>
                            <td>{{ ucfirst($txn->type) }}</td>
                            <td>{{ $txn->purpose }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
