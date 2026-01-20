<div class="table-responsive">
    <table class="table border-left-right table-responsive-lg">
        <thead>
            <tr>
                <th class="text-center">{{ __('SL') }}.</th>
                <th>{{ __('Thumbnail') }}</th>
                <th style="min-width: 150px">{{ __('Product Name') }}</th>
                <th style="min-width: 100px">{{ __('Brand') }}</th>
                <th class="text-center">{{ __('Price') }}</th>
                <th class="text-center" style="min-width: 120px">{{ __('Discount Price') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
                <th class="text-center">{{ __('Action') }}</th>
            </tr>
        </thead>
        @forelse($products as $key => $product)
            <tr>
                <td class="text-center">{{ ++$key }}</td>

                <td>
                    <img src="{{ $product->thumbnail }}" width="50">
                </td>

                <td>{{ Str::limit($product->name, 50, '...') }}</td>
                <td>
                    {{ $product?->brand?->name ?? 'N/A' }}
                </td>

                <td class="text-center">
                    {{ showCurrency($product->price) }}
                </td>

                <td class="text-center">
                    {{ showCurrency($product->discount_price) }}
                </td>
                <td class="text-center">
                    <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-title="{{ __('Update product status') }}">
                        <a href="{{ route('shop.product.toggle', $product->id) }}">
                            <!-- <input type="checkbox" {{ $product->is_active ? 'checked' : '' }}> -->
                            <input data-bs-title="{{ $product->disabled_by_admin ? 'Disabled by admin' : 'Update product status' }}" type="checkbox" {{ $product->is_active ? 'checked' : '' }} {{ $product->disabled_by_admin ? 'disabled' : '' }}>
                            <span class="slider round"></span>
                        </a>
                    </label>
                    @if ($product->disabled_by_admin)
                        <span class="badge bg-danger mt-1">
                            {{ __('Disabled by Admin') }}
                        </span>
                    @endif
                </td>

                <td class="text-center">
                    @hasPermission('shop.product.show')
                        <a href="{{ route('shop.product.show', $product->id) }}"
                            class="svg-bg btn-outline-primary circleIcon btn-sm" data-bs-toggle="tooltip"
                            data-bs-placement="left" data-bs-title="{{ __('View Product') }}">
                            <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="icon" loading="lazy" />
                        </a>
                    @endhasPermission
                    @hasPermission('shop.product.barcode')
                        <a href="{{ route('shop.product.barcode', $product->id) }}"
                            class="btn-outline-secondary circleIcon btn-sm" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ __('Generate Barcode for this product') }}">
                            <img src="{{ asset('assets/icons-admin/scanner.svg') }}" alt="icon" loading="lazy" />
                        </a>
                    @endhasPermission

                    @hasPermission('shop.product.edit')
                        <a href="{{ route('shop.product.edit', $product->id) }}" class="btn-outline-info circleIcon btn-sm"
                            data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ __('Edit Product') }}">
                            <img src="{{ asset('assets/icons-admin/edit.svg') }}" alt="icon" loading="lazy" />
                        </a>
                    @endhasPermission


                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="100%">{{ __('No Data Found') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
