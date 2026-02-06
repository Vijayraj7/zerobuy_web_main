<div class="table-responsive">
    <table class="table border-left-right table-responsive-lg">
        <thead>
            <tr>
                <th class="text-center">{{ __('SL') }}</th>
                <th>{{ __('Create Date') }}</th>
                <th>{{ __('Product ID') }}</th>
                <th style="min-width: 150px">{{ __('Product Name') }}</th>
                <th>{{ __('Image') }}</th>
                <th class="text-center">{{ __('Qty') }}</th>
                <th class="text-center">{{ __('MRP') }}</th>
                <th class="text-center" style="min-width: 120px">{{ __('Selling Price') }}</th>
                <th class="text-center">{{ __('Total Sales') }}</th>
                <th class="text-center">{{ __('Variants') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
                <th class="text-center">{{ __('Action') }}</th>
            </tr>
        </thead>
        @forelse($products as $key => $product)
            <tr>
                <td class="text-center">{{ ++$key }}</td>
                <td>{{ $product->created_at?->format('d-m-Y | h:i A') ?? '-' }}</td>
                <td>{{ $product->formatted_id ?? ('PRD0' . $product->id) }}</td>
                <td>{{ Str::limit($product->name, 50, '...') }}</td>
                <td>
                    <img src="{{ $product->thumbnail }}" width="40" height="40" class="rounded" loading="lazy">
                </td>
                <td class="text-center">{{ $product->quantity ?? 0 }}</td>
                <td class="text-center">{{ showCurrency($product->price) }}</td>
                <td class="text-center">{{ showCurrency($product->discount_price) }}</td>
                <td class="text-center">{{ $product->orderItems?->sum('quantity') ?? 0 }}</td>
                <td class="text-center">{{ $product->variants?->count() ?? 0 }}</td>
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
