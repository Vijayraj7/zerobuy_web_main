@extends('layouts.app')

@section('header-title', __('Notifications'))
@section('header-subtitle', __('Manage Notifications'))

@section('content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-md-6">
                <div class="my-3 card">
                    <div class="card-body">
                        <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
                            <h4>{{ __('User Notifications') }}</h4>

                            <a href="javascript:void(0);" class="btn py-2 btn-primary" data-bs-toggle="modal"
                                data-bs-target="#userNotificationModal">
                                <i class="fa fa-plus-circle"></i>
                                {{ __('User Notifications ') }}
                            </a>
                        </div>
                        <div class="row mt-4">
                            @foreach ($userNotifications as $noti)
                                <div class="col-md-6 mb-3">
                                    <div class="card p-2">
                                        <img src="{{ $noti->thumbnail }}" class="w-100 rounded">

                                        <div class="mt-2">
                                            <div class="small text-muted">
                                                Type: <b>{{ $noti->notification_option_type }} |
                                                    {{ $noti->optionName() ?? 'N/A' }}</b>
                                            </div>

                                            @if ($noti->message)
                                                <div class="small mt-1">
                                                    Message: <span>{{ $noti->message }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-center gap-2 mt-2">
                                            <button type="button" class="btn btn-success btn-edit-user"
                                                data-id="{{ $noti->id }}"
                                                data-business-category="{{ $noti->business_category_id }}"
                                                data-option-type="{{ $noti->notification_option_type }}"
                                                data-option-link="{{ $noti->notification_option_link }}"
                                                data-option-name="{{ $noti->optionName() ?? 'N/A' }}"
                                                data-message="{{ $noti->message }}"
                                                data-thumbnail="{{ $noti->thumbnail }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="{{ route('admin.notification.resend', $noti->id) }}"
                                                class="btn btn-info">
                                                <i class="fa fa-bell"></i>
                                            </a>
                                            <form action="{{ route('admin.notification.delete', $noti->id) }}"
                                                method="POST" class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="my-3 card">
                    <div class="card-body">
                        <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
                            <h4>{{ __('Seller Notifications') }}</h4>

                            <a href="javascript:void(0);" class="btn py-2 btn-warning" data-bs-toggle="modal"
                                data-bs-target="#sellerNotificationModal">
                                <i class="fa fa-plus-circle"></i>
                                {{ __('Seller Notifications ') }}
                            </a>
                        </div>
                        <div class="row mt-4">
                            @foreach ($sellerNotifications as $noti)
                                <div class="col-md-6 mb-3">
                                    <div class="card p-2">
                                        <img src="{{ $noti->thumbnail }}" class="w-100 rounded">
                                        <div class="mt-2">
                                            <div class="small">
                                                Send To:
                                                <b>
                                                    @if (($noti->seller_audience ?? ($noti->shop_id ? 'shop' : 'all')) === 'shop')
                                                        {{ $noti->shop?->name ?? 'N/A' }}
                                                    @elseif (($noti->seller_audience ?? 'all') === 'active')
                                                        Active Sellers
                                                    @elseif (($noti->seller_audience ?? 'all') === 'inactive')
                                                        Non Active Sellers
                                                    @else
                                                        All Sellers
                                                    @endif
                                                </b>
                                            </div>

                                            @if ($noti->message)
                                                <div class="small mt-1">
                                                    Message: <span>{{ $noti->message }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-center gap-2 mt-2">
                                            <button type="button" class="btn btn-success btn-edit-seller"
                                                data-id="{{ $noti->id }}"
                                                data-business-category="{{ $noti->business_category_id ?? '' }}"
                                                data-shop-id="{{ $noti->shop_id }}"
                                                data-seller-audience="{{ $noti->seller_audience ?? ($noti->shop_id ? 'shop' : 'all') }}"
                                                data-shop-name="{{ $noti->shop?->name ?? 'N/A' }}"
                                                data-message="{{ $noti->message }}"
                                                data-thumbnail="{{ $noti->thumbnail }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="{{ route('admin.notification.resend', $noti->id) }}"
                                                class="btn btn-info">
                                                <i class="fa fa-bell"></i>
                                            </a>
                                            <form action="{{ route('admin.notification.delete', $noti->id) }}"
                                                method="POST" class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="userNotificationModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" id="userNotificationForm" action="{{ route('admin.user.notification.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="user_method" value="POST">
                    <input type="hidden" name="notification_id" id="user_notification_id">
                    <div class="modal-header">
                        <h4 class="modal-title" id="userModalTitle">User Notification</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row">
                        <div class="mb-3">
                            <x-select name="business_category_id" id="business_category_id" label="Business Category"
                                class="select2 mb-3" required>
                                <option value="">--Main Notification Category--</option>
                                @foreach ($businessCategories as $bc)
                                    <option value="{{ $bc->id }}">{{ $bc->name }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-3">
                            <x-select name="notification_option_type" label="Slider Type" id="notification_option_type">
                                <option value="">--Notification Type--</option>
                                <option value="sub_category">Sub Category</option>
                                <option value="child_category">Child Category</option>
                                <option value="product">Product</option>
                                <option value="shop">Store</option>
                            </x-select>
                        </div>

                        <div class="mb-3">
                            <x-select name="notification_option_link" label="Option Types" id="notification_option_link"
                                class="select2"></x-select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" id="user_message" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="ratio4x1">
                                    <img src="https://placehold.co/2000x500/f1f5f9/png" id="notification_banner"
                                        alt="" width="100%">
                                </div>
                            </div>
                            <x-file name="notification_banner" label="Notification Banner Ratio 4:1 (2000 x 500 px)"
                                preview="notification_banner" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sellerNotificationModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" id="sellerNotificationForm" action="{{ route('admin.seller.notification.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="seller_method" value="POST">
                    <input type="hidden" name="notification_id" id="seller_notification_id">

                    <div class="modal-header">
                        <h4 class="modal-title" id="sellerModalTitle">Seller Notification</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row">

                        <div class="mb-3">
                            <div class="mb-3">
                                <label class="form-label">Business Category</label>
                                <select name="seller_business_category_id" class="form-control select2Seller">
                                    <option value="">All Business Categories</option>
                                    @foreach ($businessCategories as $bc)
                                        <option value="{{ $bc->id }}">{{ $bc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Send To</label>
                            <select name="seller_audience" id="seller_audience" class="form-control select2Seller">
                                <option value="all">All Sellers</option>
                                <option value="active">Active Sellers</option>
                                <option value="inactive">Non Active Sellers</option>
                                <option value="shop">Specific Seller Store</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Store / Seller (Shop)</label>
                            <select name="shop_id" id="seller_shop_id" class="form-control select2Seller" style="width: 100%;">
                                <option value="">Select Seller Store</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" id="seller_message" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mt-2">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="ratio4x1">
                                    <img src="https://placehold.co/2000x500/f1f5f9/png"
                                        id="seller_notification_banner_preview" width="100%">
                                </div>
                            </div>

                            <x-file name="notification_banner" label="Notification Banner Ratio 4:1 (2000 x 500 px)"
                                preview="seller_notification_banner_preview" />
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-warning">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                dropdownParent: $('#userNotificationModal'),
                width: '100%'
            });

            /* ========= LOAD OPTION TYPES ========= */
            function loadSliderLinks() {
                let type = $('#notification_option_type').val();
                let businessCategoryId = $('#business_category_id').val();

                if (!type || !businessCategoryId) {
                    $('#notification_option_link').empty().trigger('change');
                    return;
                }

                $('#notification_option_link').select2('destroy');

                $('#notification_option_link').select2({
                    dropdownParent: $('#userNotificationModal'),
                    width: '100%',
                    placeholder: 'Select Option Types',
                    ajax: {
                        url: "{{ route('admin.user_seller.option_types') }}",
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                type: $('#notification_option_type').val(),
                                business_category_id: $('#business_category_id').val(),
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(item => ({
                                    id: item.id,
                                    text: item.name
                                }))
                            };
                        }
                    }
                });
            }

            $('#notification_option_type, #business_category_id').on('change', function() {
                $('#notification_option_link').val(null).trigger('change');
                loadSliderLinks();
            });

            /* ========= STORE NAME FOR SUB / CHILD ========= */
            $('#userNotificationForm').on('submit', function() {
                let type = $('#notification_option_type').val();
            });

            /* ========= ADD NOTIFICATION ========= */
            $('button[data-bs-target="#userNotificationModal"]').on('click', function() {
                $('#userNotificationForm')[0].reset();
                $('#user_method').val('POST');
                $('#user_notification_id').val('');
                $('#userModalTitle').text('User Notification');
                $('#userNotificationForm').attr('action', "{{ route('admin.user.notification.store') }}");
                $('#notification_option_link').empty().trigger('change');
                $('#notification_banner').attr('src', 'https://placehold.co/2000x500/f1f5f9/png');
            });

            /* ========= EDIT USER NOTIFICATION ========= */
            $(document).on('click', '.btn-edit-user', function() {
                const id = $(this).data('id');
                const businessCategory = $(this).data('business-category');
                const optionType = $(this).data('option-type');
                const optionLink = $(this).data('option-link');
                const optionName = $(this).data('option-name');
                const message = $(this).data('message');
                const thumbnail = $(this).data('thumbnail');

                $('#user_method').val('PUT');
                $('#user_notification_id').val(id);
                $('#userModalTitle').text('Edit User Notification');
                $('#userNotificationForm').attr('action', `/admin/user-notification/${id}/update`);

                $('#business_category_id').val(businessCategory).trigger('change');
                $('#notification_option_type').val(optionType).trigger('change');
                $('#user_message').val(message);
                $('#notification_banner').attr('src', thumbnail);

                // Load option link after option type is set
                setTimeout(() => {
                    $('#notification_option_link').empty()
                        .append(new Option(optionName, optionLink, true, true))
                        .trigger('change');

                    // Trigger option types load
                    loadSliderLinks();
                }, 500);

                $('#userNotificationModal').modal('show');
            });

            /* ========= RESET ON MODAL CLOSE ========= */
            $('#userNotificationModal').on('hidden.bs.modal', function() {
                $('#userNotificationForm')[0].reset();
                $('#user_method').val('POST');
                $('#user_notification_id').val('');
                $('#userModalTitle').text('User Notification');
                $('#userNotificationForm').attr('action', "{{ route('admin.user.notification.store') }}");
                $('#notification_option_link').empty().trigger('change');
                $('#notification_banner').attr('src', 'https://placehold.co/2000x500/f1f5f9/png');
                $('#forShop').prop('checked', false);
            });

            $('.select2Seller').select2({
                dropdownParent: $('#sellerNotificationModal'),
                width: '100%'
            });

            function syncSellerAudienceState() {
                const audience = $('#seller_audience').val();
                const shopDropdown = $('#seller_shop_id');
                const shouldEnableShop = audience === 'shop';

                if (!shouldEnableShop) {
                    shopDropdown.val(null).trigger('change');
                }

                shopDropdown.prop('disabled', !shouldEnableShop);
            }

            $('#seller_audience').on('change', function() {
                syncSellerAudienceState();
            });

            /* ========= ADD SELLER NOTIFICATION ========= */
            $('button[data-bs-target="#sellerNotificationModal"]').on('click', function() {
                $('#sellerNotificationForm')[0].reset();
                $('#seller_method').val('POST');
                $('#seller_notification_id').val('');
                $('#sellerModalTitle').text('Seller Notification');
                $('#sellerNotificationForm').attr('action',
                    "{{ route('admin.seller.notification.store') }}");
                $('#seller_notification_banner_preview').attr('src',
                    'https://placehold.co/2000x500/f1f5f9/png');
                $('#seller_audience').val('all').trigger('change');
                $('#seller_shop_id').empty().append(new Option('Select Seller Store', '', false, false)).trigger('change');
                syncSellerAudienceState();
            });

            /* ========= EDIT SELLER NOTIFICATION ========= */
            $(document).on('click', '.btn-edit-seller', function() {
                const id = $(this).data('id');
                const businessCategory = $(this).data('business-category');
                const shopId = $(this).data('shop-id');
                const sellerAudience = $(this).data('seller-audience') || 'all';
                const shopName = $(this).data('shop-name');
                const message = $(this).data('message');
                const thumbnail = $(this).data('thumbnail');

                $('#seller_method').val('PUT');
                $('#seller_notification_id').val(id);
                $('#sellerModalTitle').text('Edit Seller Notification');
                $('#sellerNotificationForm').attr('action', `/admin/seller-notification/${id}/update`);

                $('select[name="seller_business_category_id"]').val(businessCategory || '').trigger('change');
                $('#seller_audience').val(sellerAudience).trigger('change');
                $('#seller_message').val(message);
                $('#seller_notification_banner_preview').attr('src', thumbnail);

                // Load and set shop
                if (sellerAudience === 'shop' && shopId) {
                    $('#seller_shop_id').empty()
                        .append(new Option('Select Seller Store', '', false, false))
                        .append(new Option(shopName, shopId, true, true))
                        .trigger('change');
                } else {
                    $('#seller_shop_id').empty()
                        .append(new Option('Select Seller Store', '', false, false))
                        .trigger('change');
                }

                syncSellerAudienceState();

                $('#sellerNotificationModal').modal('show');
            });

            /* ========= RESET SELLER MODAL ON CLOSE ========= */
            $('#sellerNotificationModal').on('hidden.bs.modal', function() {
                $('#sellerNotificationForm')[0].reset();
                $('#seller_method').val('POST');
                $('#seller_notification_id').val('');
                $('#sellerModalTitle').text('Seller Notification');
                $('#sellerNotificationForm').attr('action',
                    "{{ route('admin.seller.notification.store') }}");
                $('#seller_notification_banner_preview').attr('src',
                    'https://placehold.co/2000x500/f1f5f9/png');
                $('#seller_audience').val('all').trigger('change');
                $('#seller_shop_id').empty().append(new Option('Select Seller Store', '', false, false)).trigger(
                    'change');
                syncSellerAudienceState();
            });

            $('#seller_shop_id').select2({
                dropdownParent: $('#sellerNotificationModal'),
                width: '100%',
                placeholder: "Select Seller Store",
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.seller.shops') }}",
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.name
                            }))
                        };
                    }
                }
            });

            $(document).on('click', '.btn-delete', function() {
                let form = $(this).closest('form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This notification will be deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'No',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

        });
    </script>
@endpush
