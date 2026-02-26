@extends('layouts.app')

@section('header-title', __('Profile Details'))

@section('content')
    <div>
        <h4>
            {{ __('Profile Details') }}
        </h4>
    </div>

    <div class="row mb-3">
        <div class="col-lg-8 mt-3">
            <div class="card rounded-12 position-relative overflow-hidden">
                <div class="card-body shop details p-2 border-bottom pb-3">
                    <div class="banner position-relative">
                        <img class="img-fit" src="{{ $shop->banner }}" />
                    </div>
                    <a href="{{ route('shop.profile.edit', $shop->id) }}" class="editBtn svg-bg">
                        <img src="{{ asset('assets/icons-admin/edit.svg') }}" alt="edit" loading="lazy" />
                        <span>{{ __('Edit') }}</span>
                    </a>
                    <div class="main-content d-flex align-items-center">
                        <div class="logo">
                            <img class="img-fit" src="{{ $shop->logo }}" />
                        </div>
                        <div class="personal">
                            <span class="name h4 mb-1">{{ $shop->name }}</span>
                            <div class="d-flex gap-2 align-items-center ">
                                <div>
                                    @foreach (range(1, 5) as $rating)
                                        @if ($shop->averageRating >= $rating)
                                            <i class="fa-solid fa-star text-warning"></i>
                                        @else
                                            <i class="fa-regular fa-star text-secondary"></i>
                                        @endif
                                    @endforeach
                                </div>
                                <div>
                                    <span class="fw-bold">{{ $shop->averageRating }}</span>
                                    ({{ $shop->reviews->count() }} {{ __('Reviews') }})
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="/shops/{{ $shop->id }}" target="blank"
                                    class="btn btn-outline-primary btn-sm">
                                    {{ __('View Live') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <h4 class="m-0 p-3 border-bottom">{{ __('User Information') }}</h4>
                <div class="card-body pt-0">
                    <table class="table mb-0">
                        <tr>
                            <td style="width: 180px">{{ __('Name') }}:</td>
                            <td>{{ $shop->user?->name }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Phone') }}:</td>
                            <td>{{ $shop->user?->phone }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Email') }}:</td>
                            <td>{{ $shop->user?->email }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <h4 class="m-0 p-3 border-bottom">{{ __('Shop Information') }}</h4>
                <div class="card-body pt-0">
                    <table class="table mb-0">
                        <tr>
                            <td style="width: 180px">{{ __('Name') }}:</td>
                            <td>{{ $shop->name }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Estimated Delivery') }}:</td>
                            <td>{{ $shop->estimated_delivery_time }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Address') }}:</td>
                            <td>{{ $shop->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Minimum Order Amount') }}:</td>
                            <td>{{ $shop->min_order_amount ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Return Policy') }}:</td>
                            <td>{{ $shop->return_policy ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Shop Description') }}:</td>
                            <td>{{ $shop->description }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mt-3">
            @php
                $deliveryStateNames = $shop->states()
                    ->whereIn('id', $shop->deliverySetting?->selected_state_ids ?? [])
                    ->pluck('name')
                    ->toArray();
            @endphp

            <div class="card">
                <h4 class="m-0 p-3 border-bottom">{{ __('Product Information') }}</h4>
                <div class="card-body pt-0">
                    <table class="table mb-0">
                        <tr>
                            <td style="width: 180px">{{ __('Total Products') }}:</td>
                            <td>
                                <span class="fw-bold">{{ $shop->products->count() }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Total Orders') }}:</td>
                            <td>
                                <span class="fw-bold">{{ $shop->orders->count() }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 180px; text-transform: capitalize">{{ __('reviews') }}</td>
                            <td>
                                <span class="fw-bold">{{ $shop->reviews->count() }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <h4 class="m-0 p-3 border-bottom">Store Information</h4>
                <div class="card-body pt-0">
                    <table class="table mb-0">
                        <tr>
                            <td style="width: 180px">{{ __('Store Type') }}:</td>
                            <td>{{ ucfirst($shop->store_type ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Whatsapp Number') }}:</td>
                            <td>{{ $shop->whatsapp_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('State') }}:</td>
                            <td>{{ $shop->states?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('District') }}:</td>
                            <td>{{ $shop->districts?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Pin Code') }}:</td>
                            <td>{{ $shop->pincode ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('GST Number') }}:</td>
                            <td>{{ $shop->gst_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 180px">{{ __('Store Since') }}:</td>
                            <td>{{ $shop->store_since ? date('d-m-Y', strtotime($shop->store_since)) : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <h4 class="m-0 p-3 border-bottom">{{ __('Business Categories') }}</h4>
                <div class="card-body">
                    @forelse($shop->businessCategories as $category)
                        <span class="badge badge-primary clear-badge-font me-1 mb-1">{{ $category->name }}</span>
                    @empty
                        <span>-</span>
                    @endforelse
                </div>
            </div>

            <div class="card mt-3">
                <h4 class="m-0 p-3 border-bottom">{{ __('Delivery Mode') }}</h4>
                <div class="card-body">
                    @switch($shop->deliverySetting?->delivery_mode)
                        @case('amount_based') {{ __('Amount Based') }} @break
                        @case('state_wise') {{ __('State Wise') }} @break
                        @case('manual') {{ __('Manual') }} @break
                        @default -
                    @endswitch
                </div>
            </div>

            <div class="card mt-3">
                <h4 class="m-0 p-3 border-bottom">{{ __('Delivery States') }}</h4>
                <div class="card-body">
                    {{ count($deliveryStateNames) ? implode(', ', $deliveryStateNames) : '-' }}
                </div>
            </div>

            <div class="card mt-3">
                <h4 class="m-0 p-3 border-bottom">{{ __('Amount Ranges') }}</h4>
                <div class="card-body">
                    @if($shop->deliverySetting?->delivery_mode === 'amount_based')
                        @forelse($shop->deliverySetting->amountRules as $rule)
                            <div>₹{{ $rule->min_amount }} - ₹{{ $rule->max_amount }} → ₹{{ $rule->charge }}</div>
                        @empty
                            <span>-</span>
                        @endforelse
                    @else
                        <span>-</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
