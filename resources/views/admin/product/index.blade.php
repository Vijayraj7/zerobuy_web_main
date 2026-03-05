@extends('layouts.app')
@section('header-title', __('Product List'))

@section('content')
    <div>
        <h4>{{ __('Product List') }}</h4>
    </div>

    <form action="" method="GET" class="card card-body">

        @if (request('approve'))
            <input type="hidden" name="approve" value="{{ request('approve') }}">
        @else
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="row">
            <div class="col-lg-4 col-md-6 mb-3">
                <x-select label="Shop" name="shop">
                    <option value="">
                        {{ __('All Shop') }}
                    </option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <x-select label="Business Category" name="business_category" id="businessCategoryFilter">
                    <option value="">{{ __('All Business Categories') }}</option>
                    @foreach ($businessCategories as $businessCategory)
                        <option value="{{ $businessCategory->id }}"
                            {{ request('business_category') == $businessCategory->id ? 'selected' : '' }}>
                            {{ $businessCategory->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <x-select label="Main Category" name="category" id="mainCategoryFilter">
                    <option value="">{{ __('All Main Categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" data-business-id="{{ $category->business_category_id }}"
                            {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <x-select label="Sub Category" name="sub_category" id="subCategoryFilter">
                    <option value="">{{ __('All Sub Categories') }}</option>
                    @foreach ($subCategories as $subCategory)
                        <option value="{{ $subCategory->id }}"
                            data-business-id="{{ $subCategory->business_category_id }}"
                            data-category-id="{{ $subCategory->category_id }}"
                            {{ request('sub_category') == $subCategory->id ? 'selected' : '' }}>
                            {{ $subCategory->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <x-select label="Child Category" name="child_category" id="childCategoryFilter">
                    <option value="">{{ __('All Child Categories') }}</option>
                    @foreach ($childCategories as $childCategory)
                        <option value="{{ $childCategory->id }}"
                            data-business-id="{{ $childCategory->business_category_id }}"
                            data-category-id="{{ $childCategory->category_id }}"
                            data-sub-category-id="{{ $childCategory->sub_category_id }}"
                            {{ request('child_category') == $childCategory->id ? 'selected' : '' }}>
                            {{ $childCategory->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>
        </div>

        <div class="mt-2 d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.product.index', [
                'status' => request()->query('status'),
                'approve' => request()->query('approve'),
            ]) }}"
                class="btn btn-light py-2 px-4">
                {{ __('Reset') }}
            </a>
            <button type="submit" class="btn btn-primary py-2 px-4">
                {{ __('Filter Data') }}
            </button>
        </div>
    </form>

    <div class="container-fluid mt-3">

        <div class="mb-3 card">
            <div class="card-body">
                <div class="mb-2 d-flex justify-content-end align-items-center gap-2">
                    <span class="fw-semibold">
                        {{ request('approve') ? __('Total Accepted Products') : __('Total Products') }}:
                    </span>
                    <span id="totalProductCount" class="badge text-bg-primary">0</span>
                </div>
                <div class="table-responsive">
                    <table class="table border-left-right table-responsive-lg datatableCustomCSS" id="productTable">
                        <thead>
                            <tr>
                                <!-- <th>{{ __('SL') }}.</th>
                                <th>{{ __('Thumbnail') }}</th>
                                <th style="min-width: 150px">{{ __('Product Name') }}</th>
                                <th style="min-width: 100px">{{ __('Shop') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th style="min-width: 120px">{{ __('Discount Price') }}</th>
                                <th>{{ __('Action') }}</th> -->

                                <th>SL</th>
                                <th>Create Date</th>
                                <th>Product Code</th>
                                <th>Store Code</th>
                                <th>Store Name</th>
                                <th>Product Name</th>
                                <th>Image</th>
                                <th>Qty</th>
                                <th>MRP</th>
                                <th>Selling Price</th>
                                <th>Total Sales</th>
                                <th>Variants</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead> 
                    </table>
                </div>
            </div>
        </div> 

        <form action="" method="POST" class="d-none" id="deleteForm">
            @csrf
            @method('DELETE')
        </form>

    </div>
@endsection

@push('scripts')
    <script> 

        const linkedFilterSelectors = [
            '#businessCategoryFilter',
            '#mainCategoryFilter',
            '#subCategoryFilter',
            '#childCategoryFilter'
        ];

        linkedFilterSelectors.forEach(function(selector) {
            const $element = $(selector);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.select2('destroy');
            }
            $element.removeClass('select2');
        });

        const productTable = $('#productTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('admin.product.index') }}",
                data: function (d) {
                    d.shop    = $('select[name=shop]').val();
                    d.business_category = $('select[name=business_category]').val();
                    d.category = $('select[name=category]').val();
                    d.sub_category = $('select[name=sub_category]').val();
                    d.child_category = $('select[name=child_category]').val();
                    d.status  = "{{ request('status') }}";
                    d.approve = "{{ request('approve') }}";
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable:false, searchable:false },
                { data: 'created_date' },
                { data: 'product_code' },
                { data: 'store_code' },
                { data: 'shop', name:'shop.name' },
                {
                    data: 'name',
                    name:'name',
                    render: function (data) {
                        if (!data) {
                            return '';
                        }

                        return data.length > 35 ? `${data.substring(0, 35)}...` : data;
                    }
                },
                { data: 'thumbnail', orderable:false, searchable:false },
                { data: 'quantity' },
                { data: 'mrp' },
                { data: 'selling_price' }, 
                { data: 'total_sale_count', orderable:false, searchable:false },
                { data: 'variants_count', orderable:false, searchable:false },
                { data: 'status', orderable:false, searchable:false },
                { data: 'action', orderable:false, searchable:false }
            ]
        });

        productTable.on('xhr.dt', function (e, settings, json) {
            const totalCount = (json && typeof json.recordsTotal !== 'undefined') ? json.recordsTotal : 0;
            $('#totalProductCount').text(totalCount);
        });


        $(document).on("click", ".confirmApprove", function(e) {
            e.preventDefault();
            const url = $(this).attr("href");
            Swal.fire({
                title: "Are you sure?",
                text: "You want to approve this product",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, Approve it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });

        const confirmDeny = (url) => {
            const form = document.getElementById('deleteForm');
            form.action = url;
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete this product! If you confirm, it will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Yes, Delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        $(document).on('change', '.toggle-status', function () {
            let id = $(this).data('id');

            $.ajax({
                url: "{{ route('admin.product.status') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function (res) {
                    if (res.success) {
                        toastr.success('Status updated successfully');
                    }
                },
                error: function () {
                    toastr.error('Something went wrong');
                }
            });
        });

        const businessSelect = $('#businessCategoryFilter');
        const categorySelect = $('#mainCategoryFilter');
        const subCategorySelect = $('#subCategoryFilter');
        const childCategorySelect = $('#childCategoryFilter');

        const readOptions = ($select) =>
            $select.find('option').map(function() {
                return {
                    value: $(this).attr('value') ?? '',
                    text: $(this).text(),
                    businessId: ($(this).data('business-id') ?? '').toString(),
                    categoryId: ($(this).data('category-id') ?? '').toString(),
                    subCategoryId: ($(this).data('sub-category-id') ?? '').toString(),
                };
            }).get();

        const businessOptions = readOptions(businessSelect);
        const categoryOptions = readOptions(categorySelect);
        const subCategoryOptions = readOptions(subCategorySelect);
        const childCategoryOptions = readOptions(childCategorySelect);

        const hasValue = (value) => value !== null && value !== undefined && String(value) !== '';

        const renderOptions = ($select, options, selectedValue) => {
            const placeholder = options.find((option) => !hasValue(option.value));
            const placeholderOption = placeholder || { value: '', text: 'All' };

            $select.empty();
            $select.append(new Option(placeholderOption.text, placeholderOption.value));

            options
                .filter((option) => hasValue(option.value))
                .forEach((option) => {
                    $select.append(new Option(option.text, option.value));
                });

            $select.val(selectedValue && options.some((option) => String(option.value) === String(selectedValue))
                ? String(selectedValue)
                : '');
        };

        function updateCategoryFilters() {
            let businessId = (businessSelect.val() || '').toString();
            let categoryId = (categorySelect.val() || '').toString();
            let subCategoryId = (subCategorySelect.val() || '').toString();
            let childCategoryId = (childCategorySelect.val() || '').toString();

            const maxPass = 4;
            for (let i = 0; i < maxPass; i++) {
                const selectedCategory = categoryOptions.find((option) => option.value === categoryId);
                const selectedSubCategory = subCategoryOptions.find((option) => option.value === subCategoryId);
                const selectedChildCategory = childCategoryOptions.find((option) => option.value === childCategoryId);

                const availableBusinesses = businessOptions;

                if (hasValue(businessId) && !availableBusinesses.some((option) => option.value === businessId)) {
                    businessId = '';
                    continue;
                }

                const availableCategories = categoryOptions.filter((option) => {
                    if (!hasValue(option.value)) return true;
                    const matchBusiness = !hasValue(businessId) || option.businessId === businessId;
                    const matchSubCategory = !hasValue(subCategoryId) || (selectedSubCategory && option.value === selectedSubCategory.categoryId);
                    const matchChildCategory = !hasValue(childCategoryId) || (selectedChildCategory && option.value === selectedChildCategory.categoryId);
                    return matchBusiness && matchSubCategory && matchChildCategory;
                });

                if (hasValue(categoryId) && !availableCategories.some((option) => option.value === categoryId)) {
                    categoryId = '';
                    continue;
                }

                const availableSubCategories = subCategoryOptions.filter((option) => {
                    if (!hasValue(option.value)) return true;
                    const matchBusiness = !hasValue(businessId) || option.businessId === businessId;
                    const matchCategory = !hasValue(categoryId) || option.categoryId === categoryId;
                    const matchChildCategory = !hasValue(childCategoryId) || (selectedChildCategory && option.value === selectedChildCategory.subCategoryId);
                    return matchBusiness && matchCategory && matchChildCategory;
                });

                if (hasValue(subCategoryId) && !availableSubCategories.some((option) => option.value === subCategoryId)) {
                    subCategoryId = '';
                    continue;
                }

                const availableChildCategories = childCategoryOptions.filter((option) => {
                    if (!hasValue(option.value)) return true;
                    const matchBusiness = !hasValue(businessId) || option.businessId === businessId;
                    const matchCategory = !hasValue(categoryId) || option.categoryId === categoryId;
                    const matchSubCategory = !hasValue(subCategoryId) || option.subCategoryId === subCategoryId;
                    return matchBusiness && matchCategory && matchSubCategory;
                });

                if (hasValue(childCategoryId) && !availableChildCategories.some((option) => option.value === childCategoryId)) {
                    childCategoryId = '';
                    continue;
                }

                renderOptions(businessSelect, availableBusinesses, businessId);
                renderOptions(categorySelect, availableCategories, categoryId);
                renderOptions(subCategorySelect, availableSubCategories, subCategoryId);
                renderOptions(childCategorySelect, availableChildCategories, childCategoryId);
                break;
            }
        }

        $('#businessCategoryFilter, #mainCategoryFilter, #subCategoryFilter, #childCategoryFilter').on('change', function() {
            updateCategoryFilters();
        });

        updateCategoryFilters();

    </script>
@endpush
