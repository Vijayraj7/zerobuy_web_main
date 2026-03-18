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
                        <th>Ad Transaction ID</th>
                        <th>Reference ID</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($message))
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                {{ $message }}
                            </td>
                        </tr>

                    @elseif($transactions->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No advertisement transactions found.
                            </td>
                        </tr>

                    @else
                        @foreach($transactions as $txn)
                            <tr>
                                <td>{{ $txn->created_at->format('d-m-Y | h:i A') }}</td>
                                <td>{{ $txn->ad_transaction_id ?? '-' }}</td>
                                <td>{{ $txn->transaction_id ?? '-' }}</td>
                                <td>₹{{ $txn->amount }}</td>
                                <td>{{ ucfirst($txn->type) }}</td>
                                <td>{{ $txn->purpose }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>

                <!-- <tbody>
                    @foreach($transactions as $txn)
                        <tr>
                            <td>{{ $txn->created_at->format('d-m-Y') }}</td>
                            <td>{{ $txn->transaction_id }}</td>
                            <td>₹{{ $txn->amount }}</td>
                            <td>{{ ucfirst($txn->type) }}</td>
                            <td>{{ $txn->purpose }}</td>
                        </tr>
                    @endforeach
                </tbody> -->
            </table>
        </div>
    </div>
</div>
@endsection
