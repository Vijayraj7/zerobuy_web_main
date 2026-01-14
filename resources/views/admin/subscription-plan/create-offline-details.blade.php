@extends('layouts.app')

@section('header-title', __('Add Offline Payment Details'))

@section('content')


<div class="page-title mb-3">
    <div class="d-flex gap-2 align-items-center">
        {{ __('Offline Payment Details') }}
    </div>
</div>
<!-- <form action="#" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label"> {{ __('Account Name') }} </label>
                    <input type="text" class="form-control" name="name" placeholder="Enter Account Name"> 
                </div> 
                 <div class="col-md-6">
                    <label class="form-label"> {{ __('Account Number') }} </label>
                    <input type="text" class="form-control" name="name" placeholder="Enter Account Name"> 
                </div> 
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 justify-content-end align-items-center my-3">
        <button type="submit" class="btn btn-lg btn-primary rounded py-2 px-5">
            {{ __('Submit') }}
        </button>
    </div>
</form>  -->
<form id="offlineForm">
    @csrf
    <input type="hidden" name="id" value="{{ $offline->id ?? '' }}">

    <div class="card">
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Account Name</label>
                    <input type="text" name="account_name" placeholder="Enter Account Name" class="form-control" value="{{ $offline->account_name ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Account Number</label>
                    <input type="number" name="account_number" placeholder="Enter Account Number" class="form-control" value="{{ $offline->account_number ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Account Type</label>
                    <input type="text" name="account_type" placeholder="Enter Account Type" class="form-control" value="{{ $offline->account_type ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" name="ifsc_code" placeholder="Enter IFSC Code" class="form-control" value="{{ $offline->ifsc_code ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Branch Name</label>
                    <input type="text" name="branch_name" placeholder="Enter Branch Name" class="form-control" value="{{ $offline->branch_name ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">UPI Number</label>
                    <input type="text" name="upi_number" placeholder="Enter UPI Number" class="form-control" value="{{ $offline->upi_number ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-success px-5 submitbtn">
            {{ $offline ? 'Update' : 'Save' }}
        </button>
    </div>
</form>

@endsection


@push('scripts') 

<script>
$('#offlineForm').on('submit', function (e) {
    e.preventDefault();

    let form = $(this);
    let hasData = "{{ $offline ? 1 : 0 }}";

    let confirmText = hasData
        ? 'Do you want to change offline payment details?'
        : 'Do you want to save offline payment details?';

    Swal.fire({
        title: 'Are you sure?',
        text: confirmText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('admin.subscription-plan.offline.store') }}",
                method: "POST",
                data: form.serialize(),
                success: function (res) {
                    Toast.fire({
                        icon: 'success',
                        title: res.message
                    });
                    setTimeout(() => location.reload(), 1200); 
                    // Swal.fire('Success', res.message, 'success')
                    //     .then(() => location.reload());
                },
                error: function (data) {
                    // Swal.fire('Error', 'Something went wrong', 'error');
                    $('#validation-errors').html('');
                    $.each(data.responseJSON.errors, function(field_name, error) {
                        $(document).find('[name=' + field_name + ']').after('<span class="text-strong textdanger" style="color:red;">' + error + '</span>')
                    });
                    $('.submitbtn').prop('disabled', false).text('Submit');
                }
                
            });
        }
    });
});
</script>
@endpush
