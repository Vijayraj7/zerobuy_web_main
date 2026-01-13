@extends('layouts.app')

@section('header-title', __('Advertisement Settings'))

@section('content')
<div class="container-fluid mt-4">
    <div class="card col-md-6">
        <div class="card-header">
            <h3>{{ __('Advertisement Daily Budget') }}</h3>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.advrtsettings.update') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">
                        Daily Budget (₹)
                    </label>

                    <input type="number"
                           name="daily_budget"
                           class="form-control @error('daily_budget') is-invalid @enderror"
                           value="{{ old('daily_budget', $setting->daily_budget) }}"
                           min="1"
                           step="0.01"
                           required>

                    @error('daily_budget')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button class="btn btn-primary">
                    Save
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
