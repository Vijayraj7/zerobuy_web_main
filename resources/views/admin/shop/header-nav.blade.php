<!---################################--->
<!-- ////// Shop Header Navbar  ////// -->
<!---################################--->

<ul class="nav nav-tabs">
    @hasPermission('admin.shop.show')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.show') ? 'active' : '' }}"
                href="{{ route('admin.shop.show', $shop->id) }}">
                {{ __('Shop overview') }}
            </a>
        </li>
    @endhasPermission

    @hasPermission('admin.shop.orders')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.orders') ? 'active' : '' }}"
                href="{{ route('admin.shop.orders', $shop->id) }}">
                {{ __('Order') }}
            </a>
        </li>
    @endhasPermission

    @hasPermission('admin.shop.return')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.return') ? 'active' : '' }}"
                href="{{ route('admin.shop.return', $shop->id) }}">
                {{ __('Return') }}
            </a>
        </li>
    @endhasPermission

    @hasPermission('admin.shop.products')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.products') ? 'active' : '' }}"
                href="{{ route('admin.shop.products', $shop->id) }}">
                {{ __('Product') }}
            </a>
        </li>
    @endhasPermission

    @hasPermission('admin.shop.category')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.category') ? 'active' : '' }}"
                href="{{ route('admin.shop.category', $shop->id) }}">
                {{ __('Category') }}
            </a>
        </li>
    @endhasPermission

    @hasPermission('admin.shop.address')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.address') ? 'active' : '' }}"
                href="{{ route('admin.shop.address', $shop->id) }}">
                {{ __('Address') }}
            </a>
        </li>
    @endhasPermission

    @hasPermission('admin.shop.followers')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.followers') ? 'active' : '' }}"
                href="{{ route('admin.shop.followers', $shop->id) }}">
                {{ __('Followers') }}
            </a>
        </li>
    @endhasPermission

    <!-- @hasPermission('admin.shop.reviews')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shop.reviews') ? 'active' : '' }}"
                href="{{ route('admin.shop.reviews', $shop->id) }}">
                {{ __('Review') }}
            </a>
        </li>
    @endhasPermission -->
</ul>
