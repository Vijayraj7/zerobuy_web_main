@extends('layouts.app')

@section('header-title','Advertisement Transactions')

@push('css')
<style>
    .ad-transaction-summary-card {
        border: 0;
        border-radius: 10px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .ad-transaction-summary-card .card-body {
        padding: 0.85rem 1rem;
    }

    .ad-transaction-summary-card h6 {
        margin-bottom: 0.3rem;
        font-size: 0.6rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.9;
    }

    .ad-transaction-summary-card .summary-amount {
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .ad-transaction-summary-card .summary-note {
        margin-top: 0.35rem;
        font-size: 0.8rem;
        opacity: 0.92;
    }

    .ad-transaction-summary-card.recharge {
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff;
    }

    .ad-transaction-summary-card.ads-run {
        background: linear-gradient(135deg, #1d4ed8, #60a5fa);
        color: #fff;
    }

    .transaction-type-credit {
        color: #198754 !important;
        font-weight: 600;
    }

    .transaction-type-debit {
        color: #dc3545 !important;
        font-weight: 600;
    }
</style>
@endpush

@section('content') 

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 px-3">
    <h4 class="mb-0">{{ __('All Transactions') }}</h4>
    <div class="d-flex flex-wrap gap-3 justify-content-end">
        <div class="card ad-transaction-summary-card recharge" style="min-width: 210px;">
            <div class="card-body">
                <h6>{{ __('Total Recharged Amount') }}</h6>
                <div class="summary-amount">₹{{ number_format((float) ($totalRechargeAmount ?? 0), 2) }}</div>
            </div>
        </div>
        <div class="card ad-transaction-summary-card ads-run" style="min-width: 210px;">
            <div class="card-body">
                <h6>{{ __('Total Ads Runned Amount') }}</h6>
                <div class="summary-amount">₹{{ number_format((float) ($totalAdsRunAmount ?? 0), 2) }}</div>
            </div>
        </div>
    </div>
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
                                <td>₹{{ number_format((float) $txn->amount, 2) }}</td>
                                <td class="{{ $txn->type === 'credit' ? 'transaction-type-credit' : ($txn->type === 'debit' ? 'transaction-type-debit' : '') }}">{{ ucfirst($txn->type) }}</td>
                                <td>{{ $txn->purpose }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
