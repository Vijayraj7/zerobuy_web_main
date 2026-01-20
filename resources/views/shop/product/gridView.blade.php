<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4 g-4">
    @forelse($products as $key => $product)
        <div class="col">
            <div class="card shadow-sm d-flex flex-column h-100">
                <img src="{{ $product->thumbnail }}" alt="" class="card-img-top" loading="lazy" width="100%"
                    style="height: 200px; object-fit: cover" />
                <div class="card-body h-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="{{ __('Update product status') }}">
                            <a href="{{ route('shop.product.toggle', $product->id) }}">
                                <!-- <input type="checkbox" {{ $product->is_active ? 'checked' : '' }}> -->
                                <input data-bs-title="{{ $product->disabled_by_admin ? 'Disabled by admin' : 'Update product status' }}" type="checkbox" {{ $product->is_active ? 'checked' : '' }} {{ $product->disabled_by_admin ? 'disabled' : '' }}>
                                <span class="slider round"></span>
                                @if ($product->disabled_by_admin)
                                    <span class="badge bg-danger mt-2">
                                        {{ __('Disabled by Admin') }}
                                    </span>
                                @endif
                            </a>
                        </label>
                        <div class="d-flex gap-2 mt-2">
                            @hasPermission('shop.product.show')
                                <a href="{{ route('shop.product.show', $product->id) }}"
                                    class="svg-bg btn-outline-primary circleIcon btn-sm" data-bs-toggle="tooltip"
                                    data-bs-placement="left" data-bs-title="{{ __('View Product') }}">
                                    <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="icon" loading="lazy" />
                                </a>
                            @endhasPermission
                            @hasPermission('shop.product.barcode')
                                <a href="{{ route('shop.product.barcode', $product->id) }}"
                                    class="btn-outline-info circleIcon btn-sm" data-bs-toggle="tooltip"
                                    data-bs-placement="top" data-bs-title="{{ __('Generate Barcode for this product') }}">
                                    <i class="bi bi-upc-scan"></i>
                                </a>
                            @endhasPermission
                            @hasPermission('shop.product.edit')
                                <a href="{{ route('shop.product.edit', $product->id) }}"
                                    class="btn-outline-info circleIcon btn-sm" data-bs-toggle="tooltip"
                                    data-bs-placement="left" data-bs-title="{{ __('Edit Product') }}">
                                    <img src="{{ asset('assets/icons-admin/edit.svg') }}" alt="icon" loading="lazy" />
                                </a>
                            @endhasPermission
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5 class="card-title">{{ Str::limit($product->name, 50, '...') }}</h5>

                        <p>
                            {{ $product->short_description }}
                        </p>

                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3">
                            <p class="card-text m-0 d-flex align-items-center gap-1">
                                <strong>{{ showCurrency($product->price) }}</strong>
                                @if ($product->discount_price)
                                    <span class="badge bg-primary rounded-pill">
                                        {{ showCurrency($product->discount_price) }}
                                    </span>
                                @endif
                            </p>

                            <div class="d-flex align-items-center gap-1">
                                <i class="fa fa-star text-warning"></i>
                                ({{ number_format($product->reviews->avg('rating'), 1) }})
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    {{ __('No Data Found') }}
                </div>
            </div>
        </div>
    @endforelse
</div>
