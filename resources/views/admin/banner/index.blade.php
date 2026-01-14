@extends('layouts.app')

@section('header-title', __('Banners'))
@section('header-subtitle', __('Manage Banners items'))

@section('content')
    <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
        <h4>{{ __('Banner List') }}</h4>

        @hasPermission('admin.banner.create')
            <!-- <a href="{{ route('admin.banner.create') }}" class="btn py-2 btn-primary">
                <i class="fa fa-plus-circle"></i>
                {{ __('Add Banner') }}
            </a> -->

            <a href="javascript:void(0);" class="btn py-2 btn-primary" data-bs-toggle="modal" data-bs-target="#bannerModal">
                <i class="fa fa-plus-circle"></i>
                {{ __('Add Banner') }}
            </a> 
        @endhasPermission
    </div>

    <div class="container-fluid mt-3">

        <div class="my-3 card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table border-left-right table-responsive-lg">
                        <thead>
                            <tr> 
                                <th>#</th>
                                <th>{{ __('Main Slider Category') }}</th>
                                <th>{{ __('Slider Position') }}</th>
                                <th>{{ __('Slider Type') }}</th>
                                <th>{{ __('Slider Link') }}</th>
                                <th>{{ __('Banner') }}</th>
                                @hasPermission('admin.banner.toggle')
                                    <th class="text-center">{{ __('Status') }}</th>
                                @endhasPermission
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        @forelse($banners as $banner)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $banner->businessCategory?->name }}
                                    @if ($businessModel != 'single' && $banner->shop_id)
                                        <br>
                                        <span class="badge bg-primary"><i class="fa-solid fa-store"></i>
                                            {{ $banner->shop?->name }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($banner->slider_position) }}</td>
                                <td>{{ str_replace('_',' ', ucfirst($banner->slider_type)) }}</td>
                                <td>{{ $banner->slider_link }}</td>
                                <td><img src="{{ $banner->thumbnail }}" height="76"></td> 
                                @hasPermission('admin.banner.toggle')
                                    <td class="text-center">
                                        <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                            data-bs-title="Status Toggle">
                                            <a href="{{ route('admin.banner.toggle', $banner->id) }}">
                                                <input type="checkbox" {{ $banner->status ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </a>
                                        </label>
                                    </td>
                                @endhasPermission

                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        @hasPermission('admin.banner.edit')
                                            <!-- <a href="{{ route('admin.banner.edit', $banner->id) }}" class="btn btn-outline-info btn-sm circleIcon">
                                                <img src="{{ asset('assets/icons-admin/edit.svg') }}" alt="edit" loading="lazy" />
                                            </a> -->

                                            <a href="javascript:void(0);" class="btn btn-outline-info btn-sm circleIcon editBtn" data-id="{{ $banner->id }}">
                                                <img src="{{ asset('assets/icons-admin/edit.svg') }}" alt="edit" loading="lazy" />
                                            </a>
                                        @endhasPermission

                                        @hasPermission('admin.banner.destroy')
                                            <a href="{{ route('admin.banner.destroy', $banner->id) }}"
                                                class="btn btn-outline-danger btn-sm deleteConfirm circleIcon">
                                                <img src="{{ asset('assets/icons-admin/trash.svg') }}" alt="delete" loading="lazy" />
                                            </a>
                                        @endhasPermission
                                    </div>
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
            </div>
        </div>

        <div class="my-3">
            {{ $banners->links() }}
        </div>

    </div>


    <div class="modal fade" id="bannerModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content"> 
                <form method="POST" method="POST" id="bannerForm" action="{{ route('admin.banner.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="method">
                    <div class="modal-header"> 
                        <h4 class="modal-title">Banner</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body row">
                        <div class="mb-3">
                            <x-select name="business_category_id" label="Business Category" class="select2 mb-3" required>
                                <option value="">--Select Business Category--</option>
                                @foreach($businessCategories as $bc)
                                <option value="{{ $bc->id }}">{{ $bc->name }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-3">
                            <x-select name="slider_position" label="Position" required>
                                <option value="">--Select Slider Position--</option>
                                <option value="top">Top</option>
                                <option value="center">Center</option>
                                <option value="bottom">Bottom</option>
                            </x-select>
                        </div>

                        <div class="mb-3">
                            <x-select name="slider_type" label="Slider Type" id="slider_type">
                                <option value="">--Select Slider Type--</option>
                                <option value="sub_category">Sub Category</option>
                                <option value="child_category">Child Category</option>
                                <option value="product">Product</option>
                                <option value="shop">Store</option>
                            </x-select>
                        </div>

                        <div class="mb-3">
                            <x-select name="slider_link" label="Slider Link" id="slider_link" class="select2"></x-select>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="ratio4x1">
                                    <img src="https://placehold.co/2000x500/f1f5f9/png" id="banner" alt="" width="100%">
                                </div>
                            </div>
                            <x-file name="banner" label="Banner Ratio 4:1 (2000 x 500 px) *" preview="banner" />
                        </div>

                        <!-- <x-file name="banner" label="Banner Image" /> -->

                        @if ($businessModel != 'single')
                            <div class="mt-4 border d-inline-flex align-items-center justify-content-center gap-2 p-2 rounded">
                                <label for="forShop" class="form-label mb-0 fw-bold">
                                    {{__('This Banner For Own Shop')}}
                                </label>
                                <input type="checkbox" name="for_shop" id="forShop" style="width: 20px; height: 20px" class="form-check-input m-0" />
                            </div>
                        @endif

                    </div> 
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {

            $('.select2').select2({
                dropdownParent: $('#bannerModal'),
                width: '100%'
            });

            /* ========= LOAD SLIDER LINKS ========= */
            function loadSliderLinks() {
                let type = $('#slider_type').val();
                let businessCategoryId = $('#business_category_id').val();

                if (!type || !businessCategoryId) {
                    $('#slider_link').empty().trigger('change');
                    return;
                }

                $('#slider_link').select2('destroy');

                $('#slider_link').select2({
                    dropdownParent: $('#bannerModal'),
                    width: '100%',
                    placeholder: 'Select slider link',
                    ajax: {
                        url: "{{ route('admin.banner.slider.options') }}",
                        dataType: 'json',
                        delay: 300,
                        // data: function () {
                        //     return {
                        //         type: type,
                        //         business_category_id: businessCategoryId
                        //     };
                        // },
                        data: function (params) {
                            return {
                                type: $('#slider_type').val(),
                                business_category_id: $('#business_category_id').val(),
                                search: params.term   // ✅ VERY IMPORTANT
                            };
                        },
                        processResults: function (data) {
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

            $('#slider_type, #business_category_id').on('change', function () {
                $('#slider_link').val(null).trigger('change');
                loadSliderLinks();
            });

            /* ========= STORE NAME FOR SUB / CHILD ========= */
            $('#bannerForm').on('submit', function () {
                let type = $('#slider_type').val();

                // if (type === 'sub_category' || type === 'child_category') {
                //     let selected = $('#slider_link').select2('data')[0];
                //     if (selected) {
                //         $('<input>').attr({
                //             type: 'hidden',
                //             name: 'slider_link',
                //             value: selected.text
                //         }).appendTo('#bannerForm');
                //     }
                // }
            });

            /* ========= ADD BANNER ========= */
            $('button[data-bs-target="#bannerModal"]').on('click', function () {
                $('#bannerForm')[0].reset();
                $('#method').val('');
                $('#bannerForm').attr('action', "{{ route('admin.banner.store') }}");
                $('#slider_link').empty().trigger('change');
                $('#banner').attr('src', 'https://placehold.co/2000x500/f1f5f9/png');
            });

            /* ========= EDIT BANNER ========= */
            $('.editBtn').on('click', function () {
                let id = $(this).data('id');

                $.get(`/admin/banner/${id}`, function (res) {

                    $('#bannerForm').attr('action', `/admin/promotional-banner/${id}/update`);
                    $('#method').val('PUT');

                    $('#business_category_id').val(res.business_category_id).trigger('change');
                    $('#slider_position').val(res.slider_position).trigger('change');
                    $('#slider_type').val(res.slider_type).trigger('change');

                    $('#banner').attr('src', res.thumbnail ?? 'https://placehold.co/2000x500/f1f5f9/png');
                    $('#forShop').prop('checked', !!res.shop_id);

                    setTimeout(function () {
                        loadSliderLinks();

                        let option = new Option(
                            res.slider_link,
                            res.slider_link,
                            true,
                            true
                        );

                        $('#slider_link').append(option).trigger('change');
                    }, 500);

                    $('#bannerModal').modal('show');
                });
            });

            /* ========= RESET ON MODAL CLOSE ========= */
            $('#bannerModal').on('hidden.bs.modal', function () {
                $('#bannerForm')[0].reset();
                $('#method').val('');
                $('#bannerForm').attr('action', "{{ route('admin.banner.store') }}");
                $('#slider_link').empty().trigger('change');
                $('#banner').attr('src', 'https://placehold.co/2000x500/f1f5f9/png');
                $('#forShop').prop('checked', false);
            });

        });
    </script>
@endpush 
