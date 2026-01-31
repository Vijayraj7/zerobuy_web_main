@extends('layouts.app')

@section('header-title', __('Analytics Dashboard'))

@push('styles')
<style>
    * {
        font-family: 'Segoe UI', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        color: #2d3748;
    }

    .analytics-card {
        border-radius: 20px;
        border: none;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .analytics-card:hover {
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        transform: translateY(-8px);
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 28px;
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.8) 50%, rgba(255,255,255,0.4) 100%);
        animation: shimmer 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.4; }
        50% { transform: scale(1.15) rotate(180deg); opacity: 0.7; }
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .stat-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 60px rgba(102, 126, 234, 0.35);
    }

    .stat-card.revenue { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    }
    .stat-card.orders { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        box-shadow: 0 10px 40px rgba(240, 147, 251, 0.3);
    }
    .stat-card.customers { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        box-shadow: 0 10px 40px rgba(79, 172, 254, 0.3);
    }
    .stat-card.products { 
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        box-shadow: 0 10px 40px rgba(67, 233, 123, 0.3);
    }
    .stat-card.shops { 
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        box-shadow: 0 10px 40px rgba(250, 112, 154, 0.3);
    }
    .stat-card.pending { 
        background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
        color: white;
        box-shadow: 0 10px 40px rgba(255, 107, 107, 0.3);
    }

    .stat-value {
        font-size: 2.8rem;
        font-weight: 800;
        margin: 12px 0;
        animation: countUp 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        letter-spacing: -1px;
    }

    @keyframes countUp {
        from { 
            opacity: 0; 
            transform: translateY(30px) scale(0.9); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
        }
    }

    .stat-label {
        font-size: 0.85rem;
        opacity: 0.95;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        position: absolute;
        right: 24px;
        top: 24px;
        font-size: 3.5rem;
        opacity: 0.25;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .chart-container {
        position: relative;
        height: 350px;
        padding: 24px;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px;
        border-bottom: 2px solid transparent;
        background: linear-gradient(to bottom, rgba(102, 126, 234, 0.03), transparent);
    }

    .chart-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1a202c;
        letter-spacing: -0.5px;
        position: relative;
        padding-left: 16px;
    }

    .chart-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
    }

    .period-selector {
        display: flex;
        gap: 10px;
        background: rgba(255, 255, 255, 0.95);
        padding: 8px;
        border-radius: 60px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.5);
    }

    .period-btn {
        padding: 12px 28px;
        border: none;
        background: transparent;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        font-size: 0.875rem;
        font-weight: 700;
        color: #64748b;
        position: relative;
        overflow: hidden;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .period-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform: translate(-50%, -50%);
        z-index: 0;
    }

    .period-btn span {
        position: relative;
        z-index: 1;
    }

    .period-btn:hover {
        color: #667eea;
        transform: translateY(-3px) scale(1.05);
        text-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .period-btn:hover::before {
        width: 200%;
        height: 200%;
        opacity: 0.15;
    }

    .period-btn:active {
        transform: translateY(-1px) scale(1.02);
    }

    .period-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.5), 
                    0 4px 12px rgba(102, 126, 234, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
        transform: translateY(-3px) scale(1.05);
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .period-btn.active::before {
        width: 100%;
        height: 100%;
        opacity: 1;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
    }

    .period-btn.active::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shine 3s infinite;
    }

    @keyframes shine {
        0% { left: -100%; }
        50%, 100% { left: 100%; }
    }

    .metric-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 700;
        margin-top: 12px;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideIn {
        from { 
            opacity: 0; 
            transform: translateX(-20px);
        }
        to { 
            opacity: 1; 
            transform: translateX(0);
        }
    }

    .metric-badge.positive {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.4);
    }

    .metric-badge.negative {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.4);
    }

    .top-items-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .top-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .top-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
        transition: width 0.3s ease;
    }

    .top-item:hover {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), transparent);
        transform: translateX(4px);
    }

    .top-item:hover::before {
        width: 100%;
    }

    .top-item:last-child {
        border-bottom: none;
    }

    .item-rank {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.95rem;
        margin-right: 16px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }

    .top-item:hover .item-rank {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 4px;
        font-size: 1rem;
        letter-spacing: -0.3px;
    }

    .item-meta {
        font-size: 0.875rem;
        color: #718096;
        font-weight: 500;
    }

    .item-value {
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 1.25rem;
        letter-spacing: -0.5px;
    }

    .loading-skeleton {
        animation: skeleton-loading 1.5s linear infinite alternate;
        border-radius: 8px;
    }

    @keyframes skeleton-loading {
        0% { background-color: #f0f0f0; }
        100% { background-color: #e0e0e0; }
    }

    .fade-in {
        animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .growth-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .growth-indicator.up { 
        color: #22c55e;
        text-shadow: 0 1px 3px rgba(34, 197, 94, 0.3);
    }
    .growth-indicator.down { 
        color: #ef4444;
        text-shadow: 0 1px 3px rgba(239, 68, 68, 0.3);
    }

    /* Enhanced Badge Styles */
    .badge {
        padding: 8px 18px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.875rem;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .badge.bg-success {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    }

    .badge.bg-warning {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
    }

    .badge.bg-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    }

    .badge.bg-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }

    /* Page Title Styling */
    h4 {
        font-weight: 800;
        background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -1px;
        font-size: 2rem;
    }

    .text-muted {
        color: #64748b !important;
        font-weight: 500;
        font-size: 0.95rem;
    }

    /* Card Body Enhancements */
    .card-body h6 {
        font-weight: 700;
        color: #1a202c;
        font-size: 1.1rem;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid transparent;
        background: linear-gradient(to right, rgba(102, 126, 234, 0.1), transparent);
        padding-left: 12px;
        border-left: 4px solid #667eea;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    /* Enhanced Button Styles */
    .btn {
        padding: 12px 32px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: none;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn:hover {
        transform: translateY(-3px) scale(1.03);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
    }

    .btn:active {
        transform: translateY(-1px) scale(1.01);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-primary:hover {
        box-shadow: 0 10px 35px rgba(102, 126, 234, 0.5);
    }

    .btn-success {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    }

    .btn-success:hover {
        box-shadow: 0 10px 35px rgba(34, 197, 94, 0.5);
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <h4 class="mb-1">{{ __('Analytics Dashboard') }}</h4>
            <p class="text-muted mb-0">{{ __('Comprehensive business insights and performance metrics') }}</p>
        </div>
        <div class="period-selector">
            <button class="period-btn {{ $period == '7' ? 'active' : '' }}" onclick="changePeriod(7)"><span>7 Days</span></button>
            <button class="period-btn {{ $period == '30' ? 'active' : '' }}" onclick="changePeriod(30)"><span>30 Days</span></button>
            <button class="period-btn {{ $period == '90' ? 'active' : '' }}" onclick="changePeriod(90)"><span>90 Days</span></button>
            <button class="period-btn {{ $period == '365' ? 'active' : '' }}" onclick="changePeriod(365)"><span>1 Year</span></button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6 fade-in" style="animation-delay: 0.1s">
            <div class="stat-card revenue">
                <i class="fas fa-dollar-sign stat-icon"></i>
                <div class="stat-label">{{ __('Total Revenue') }}</div>
                <div class="stat-value">₹{{ number_format($stats['total_revenue'], 0) }}</div>
                @if($revenueGrowth != 0)
                    <div class="metric-badge {{ $revenueGrowth > 0 ? 'positive' : 'negative' }}">
                        <i class="fas fa-arrow-{{ $revenueGrowth > 0 ? 'up' : 'down' }}"></i>
                        {{ abs(round($revenueGrowth, 1)) }}% vs last month
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 fade-in" style="animation-delay: 0.2s">
            <div class="stat-card orders">
                <i class="fas fa-shopping-cart stat-icon"></i>
                <div class="stat-label">{{ __('Total Orders') }}</div>
                <div class="stat-value">{{ number_format($stats['total_orders']) }}</div>
                <div class="metric-badge positive">
                    <i class="fas fa-check-circle"></i>
                    {{ $stats['completed_orders'] }} Completed
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 fade-in" style="animation-delay: 0.3s">
            <div class="stat-card customers">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-label">{{ __('New Customers') }}</div>
                <div class="stat-value">{{ number_format($stats['total_customers']) }}</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 fade-in" style="animation-delay: 0.4s">
            <div class="stat-card products">
                <i class="fas fa-box stat-icon"></i>
                <div class="stat-label">{{ __('Products Added') }}</div>
                <div class="stat-value">{{ number_format($stats['total_products']) }}</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 fade-in" style="animation-delay: 0.5s">
            <div class="stat-card shops">
                <i class="fas fa-store stat-icon"></i>
                <div class="stat-label">{{ __('Active Shops') }}</div>
                <div class="stat-value">{{ number_format($shopMetrics['active_shops']) }}</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 fade-in" style="animation-delay: 0.6s">
            <div class="stat-card pending">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-label">{{ __('Pending Orders') }}</div>
                <div class="stat-value">{{ number_format($stats['pending_orders']) }}</div>
            </div>
        </div>
    </div>

    <!-- Revenue & Orders Trends -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8 fade-in" style="animation-delay: 0.7s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Revenue Trend') }}</h5>
                    <div>
                        <span class="badge bg-success">₹{{ number_format($avgOrderValue, 0) }} Avg Order Value</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 fade-in" style="animation-delay: 0.8s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Order Status') }}</h5>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Growth & Category Performance -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6 fade-in" style="animation-delay: 0.9s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Customer Growth') }}</h5>
                </div>
                <div class="chart-container">
                    <canvas id="customerGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 fade-in" style="animation-delay: 1s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Category Performance') }}</h5>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Hourly Sales Pattern -->
    <div class="row g-4 mb-4">
        <div class="col-12 fade-in" style="animation-delay: 1.1s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Hourly Sales Pattern (This Week)') }}</h5>
                </div>
                <div class="chart-container">
                    <canvas id="hourlySalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products & Top Shops -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6 fade-in" style="animation-delay: 1.2s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Top 10 Products') }}</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="top-items-list">
                        @foreach($topProducts as $index => $product)
                            <li class="top-item">
                                <div class="d-flex align-items-center flex-1">
                                    <div class="item-rank">{{ $index + 1 }}</div>
                                    <div class="item-info">
                                        <div class="item-name">{{ Str::limit($product->name, 40) }}</div>
                                        <div class="item-meta">{{ $product->shop?->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="item-value">{{ $product->order_items_count }} {{ __('sales') }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6 fade-in" style="animation-delay: 1.3s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Top 10 Shops by Revenue') }}</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="top-items-list">
                        @foreach($topShops as $index => $shop)
                            <li class="top-item">
                                <div class="d-flex align-items-center flex-1">
                                    <div class="item-rank">{{ $index + 1 }}</div>
                                    <div class="item-info">
                                        <div class="item-name">{{ $shop->name }}</div>
                                        <div class="item-meta">{{ $shop->products_count ?? 0 }} Products</div>
                                    </div>
                                </div>
                                <div class="item-value">₹{{ number_format($shop->revenue ?? 0, 0) }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4 fade-in" style="animation-delay: 1.4s">
            <div class="card analytics-card">
                <div class="card-body">
                    <h6 class="mb-3">{{ __('Shop Metrics') }}</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ __('Active Shops') }}</span>
                        <strong>{{ $shopMetrics['active_shops'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ __('Verified Shops') }}</span>
                        <strong>{{ $shopMetrics['verified_shops'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Shop Reports') }}</span>
                        <strong>{{ $shopMetrics['total_reports'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 fade-in" style="animation-delay: 1.5s">
            <div class="card analytics-card">
                <div class="card-body">
                    <h6 class="mb-3">{{ __('Review Statistics') }}</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ __('Total Reviews') }}</span>
                        <strong>{{ $reviewStats['total_reviews'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Average Rating') }}</span>
                        <strong>
                            <i class="fas fa-star text-warning"></i>
                            {{ number_format($reviewStats['average_rating'], 1) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 fade-in" style="animation-delay: 1.6s">
            <div class="card analytics-card">
                <div class="chart-header">
                    <h5 class="chart-title">{{ __('Payment Methods') }}</h5>
                </div>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Chart.js Configuration
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';

    // Revenue Trend Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($revenueTrend->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!},
            datasets: [{
                label: 'Revenue (₹)',
                data: {!! json_encode($revenueTrend->pluck('revenue')) !!},
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 13 },
                    displayColors: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Order Status Chart
    const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($ordersByStatus->pluck('order_status')->map(fn($s) => ucfirst($s?->value ?? $s))) !!},
            datasets: [{
                data: {!! json_encode($ordersByStatus->pluck('count')) !!},
                backgroundColor: [
                    '#667eea',
                    '#f093fb',
                    '#4facfe',
                    '#43e97b',
                    '#fa709a',
                    '#ffecd2'
                ],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
            }
        }
    });

    // Customer Growth Chart
    const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
    new Chart(customerGrowthCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($customerGrowth->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!},
            datasets: [{
                label: 'New Customers',
                data: {!! json_encode($customerGrowth->pluck('count')) !!},
                backgroundColor: 'rgba(79, 172, 254, 0.8)',
                borderColor: '#4facfe',
                borderWidth: 2,
                borderRadius: 6,
                barThickness: 'flex',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeOutBounce'
            }
        }
    });

    // Category Performance Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($categoryPerformance->pluck('name')) !!},
            datasets: [{
                label: 'Sales',
                data: {!! json_encode($categoryPerformance->pluck('sales_count')) !!},
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(240, 147, 251, 0.8)',
                    'rgba(79, 172, 254, 0.8)',
                    'rgba(67, 233, 123, 0.8)',
                    'rgba(250, 112, 154, 0.8)',
                    'rgba(255, 236, 210, 0.8)',
                    'rgba(118, 75, 162, 0.8)',
                    'rgba(245, 87, 108, 0.8)',
                ],
                borderWidth: 0,
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                y: {
                    grid: { display: false }
                }
            },
            animation: {
                duration: 1800,
                easing: 'easeInOutCubic'
            }
        }
    });

    // Hourly Sales Chart
    const hourlySalesCtx = document.getElementById('hourlySalesChart').getContext('2d');
    new Chart(hourlySalesCtx, {
        type: 'line',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + ':00'),
            datasets: [{
                label: 'Orders',
                data: Array.from({length: 24}, (_, i) => {
                    const hourData = {!! json_encode($hourlySales) !!}.find(h => h.hour == i);
                    return hourData ? hourData.count : 0;
                }),
                borderColor: '#43e97b',
                backgroundColor: 'rgba(67, 233, 123, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#43e97b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Payment Methods Chart
    const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    new Chart(paymentMethodsCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($paymentMethods->pluck('payment_method')->map(fn($m) => ucfirst($m?->value ?? $m ?? 'N/A'))) !!},
            datasets: [{
                data: {!! json_encode($paymentMethods->pluck('count')) !!},
                backgroundColor: [
                    '#667eea',
                    '#f093fb',
                    '#4facfe',
                    '#43e97b',
                    '#fa709a'
                ],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
            }
        }
    });

    // Period Change Function
    function changePeriod(days) {
        window.location.href = '{{ route("admin.analytics.index") }}?period=' + days;
    }
</script>
@endpush
