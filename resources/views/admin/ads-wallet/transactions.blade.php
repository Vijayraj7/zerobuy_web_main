@extends('layouts.app')

@section('header-title', 'Wallet Transactions')

@section('content')
<div class="container-fluid mt-3">

    <div class="card">
        <div class="card-body">

            <h4 class="mb-2">
                Wallet Transactions —
                <span class="text-muted">
                    {{ optional($wallet->shop)->name ?? 'Store' }}
                </span>
            </h4>

            <div class="table-responsive mt-3">
                <table id="transactionTable" class="table table-bordered datatableCustomCSS">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Transaction ID</th>
                            <th>Purpose</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {

    $('#transactionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.wallet.transactions', $wallet->id) }}",
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'date', name:'created_at' },
            { data: 'transaction_id', name:'transaction_id' },
            { data: 'purpose', name:'purpose' },
            { data: 'amount', orderable:false },
            { data: 'status', orderable:false, searchable:false }
        ]
    });

});
</script>
@endpush
