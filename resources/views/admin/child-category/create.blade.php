@extends('layouts.app')
@section('header-title', __('Create New Child Category'))

@section('content')
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            <i class="fa-solid fa-border-all"></i> {{__('Create New Child Category')}}
        </div>
    </div>
    <form action="{{ route('admin.business-category.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8 m-auto">
                <div class="card mt-3">
                    <div class="card-body">

                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-user"></i>
                            <h5>
                                {{__('Child Category Information')}}
                            </h5>
                        </div>
                        <!-- <select class="form-control select2" name="business_category_id" required></select>  -->
                        <select id="business_category" name="business_category_id" class="form-control select2" required>
                            <option value="">Select Business Category</option>
                            @foreach($businessCategories as $bc)
                                <option value="{{ $bc->id }}">{{ $bc->name }}</option>
                            @endforeach
                        </select>
                        <select id="category" name="category_id" class="form-control select2" required>
                            <option value="">Select Category</option>
                        </select> 
                        <select id="sub_category" name="sub_category_id" class="form-control select2" required>
                            <option value="">Select Sub Category</option>
                        </select>
                        <div class="mt-3">
                            <x-input label="Child Category Name" name="name" type="text" placeholder="Enter Name" required="true"/>
                        </div> 

                        <div class="mt-5 d-flex gap-2 justify-content-between">
                            <a href="{{ route('admin.category.index')}}" class="btn btn-secondary py-2 px-4">
                            {{__('Back')}}
                            </a>

                            <button type="submit" class="btn btn-primary py-2 px-4">
                                {{__('Submit')}}
                            </button>

                        </div>

                    </div>

                </div>
            </div>
        </div>

    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $('#business_category').on('change', function () {
        let businessCategoryId = $(this).val();

        $('#category').html('<option value="">Loading...</option>');
        $('#sub_category').html('<option value="">Select Sub Category</option>');

        if (businessCategoryId) {
            $.get("{{ url('admin/get-categories') }}/" + businessCategoryId, function (data) {
                let options = '<option value="">Select Category</option>';
                data.forEach(function (item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });
                $('#category').html(options);
            });
        }
    });


    $('#category').on('change', function () {
        let categoryId = $(this).val();

        $('#sub_category').html('<option value="">Loading...</option>');

        if (categoryId) {
            $.get("{{ url('admin/get-subcategories') }}/" + categoryId, function (data) {
                let options = '<option value="">Select Sub Category</option>';
                data.forEach(function (item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });
                $('#sub_category').html(options);
            });
        }
    });

});
</script>
@endpush
