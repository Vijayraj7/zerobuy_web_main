@extends('layouts.app')

@section('content')
<!-- some changes aaded by ancy -->
    <div class="container-fluid my-md-0 my-4">
        <form action="{{ route('admin.customer.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="row h-100vh">
                <div class="col-12 m-auto">
                    <div class="card rounded-12 border-0 shadow-md">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                            <h3 class="m-0">{{ __('Add Customer') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-7">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mt-3">
                                                <x-input label="First Name" name="name" type="text"
                                                    placeholder="Enter Name" required="true" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mt-3">
                                            <x-input label="Phone Number" name="phone" type="number" placeholder="Enter phone number" required="true"/>
                                        </div>
                                        <div class="col-md-6 mt-3">
                                            <x-input type="email" name="email" label="Email" placeholder="Enter Email Address" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mt-3">
                                            <x-input type="password" name="password" label="Password" placeholder="Enter Password" required="true" />
                                        </div>

                                        <div class="col-md-6 mt-3">
                                            <x-input type="password" name="password_confirmation" label="Confirm Password"
                                                placeholder="Enter Confirm Password" required="true" />
                                        </div>
                                    </div>
                                    <!-- <div class="row">
                                        <div class="col-md-6 mt-3">
                                            <x-select label="Gender" name="gender">
                                                <option value="male">
                                                    {{ __('Male') }}
                                                </option>
                                                <option value="female">
                                                    {{ __('Female') }}
                                                </option>
                                            </x-select>
                                        </div>
                                        <div class="col-md-6 mt-3">
                                            <x-input type="date" name="date_of_birth" label="Date of Birth" placeholder="Enter Date of Birth" />
                                        </div>
                                    </div> -->  
                                </div>

                                <div class="col-lg-5">
                                    <div class="mt-3 d-flex align-items-center justify-content-center">
                                        <div class="ratio1x1">
                                            <img id="previewProfile" src="https://placehold.co/500x500/png" alt="photo" width="100%">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <x-file name="profile_photo" label="User profile (Ratio 1:1)"
                                            preview="previewProfile" :required="true" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mt-3 col-md-5">
                                        <x-input label="Address Line 1" name="address_line" placeholder="Enter Address Line 1" required="true" />
                                    </div>

                                    <div class="mt-3 col-md-5">
                                        <x-input label="Address Line 2" name="address_line2" placeholder="Enter Address Line 2" />
                                    </div>

                                    <div class="mt-3 col-md-2">
                                        <x-select label="State" name="state" required="true">
                                            <option value="">{{ __('Select State') }}</option>
                                            @foreach($states as $id => $sname)
                                                <option value="{{ $id }}" {{ old('state') == $id ? 'selected' : '' }}>{{ $sname }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mt-3 col-md-3">
                                        <x-select label="District" name="area" id="district" required="true">
                                        </x-select>
                                    </div>

                                    <div class="mt-3 col-md-3">
                                        <x-input label="Post Code" name="post_code" placeholder="Enter Post Code" required="true" />
                                    </div>

                                    <div class="mt-3 col-md-3">
                                        <x-select label="Address Type" name="address_type" required="true">
                                            <option value="home">Home</option>
                                            <option value="shop">Shop</option>
                                            <option value="other">Other</option>
                                        </x-select>
                                    </div>

                                    <div class="mt-3 col-md-3">
                                        <x-select label="Default Address?" name="is_default">
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </x-select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('admin.customer.index') }}" class="btn btn-lg btn-outline-secondary">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-lg btn-primary">
                                {{ __('Submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stateSelect = document.getElementById('state');
        const districtSelect = document.getElementById('district');

        if (!stateSelect || !districtSelect) {
            return;
        }

        const districts = @json($districts);
        const selectedDistrict = @json(old('area'));

        function populateDistricts(stateId) {
            districtSelect.innerHTML = '';
            districtSelect.disabled = !stateId;

            if (!stateId) {
                return;
            }

            districts
                .filter((district) => String(district.state_id) === String(stateId))
                .forEach((district) => {
                    const option = document.createElement('option');
                    option.value = district.name;
                    option.textContent = district.name;
                    if (selectedDistrict && selectedDistrict === district.name) {
                        option.selected = true;
                    }
                    districtSelect.appendChild(option);
                });

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery(districtSelect).trigger('change.select2');
            }
        }

        populateDistricts(stateSelect.value);

        stateSelect.addEventListener('change', function () {
            populateDistricts(this.value);
        });
    });
</script>
@endpush
