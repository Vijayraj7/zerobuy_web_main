@extends('layouts.app')

@section('header-title', __('Analytics'))
@section('header-subtitle', __('Seller performance overview'))

@push('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --an-bg: transparent;
        --an-ink: #1f2937;
        --an-muted: #6b7280;
        --an-card: rgba(255, 255, 255, 0.92);
        --an-accent: #0f766e;
        --an-accent-2: #f97316;
        --an-accent-3: #2563eb;
        --an-border: rgba(15, 23, 42, 0.08);
    }

    .analytics-shell {
        background: transparent;
        border-radius: 16px;
        padding: 18px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
        animation: panel-in 0.7s ease-out;
        font-family: 'Space Grotesk', sans-serif;
    }

    .analytics-header {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .analytics-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--an-ink);
        letter-spacing: -0.02em;
        margin-bottom: 4px;
    }

    .analytics-subtitle {
        color: var(--an-muted);
        font-size: 0.9rem;
    }

    .analytics-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .analytics-toolbar .btn {
        border-radius: 999px;
        padding: 8px 16px;
        font-weight: 600;
    }

    .analytics-card {
        background: var(--an-card);
        border-radius: 16px;
        border: 1px solid var(--an-border);
        padding: 16px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(16px);
        transition: transform 0.35s ease, box-shadow 0.35s ease;
        animation: fade-up 0.7s ease forwards;
        opacity: 0;
    }

    .analytics-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.14);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .stat-card {
        padding: 16px;
        border-radius: 16px;
        color: white;
        position: relative;
        overflow: hidden;
        min-height: 116px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        animation: float-in 0.8s ease forwards;
        opacity: 0;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255,255,255,0.35), transparent 55%);
        opacity: 0.8;
    }

    .stat-card h6 {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 6px;
        position: relative;
        z-index: 1;
    }

    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .stat-card .stat-meta {
        font-size: 0.82rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .stat-revenue { background: linear-gradient(135deg, #0f766e, #14b8a6); }
    .stat-orders { background: linear-gradient(135deg, #1d4ed8, #60a5fa); }
    .stat-aov { background: linear-gradient(135deg, #f97316, #facc15); }
    .stat-returns { background: linear-gradient(135deg, #7c3aed, #c084fc); }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .chart-box {
        height: 220px;
        max-height: 500px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .chart-box canvas {
        height: 160px !important;
        max-height: 160px;
        width: 100% !important;
    }

    .chart-title {
        font-weight: 600;
        color: var(--an-ink);
        margin-bottom: 12px;
        font-size: 0.95rem;
        letter-spacing: -0.01em;
    }

    .table thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--an-muted);
        border-bottom: 1px solid var(--an-border);
        background: transparent;
    }

    .top-product {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .top-product img {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid rgba(148, 163, 184, 0.4);
    }

    .fade-delay-1 { animation-delay: 0.05s; }
    .fade-delay-2 { animation-delay: 0.12s; }
    .fade-delay-3 { animation-delay: 0.2s; }
    .fade-delay-4 { animation-delay: 0.28s; }

    @keyframes panel-in {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fade-up {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes float-in {
        from { opacity: 0; transform: translateY(18px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>
@endpush

@section('content')
    <div class="analytics-shell">
        <div class="analytics-header">
            <div>
                <div class="analytics-title">{{ __('Analytics Overview') }}</div>
                <div class="analytics-subtitle">{{ $rangeLabel }} ({{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }})</div>
            </div>
            <form class="analytics-toolbar" method="get" action="{{ route('shop.analytics.index') }}">
                <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                <a href="{{ route('shop.analytics.index', ['period' => '7d']) }}" class="btn btn-outline-secondary">7D</a>
                <a href="{{ route('shop.analytics.index', ['period' => '30d']) }}" class="btn btn-outline-secondary">30D</a>
                <a href="{{ route('shop.analytics.index', ['period' => 'month']) }}" class="btn btn-outline-secondary">{{ __('Month') }}</a>
            </form>
        </div>

        <div class="stat-grid">
            <div class="stat-card stat-revenue fade-delay-1">
                <h6>{{ __('Revenue') }}</h6>
                <div class="stat-value">{{ showCurrency(number_format($stats['revenue'], 2, '.', '')) }}</div>
                <div class="stat-meta">{{ __('Delivered orders only') }}</div>
            </div>
            <div class="stat-card stat-orders fade-delay-2">
                <h6>{{ __('Orders') }}</h6>
                <div class="stat-value">{{ $stats['orders'] }}</div>
                <div class="stat-meta">{{ __('Total orders in range') }}</div>
            </div>
            <div class="stat-card stat-aov fade-delay-3">
                <h6>{{ __('Average Order Value') }}</h6>
                <div class="stat-value">{{ showCurrency(number_format($stats['aov'], 2, '.', '')) }}</div>
                <div class="stat-meta">{{ __('Based on delivered') }}</div>
            </div>
            <div class="stat-card stat-returns fade-delay-4">
                <h6>{{ __('Returns') }}</h6>
                <div class="stat-value">{{ showCurrency(number_format($stats['returns_amount'], 2, '.', '')) }}</div>
                <div class="stat-meta">{{ $stats['returns_count'] }} {{ __('returns') }}</div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="analytics-card chart-box fade-delay-1">
                <div class="chart-title">{{ __('Sales vs Returns') }}</div>
                <canvas id="salesChart" height="180"></canvas>
            </div>
            <div class="analytics-card chart-box fade-delay-2">
                <div class="chart-title">{{ __('Order Status Breakdown') }}</div>
                <canvas id="statusChart" height="180"></canvas>
            </div>
        </div>

        <div class="analytics-card fade-delay-3">
            <div class="chart-title">{{ __('Top Products') }}</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Qty') }}</th>
                            <th>{{ __('Revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topProducts as $product)
                            <tr>
                                <td>
                                    <div class="top-product">
                                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                                        <span>{{ $product['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $product['qty'] }}</td>
                                <td>{{ showCurrency(number_format($product['amount'], 2, '.', '')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">{{ __('No data found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="analytics-card fade-delay-4" style="margin-top: 14px;">
            <div class="chart-title">{{ __('Top Rated Products') }}</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Rating') }}</th>
                            <th>{{ __('Reviews') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topRated as $product)
                            <tr>
                                <td>
                                    <div class="top-product">
                                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                                        <span>{{ $product['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ number_format($product['rating'], 2, '.', '') }}</td>
                                <td>{{ $product['reviews'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">{{ __('No data found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="analytics-card fade-delay-4" style="margin-top: 14px;">
            <div class="chart-title">{{ __('Top Customers') }}</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Mobile') }}</th>
                            <th>{{ __('Orders') }}</th>
                            <th>{{ __('Spend') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topCustomers as $customer)
                            <tr>
                                <td>
                                    <div class="top-product">
                                        <img src="{{ $customer['image'] }}" alt="{{ $customer['name'] }}">
                                        <span>{{ $customer['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $customer['mobile'] }}</td>
                                <td>{{ $customer['orders'] }}</td>
                                <td>{{ showCurrency(number_format($customer['amount'], 2, '.', '')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('No data found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const salesLabels = @json($chart['labels']);
    const salesSeries = @json($chart['sales']);
    const returnsSeries = @json($chart['returns']);
    const statusLabels = @json($statusBreakdown->pluck('label'));
    const statusCounts = @json($statusBreakdown->pluck('count'));

    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [
                    {
                        label: 'Sales',
                        data: salesSeries,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.15)',
                        fill: true,
                        tension: 0.35,
                    },
                    {
                        label: 'Returns',
                        data: returnsSeries,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.12)',
                        fill: true,
                        tension: 0.35,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 10, boxHeight: 10 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        grid: { color: 'rgba(148, 163, 184, 0.2)' }
                    }
                }
            }
        });
    }

    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [
                    {
                        data: statusCounts,
                        backgroundColor: ['#0f766e', '#1d4ed8', '#f97316', '#7c3aed', '#ef4444', '#06b6d4', '#f59e0b'],
                        borderWidth: 0,
                    }
                ]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '62%'
            }
        });
    }
</script>
@endpush
