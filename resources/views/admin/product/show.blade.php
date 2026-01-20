@extends('layouts.app')

@section('header-title', __('Product Details'))

@section('content')
    <div><h4>{{ __('Product Details') }}</h4></div>


    <div class="card mt-3 shadow-sm">
        <div class="card-body">
            <div class="d-flex gap-3">
                <div class="text-center">
                    <div class="rounded overflow-hidden ratio1x1">
                        <img src="{{ $product->thumbnail }}" alt="" width="140">
                    </div>
                    <a href="/products/{{ $product->id }}/details" target="_blank" class="btn btn-outline-primary mt-3">
                        <i class="fa-solid fa-globe"></i> {{ __('View Live') }}
                    </a>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap gap-3 justify-content-between">
                        <div class="d-flex gap-3 productThumbnail">
                            @foreach ($product->thumbnails() as $photo)
                                <img src="{{ $photo->thumbnail }}" alt="product" />
                            @endforeach
                        </div>

                        <div>
                            <div class="d-flex gap-3 border p-2 rounded fw-bold">
                                <div>{{ $product->orders->count() }} {{ __('Orders') }}</div>

                                <div class="border-start w-0" style="height: 20px"></div>

                                <div>
                                    <i class="fa-solid fa-star text-warning"></i>
                                    {{ number_format($product->reviews->avg('rating'), 1) }}
                                </div>

                                <div class="border-start w-0" style="height: 20px"></div>

                                <div>{{ number_format($product->reviews->count(), 1) }} {{ __('Reviews') }}</div>
                            </div>
                            <div class="mt-2">
                                <div>
                                    {{ __('status') }}:
                                    @if ($product->is_approve)
                                        <span class="status-approved">
                                            <i class="fa fa-check text-success"></i> {{ __('Approved') }}
                                        </span>
                                    @else
                                        <span class="status-pending">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {{ __('Pending') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                        </div>

                    </div>
                    <h3 class="mb-2 mt-3 pb-1">{{ $product->name }}</h3>

                    <div>
                        <h6 class="mb-1 text-muted">
                            {{ __('Short Description') }}
                        </h6>
                        <p>{{ $product->short_description }}</p>
                    </div>
                </div>
            </div>

            <div class="border-top my-3"></div>

            <!-- General Information -->
            <div class="d-flex gap-4 flex-wrap justify-content-lg-between">

                <div>
                    @php
                        $categories = $product->categories?->pluck('name')->join(', ');
                        $colors = $product->colors?->pluck('name')->join(', ');
                        $sizes = $product->sizes?->pluck('name')->join(', ');
                    @endphp
                    <h5 class="text-dark fw-bold">
                        {{ __('General Information') }}
                    </h5>
                    <table class="table table-borderless mb-0 border-0">
                        <!-- <tr>
                            <td class="ps-0 py-1">{{ __('Brand') }}</td>
                            <td class="py-1">
                                : {{ __($product->brand?->name) }}
                            </td>
                        </tr> -->
                        <tr>
                            <td class="ps-0 py-1">{{ __('Categories') }}</td>
                            <td class="py-1">
                                : {{ $categories }}
                            </td>
                        </tr>
                        <!-- <tr>
                            <td class="ps-0 py-1">{{ __('Colors') }}</td>
                            <td class="py-1">
                                : {{ $colors }}
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-0 py-1">{{ __('Sizes') }}</td>
                            <td class="py-1">
                                : {{ $sizes }}
                            </td>
                        </tr> -->
                    </table>
                </div>

                <div>
                    <h5 class="text-dark fw-bold">{{ __('Price Information') }}</h5>
                    <table class="table table-borderless mb-0 border-0">
                        <tr>
                            <td class="ps-0 py-1">{{ __('Price') }}</td>
                            <td class="py-1">: {{ showCurrency($product->price) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-0 py-1">{{ __('Discount Price') }}</td>
                            <td class="py-1">
                                : {{ showCurrency($product->discount_price) }}
                            </td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h5 class="text-dark fw-bold">
                        {{ __('Current Stock Quantity') }}
                    </h5>
                    <p class="mb-0 fw-bold">
                        {{ $product->quantity }}
                    </p>
                </div>
            </div>

            @if ($product->itemDetails->isNotEmpty())
                <div class="border-top my-3"></div>

                <h5 class="fw-bold">{{ __('Product Item Details') }}</h5>

                <table class="table table-sm table-bordered w-50">
                    @foreach ($product->itemDetails as $detail)
                        <tr>
                            <th>{{ $detail->item_name }}</th>
                            <td>{{ $detail->item_value }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @php
                $hasColor = $product->variants->whereNotNull('color_id')->count() > 0;
                $hasSize  = $product->variants->whereNotNull('size_id')->count() > 0;
            @endphp


            @if ($product->variants->isNotEmpty())
                <div class="border-top my-4"></div>

                <h5 class="fw-bold mb-3">{{ __('Product Variants') }}</h5>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>

                                @if ($hasColor)
                                    <th>{{ __('Color') }}</th>
                                @endif

                                @if ($hasSize)
                                    <th>{{ __('Size') }}</th>
                                @endif

                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Quantity') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($product->variants as $index => $variant)
                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    {{-- COLOR --}}
                                    @if ($hasColor)
                                        <td>
                                            @if ($variant->color)
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <span class="color-box"
                                                        style="background-color: {{ $variant->color->color_code }}">
                                                    </span>
                                                    <span class="fw-semibold">
                                                        {{ $variant->color->name }}
                                                    </span>
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endif

                                    {{-- SIZE --}}
                                    @if ($hasSize)
                                        <td>
                                            {{ $variant->size?->name ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="fw-semibold">
                                        {{ showCurrency($variant->price) }}
                                    </td>

                                    <td>
                                        <span class="badge bg-success">
                                            {{ $variant->quantity }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif


            @if ($product->bulkItems->isNotEmpty())
                <div class="border-top my-3"></div>

                <h5 class="fw-bold">{{ __('Bulk Items') }}</h5>

                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Item Name') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('MOQ') }}</th>
                            <th>{{ __('MRP') }}</th>
                            <th>{{ __('Selling Price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->bulkItems as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->moq }}</td>
                                <td>{{ showCurrency($item->mrp) }}</td>
                                <td>{{ showCurrency($item->selling_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($product->bulkPrices->isNotEmpty())
                <div class="border-top my-3"></div>

                <h5 class="fw-bold">{{ __('Bulk Pricing') }}</h5>

                <table class="table table-bordered table-sm w-50">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Min Qty') }}</th>
                            <th>{{ __('Max Qty') }}</th>
                            <th>{{ __('Price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->bulkPrices as $price)
                            <tr>
                                <td>{{ $price->min_qty }}</td>
                                <td>{{ $price->max_qty }}</td>
                                <td>{{ showCurrency($price->price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif





            <div class="border-top my-3"></div>

            <div>
                @if ($product->video)
                    <div>
                        <h5 class="text-dark fw-bold">
                            {{ __('Product Video') }}
                        </h5>
                    </div>
                    <div id="videoContainer">
                        @if ($product->video->type == 'file')
                            <video controls style="max-width: 700px; max-height: 300px">
                                <source src="{{ asset($product->video->url) }}">
                            </video>
                        @elseif ($product->video->type != 'file')
                            <div style="max-width: 700px; overflow: hidden">
                                {!! $product->video->url !!}
                            </div>
                        @endif
                    </div>
                @endif

                <h5 class="text-dark fw-bold">
                    {{ __('Description') }}
                </h5>
                <p>
                    {!! $product->description !!}
                </p>
            </div>

        </div>
    </div>
@endsection

@push('css')
    <style>
        iframe {
            height: 380px;
        }
    </style>
    <style>
        .color-box {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1px solid #ccc;
            display: inline-block;
        }
    </style>
@endpush
