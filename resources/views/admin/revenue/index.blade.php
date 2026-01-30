@extends('layouts.app')

@section('header-title', __('Revenue'))

@section('content')
<div class="container-fluid">

    {{-- DATE FILTER (CUSTOM MODE) --}}
    <form method="GET" class="row g-2 mb-3">
        <input type="hidden" name="mode" value="custom">

        <div class="col-md-3">
            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
        </div>

        <div class="col-md-3">
            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Search</button>
        </div>

        <div class="col-md-2">
            <a href="{{ route('admin.revenue.index') }}" class="btn btn-danger w-100">Reset</a>
        </div>
    </form>

    <div class="mb-4 d-flex align-items-center justify-content-between"> 
        <div>
            <a href="{{ route('admin.revenue.index', ['mode'=>'today']) }}"
            class="btn btn-sm {{ $mode=='today' ? 'btn-primary' : 'btn-outline-primary' }}">Today</a>

            <a href="{{ route('admin.revenue.index', ['mode'=>'month']) }}"
            class="btn btn-sm {{ $mode=='month' ? 'btn-primary' : 'btn-outline-primary' }}">Month</a>

            <a href="{{ route('admin.revenue.index', ['mode'=>'year']) }}"
            class="btn btn-sm {{ $mode=='year' ? 'btn-primary' : 'btn-outline-primary' }}">Year</a>

            <span class="btn btn-sm {{ $mode=='custom' ? 'btn-primary' : 'btn-outline-primary' }}">
                Custom
            </span>
        </div> 

        <div class="d-flex align-items-center gap-0">
            <div class="d-flex rounded-pill overflow-hidden shadow-sm">

                {{-- LEFT SHADE --}}
                <div class="px-3 py-2 bg-secondary text-white d-flex align-items-center">
                    <i class="fas fa-wallet me-2"></i>
                    <span>Wallet</span>
                </div>

                {{-- RIGHT SHADE --}}
                <div class="px-3 py-2 bg-primary text-white fw-bold">
                    {{showCurrency (number_format($walletBalance ?? 0, 2)) }}
                </div>

            </div>
        </div>

    </div>

    {{-- SUMMARY CARDS (UNCHANGED) --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="dashboard-box item-2">
                <h2 class="count">{{ $totalSubscriptionSales }}</h2>
                <h3 class="title">{{ __('Total Subscription Sales') }}</h3>
                <div class="icon">
                    <img src="{{ asset('assets/icons-admin/chart-trend-up.svg') }}" alt="icon" loading="lazy" />
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-box item-2">
                <h2 class="count">{{ showCurrency (number_format($totalSubscriptionAmount)) }}</h2>
                <h3 class="title">{{ __('Total Subscription Amount') }}</h3>
                <div class="icon">
                    <img src="{{ asset('assets/icons-admin/wallet.svg') }}" alt="icon" loading="lazy" />
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-box item-2">
                <h2 class="count">{{ $totalAds }}</h2>
                <h3 class="title">{{ __('Total Ads') }}</h3>
                <div class="icon">
                    <img src="{{ asset('assets/icons-admin/ads.svg') }}" alt="icon" loading="lazy" />
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-box item-2">
                <h2 class="count">{{ showCurrency (number_format($totalAdsAmount)) }}</h2>
                <h3 class="title">{{ __('Total Ads Amount') }}</h3>
                <div class="icon">
                    <img src="{{ asset('assets/icons-admin/wallet.svg') }}" alt="icon" loading="lazy" />
                </div>
            </div>
        </div>
        <!-- <div class="col-md-3 mt-3">
            <div class="dashboard-box item-2">
                <h2 class="count">{{ showCurrency (number_format($walletBalance)) }}</h2>
                <h3 class="title">{{ __('Wallet Balance') }}</h3>
                <div class="icon">
                    <img src="{{ asset('assets/icons-admin/wallet.svg') }}" alt="icon" loading="lazy" />
                </div>
            </div>
        </div>  -->
    </div>

    {{-- CHARTS --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Subscription Summary</div>
                <div class="card-body">
                    <canvas id="subscriptionChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Ads Summary</div>
                <div class="card-body">
                    <canvas id="adsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labels = @json($labels);

new Chart(document.getElementById('subscriptionChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Subscription Revenue',
            data: @json($subscriptionChart),
            backgroundColor: '#1E88E5'
        }]
    }
});

new Chart(document.getElementById('adsChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Ads Revenue',
            data: @json($adsChart),
            backgroundColor: '#00C853'
        }]
    }
});
</script>
@endpush