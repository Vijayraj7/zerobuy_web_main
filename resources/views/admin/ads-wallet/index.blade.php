@extends('layouts.app')

@section('header-title', 'Ads Wallet')

@section('content')
<div class="app-page-title">
    <div class="page-title-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h3>Ads Wallet</h3>
            <p class="text-muted">Wallet Lists</p>
        </div>
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body">

            <h4 class="mb-3">All Ads Wallet Lists</h4>

            <!-- Filters -->
            <div class="d-flex flex-wrap align-items-center gap-3 mt-3 px-2">
                <!-- Date Filters -->
                <div class="d-flex align-items-center gap-2">
                    <label>From:</label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label>To:</label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                <!-- Reload Button -->
                <button id="reloadBtn" class="btn btn-secondary">
                    <i class="fa fa-rotate-right"></i>
                </button>
                <!-- Export Buttons -->
                <div id="exportButtons"></div>
            </div>

            <div class="table-responsive mt-3">
                <table id="walletTable" class="table table-bordered datatableCustomCSS">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Store ID</th>
                            <th>Shop Thumbnail</th>
                            <th>Store Name</th>
                            <th>State</th>
                            <th>Total Products</th>
                            <th>Total Orders</th>
                            <th>Subscription</th>
                            <th>Wallet Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Recharge Modal -->
<div class="modal fade" id="rechargeModal">
    <div class="modal-dialog">
        <form id="rechargeForm">
            @csrf
            <input type="hidden" name="wallet_id" id="wallet_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5>Add Wallet Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label>Current Balance</label>
                        <input type="text" id="current_balance" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label>Transaction Type</label>
                        <select name="type" class="form-control" required>
                            <option value="credit">Add (Credit)</option>
                            <option value="debit">Minus (Debit)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" placeholder="Enter amount" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Add Amount</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {

    let table = $('#walletTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.adswallet.index') }}",
            data: function (d) {
                d.start_date = $('#startDate').val();
                d.end_date   = $('#endDate').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'store_id', orderable:false },
            { data: 'shop_thumbnail', orderable:false, searchable:false },
            { data: 'store_name', orderable:false },
            { data: 'state', orderable:false },
            { data: 'total_products', orderable:false },
            { data: 'total_orders', orderable:false },
            { data: 'subscription', orderable:false },
            { data: 'wallet_amount', orderable:true },
            { data: 'status', orderable:false },
            { data: 'actions', orderable:false, searchable:false }
        ]
    });

    $('#startDate,#endDate').change(() => table.ajax.reload());

    $('#reloadBtn').click(function () {
        $('#startDate,#endDate').val('');
        table.ajax.reload();
    });

  
    $(document).on('click', '.rechargeBtn', function () { 
        $('#rechargeForm')[0].reset();

        let walletId = $(this).data('id');
        let balance  = $(this).data('balance');

        $('#wallet_id').val(walletId);
        $('#current_balance').val('₹ ' + balance);

        $('#rechargeModal').modal('show');
    });                                                         

    $('#rechargeForm').submit(function(e){
        e.preventDefault();

        $.post("{{ route('admin.wallet.recharge') }}", $(this).serialize())
            .done(function(res){
                $('#rechargeModal').modal('hide');
                $('#walletTable').DataTable().ajax.reload(null, false);
            })
            .fail(function(xhr){
                alert(xhr.responseJSON.message);
            });
    });



});
</script>
@endpush
