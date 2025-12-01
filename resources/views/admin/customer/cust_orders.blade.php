@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
    <h4>{{ __('All Orders') }}</h4> 
</div>

<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body">
            <div class="table-responsive"> 
                <table id="customerOrderTable" class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Order Date') }}</th>
                            <th>{{ __('Order ID') }}</th>
                            <th>{{ __('Store ID') }}</th>
                            <th>{{ __('Store Name') }}</th>
                            <th>{{ __('Customer Name') }}</th>
                            <th>{{ __('Mobile No') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Total Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
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
    
</script>

@endpush
