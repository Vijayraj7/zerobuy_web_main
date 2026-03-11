@extends('layouts.app')

@section('header-title', __('Subscription Plans'))

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="w-100 page-title-heading d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>{{__('Subscription Plans')}}</div>
                <div class="d-flex gap-2 align-items-center gap-md-4">
                    @hasPermission('admin.subscription-plan.create')
                    <a href="{{ route('admin.subscription-plan.create') }}" class="btn py-2 btn-primary">
                        <i class="fa fa-plus-circle"></i>
                        {{__('Add Subscription Plan')}}
                    </a>
                    @endhasPermission
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="subscription-plan-container">
            @forelse ($subscriptionPlans as $key => $subscriptionPlan)
                <div class="subscription-plan {{ $subscriptionPlan->is_popular ? 'popular' : 'position-relative'}}">
                    @if ($subscriptionPlan->is_popular)
                        <div class="popular-plan position-relative">
                    @endif
                    <div class="w-100">
                        <!-- <div style="align-self: stretch; padding-left: 24px; padding-right: 24px; padding-top: 32px; padding-bottom: 12px; flex-direction: column; justify-content: flex-start; align-items: flex-start; gap: 16px; display: flex">
                            <div style="color: white; font-size: 16px; font-family: Inter; font-weight: 600; letter-spacing: 0.32px; word-wrap: break-word">{{ $subscriptionPlan->name }}</div>
                            <div style="align-self: stretch; justify-content: flex-start; align-items: center; gap: 8px; display: inline-flex">
                                <div style="color: white; font-size: 36px; font-family: Inter; font-weight: 800; letter-spacing: 0.72px; word-wrap: break-word">{{ showCurrency($subscriptionPlan->price + 0) }}</div>
                                <div style="flex-direction: column; justify-content: center; align-items: flex-start; gap: 2px; display: inline-flex">
                                    <div style="color: white; font-size: 12px; font-family: Inter; font-weight: 400; word-wrap: break-word">{{ $subscriptionPlan->duration ? daysToLargestUnit($subscriptionPlan->duration) : 'Unlimited' }}</div>
                                </div>
                            </div>
                        </div> -->

                        <div style="align-self: stretch; padding-left: 24px; padding-right: 24px; padding-top: 32px; padding-bottom: 12px; display: flex; flex-direction: column; gap: 16px;">
                            <!-- Plan Name -->
                            <div style="color: white; font-size: 16px; font-family: Inter; font-weight: 600;">
                                {{ $subscriptionPlan->name }}
                            </div>
                            <div style="font-size: 12px; font-family: Inter; font-weight: 500; margin-top: -10px;" class="{{ $subscriptionPlan->is_one_time_purchase ? 'text-success' : 'text-warning' }}">
                                {{ $subscriptionPlan->is_one_time_purchase ? 'One Time Purchase Per Store' : 'Repeat Purchase Allowed' }}
                            </div>
                            <!-- Price Row -->
                            <div style="display: inline-flex; align-items: center; gap: 12px;">
                                <!-- Price Section -->
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <!-- Selling Price -->
                                    <div style="color: white; font-size: 36px; font-family: Inter; font-weight: 800;">
                                        {{ showCurrency($subscriptionPlan->price) }}
                                    </div>
                                    <!-- MRP + Discount -->
                                    @if($subscriptionPlan->mrp && $subscriptionPlan->mrp > $subscriptionPlan->price)
                                        @php
                                            $discount = round((($subscriptionPlan->mrp - $subscriptionPlan->price) / $subscriptionPlan->mrp) * 100);
                                        @endphp
                                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                                            <!-- Crossed MRP -->
                                            <span style="color: #9CA3AF; font-size: 14px; font-family: Inter; text-decoration: line-through;">
                                                {{ showCurrency($subscriptionPlan->mrp) }}
                                            </span>
                                            <!-- Discount -->
                                            <span style="color: #22C55E; font-size: 13px; font-family: Inter; font-weight: 600;">
                                                {{ $discount }}% OFF
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Duration -->
                                <div style="display: flex; flex-direction: column; justify-content: center;">
                                    <div style="color: white; font-size: 12px; font-family: Inter;">
                                        {{ $subscriptionPlan->duration ? daysToLargestUnit($subscriptionPlan->duration) : 'Unlimited' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($subscriptionPlan->is_popular)
                            <div style="padding-left: 8px; padding-right: 8px; padding-top: 6px; padding-bottom: 6px; right: 24px; top: 24px; position: absolute; background: rgba(255, 255, 255, 0.09); border-radius: 8px; justify-content: center; align-items: center; gap: 4px; display: inline-flex">
                                <div style="text-align: center; color: #FFC107; font-size: 12px; font-family: Inter; font-weight: 500; letter-spacing: 0.24px; word-wrap: break-word">Most Popular</div>
                            </div>
                        @endif
                        <div class="w-100 px-4 d-flex gap-3 justify-content-between text-white">
                            <div class="plan-info-box">
                                <span class="label">Sale limit</span>
                                <span class="value">{{ $subscriptionPlan->sale_limit ?? 'Unlimited' }}</span>
                            </div>
                            <div class="plan-info-box">
                                <span class="label">Duration</span>
                                <span class="value">{{ $subscriptionPlan->duration ? daysToLargestUnit($subscriptionPlan->duration) : 'Unlimited' }}</span>
                            </div>
                        </div>
                        <div class="w-100 p-4 text-white">
                            {!! $subscriptionPlan->description !!}
                        </div>
                    </div>
                    <div class="w-100">
                        <div style="align-self: stretch; padding-bottom: 32px; padding-left: 24px; padding-right: 24px; justify-content: space-between; align-items: center; gap: 10px; display: flex">
                            <a class="btn btn-info btn-sm text-capitalize w-100" href="{{ route('admin.subscription-plan.edit', $subscriptionPlan->id) }}">
                                Edit
                            </a>
                            <a href="{{ route('admin.subscription-plan.toggle', $subscriptionPlan->id) }}"
                                class="btn btn-sm text-capitalize w-100 {{ $subscriptionPlan->is_active ? 'btn-warning' : 'btn-success' }}">
                                {{ $subscriptionPlan->is_active ? 'Deactivate' : 'Activate' }}
                            </a>
                        </div>
                    </div>
                    @if ($subscriptionPlan->is_popular)
                        </div>
                    @endif
                </div>
            @empty
                <div class="card shadow-sm rounded-12 show-card position-relative overflow-hidden w-100">
                    <div class="card-body shop p-2">
                        <p class="text-center text-muted">{{ __('No subscription plans found') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="my-3">
            {{ $subscriptionPlans->links() }}
        </div>

    </div>
@endsection

@push('css')
    <style>
        .subscription-plan-container {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
            height: 100%;
            justify-content: center;
            row-gap: 72px;
            column-gap: 24px;
            margin: 48px 0;
        }

        .subscription-plan {
            width: 300px;
            min-height: 500px;
            background: #24262D;
            overflow: hidden;
            border-radius: 30px;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            display: inline-flex;
            transform: translateY(24px);
        }

        .subscription-plan.popular {
            padding: 8px;
            border-radius: 32px;
            background: #D7DAE0;
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.28);
            transform: translateY(-24px);
        }

        .popular-plan {
            width: 100%;
            height: 100%;
            background: linear-gradient(136deg, #010101 0%, #DD2C5C 100%);
            border-radius: 30px;
            outline: 7px #D7DAE0 solid;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            display: inline-flex
        }

        .plan-info-box {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 8px 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
            min-width: 0;
        }

        .plan-info-box .label {
            font-size: 13px;
            color: #bbbbbb;
            font-weight: 500;
        }

        .plan-info-box .value {
            font-size: 16px;
            font-weight: 600;
            color: white;
            word-break: break-word;
        }
    </style>
@endpush

@push('scripts')
    <script>
        fixHeight();

        window.addEventListener('resize', fixHeight);

        function fixHeight() {
            const planElements = document.querySelectorAll('.subscription-plan');

            let maxHeight = 0;

            planElements.forEach(planElement => {
                const planHeight = planElement.getBoundingClientRect().height;
                if (planHeight > maxHeight) {
                    maxHeight = planHeight;
                }
            });

            planElements.forEach(planElement => {
                planElement.style.height = `${maxHeight}px`;
            });
        }
    </script>
@endpush
