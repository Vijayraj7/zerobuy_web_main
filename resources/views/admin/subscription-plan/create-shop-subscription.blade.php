@extends('layouts.app')

@section('header-title', __('Manually Add Shop Subscriptions'))

@section('content')
<div class="page-title mb-3">
    <div class="d-flex gap-2 align-items-center">
        {{ __('Create New Plan') }}
    </div>
</div>
<form action="#" method="POST">
    @csrf
    <div class="card">
        <div class="card-body"> 
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Select Store') }}</label>
                    <select name="store_id" id="store_id" class="form-control select2">
                        <option value="">--Select Store--</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Select Plan') }}</label>
                    <select name="plan_id" id="plan_id" class="form-control select2">
                        <option value="">--Select Plan--</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 justify-content-end align-items-center my-3">
        <button type="submit" class="btn btn-lg btn-primary rounded py-2 px-5">
            {{ __('Submit') }}
        </button>
    </div>
</form> 
@endsection



@push('scripts')

@endpush
