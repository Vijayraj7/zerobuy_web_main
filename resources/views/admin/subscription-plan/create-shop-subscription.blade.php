@extends('layouts.app')

@section('header-title', __('Manually Add Shop Subscriptions'))

@section('content')
<div class="page-title mb-3">
    <div class="d-flex gap-2 align-items-center">
        {{ __('Create New Plan') }}
    </div>
</div>
<form action="{{ route('admin.subscription-plan.shopmanual.store') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body"> 
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Select Store') }}</label>
                    <select name="store_id" id="store_id" class="form-control select2" required>
                        <option value="">--Select Store--</option>
                        @foreach($shops as $shop)
                            <option
                                value="{{ $shop->id }}"
                                data-active="{{ $shop->current_subscription_count > 0 ? 1 : 0 }}"
                                data-active-label="{{ $shop->current_subscription_count > 0 ? 'Active' : 'Not Active' }}"
                                @selected(old('store_id') == $shop->id)
                            >
                                {{ $shop->name }} ({{ $shop->current_subscription_count > 0 ? 'Active' : 'Not Active' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Select Plan') }}</label>
                    <select name="plan_id" id="plan_id" class="form-control select2" required>
                        <option value="">--Select Plan--</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                {{ $plan->name }} - {{ number_format($plan->price, 2) }}
                                @if($plan->duration)
                                    ({{ $plan->duration }} Days)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <div id="currentSubscriptionBox" class="alert alert-light border d-none mb-0"></div>
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
<script>
    (function () {
        const shopSelect = document.getElementById('store_id');
        const detailsBox = document.getElementById('currentSubscriptionBox');
        const endpointTemplate = "{{ route('admin.subscription-plan.shopmanual.create') }}"
            .replace('/create', '/current-subscription/__SHOP_ID__');

        if (!shopSelect || !detailsBox) {
            return;
        }

        function initStoreSelect2Status() {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return false;
            }

            const $shopSelect = window.jQuery(shopSelect);

            if ($shopSelect.data('shop-status-select2-initialized') === true) {
                return true;
            }

            if ($shopSelect.hasClass('select2-hidden-accessible')) {
                $shopSelect.select2('destroy');
            }

            const renderOption = function (state) {
                if (!state.id) {
                    return state.text;
                }

                const activeLabel = state.element?.dataset?.activeLabel || '';
                const isActive = state.element?.dataset?.active === '1';
                const colorClass = isActive ? 'text-success' : 'text-danger';

                const plainText = (state.text || '').replace(/\s*\((Active|Not Active)\)\s*$/i, '');

                return window.jQuery(
                    `<span class="d-flex justify-content-between align-items-center w-100">
                        <span>${plainText}</span>
                        <span class="${colorClass} fw-semibold ms-2">${activeLabel}</span>
                    </span>`
                );
            };

            $shopSelect.select2({
                width: '100%',
                templateResult: renderOption,
                templateSelection: renderOption,
                escapeMarkup: function (markup) {
                    return markup;
                }
            });

            $shopSelect.data('shop-status-select2-initialized', true);
            return true;
        }

        function initStoreSelect2StatusWithRetry(maxRetry = 12) {
            if (initStoreSelect2Status()) {
                return;
            }

            if (maxRetry <= 0) {
                return;
            }

            setTimeout(function () {
                initStoreSelect2StatusWithRetry(maxRetry - 1);
            }, 150);
        }

        function setInfo(message, type = 'light') {
            detailsBox.classList.remove('d-none', 'alert-light', 'alert-success', 'alert-warning', 'alert-danger');
            detailsBox.classList.add('alert-' + type);
            
            // Add explicit styling with !important to override Bootstrap
            if (type === 'danger') {
                detailsBox.setAttribute('style', 'background-color: #f8d7da !important; color: #721c24 !important; border-color: #f5c6cb !important;');
            } else if (type === 'success') {
                detailsBox.setAttribute('style', 'background-color: #d4edda !important; color: #155724 !important; border-color: #c3e6cb !important;');
            } else {
                detailsBox.removeAttribute('style');
            }
            
            detailsBox.innerHTML = message;
        }

        function hideInfo() {
            detailsBox.classList.add('d-none');
            detailsBox.innerHTML = '';
        }

        function formatValue(value, suffix = '') {
            if (value === null || value === undefined || value === '') {
                return '-';
            }
            return String(value) + suffix;
        }

        async function loadCurrentSubscription(shopId) {
            if (!shopId) {
                hideInfo();
                return;
            }

            setInfo('Loading current subscription...', 'light');

            try {
                const url = endpointTemplate.replace('__SHOP_ID__', shopId);
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const result = await response.json();

                if (!result.has_active_subscription || !result.data) {
                    setInfo('No active subscription found for selected shop.', 'danger');
                    return;
                }

                const data = result.data;
                const html = `
                    <div><strong>Current Active Subscription</strong></div>
                    <div class="mt-2 row g-1">
                        <div><strong>Plan:</strong> ${formatValue(data.plan_name)}</div>
                        <div><strong>Price:</strong> ${formatValue(data.price)}</div>
                        <div><strong>Validity:</strong> ${formatValue(data.duration, ' Days')}</div>
                        <div><strong>Start Date:</strong> ${formatValue(data.starts_at)}</div>
                        <div><strong>Expiry Date:</strong> ${formatValue(data.ends_at)}</div>
                        <div><strong>Remaining Days:</strong> ${formatValue(data.remaining_days, ' Days')}</div>
                        <div><strong>Sale Limit:</strong> ${formatValue(data.sale_limit)}</div>
                        <div><strong>Remaining Sales:</strong> ${formatValue(data.remaining_sales)}</div>
                    </div>
                `;

                setInfo(html, 'success');
            } catch (error) {
                setInfo('Unable to fetch current subscription details. Please try again.', 'danger');
            }
        }

        initStoreSelect2StatusWithRetry();

        shopSelect.addEventListener('change', function () {
            loadCurrentSubscription(this.value);
        });

        // Select2 can fire custom events; listen for those too when available.
        if (window.jQuery) {
            window.jQuery(shopSelect).on('select2:select select2:clear', function () {
                loadCurrentSubscription(this.value);
            });
        }

        if (shopSelect.value) {
            loadCurrentSubscription(shopSelect.value);
        }
    })();
</script>
@endpush
