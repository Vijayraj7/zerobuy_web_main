@extends('layouts.app')

@section('title', __('App Versions'))

@section('content')
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            <i class="bi bi-phone"></i> {{ __('App Versions') }}
        </div>
    </div>

    @php $generaleSetting = generaleSetting('setting'); @endphp

    <form action="{{ route('admin.app-version.update') }}" method="POST">
        @csrf

        <!--######## Download App Link ##########-->
        <div class="card mt-3">
            <div class="card-header d-flex align-items-center gap-2 py-3">
                <i class="bi bi-app-indicator"></i>
                <h5 class="mb-0">
                    {{ __('Download App Link') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-6">
                        <label for="" class="mb-1">
                            {{ __('Google PlayStore App Link') }}
                        </label>
                        <textarea name="google_playstore_url" class="form-control" rows="3"
                            placeholder="Enter Google PlayStore App Link">{{ $generaleSetting?->google_playstore_url }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="" class="mb-1">
                            {{ __('Apple Store App Link') }}
                        </label>
                        <textarea name="app_store_url" class="form-control" rows="3" placeholder="Enter Apple Store App Link">{{ $generaleSetting?->app_store_url }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="" class="mb-1">
                            {{ __('Seller App Google PlayStore Link') }}
                        </label>
                        <textarea name="seller_google_playstore_url" class="form-control" rows="3"
                            placeholder="Enter Seller App Google PlayStore Link">{{ $generaleSetting?->seller_google_playstore_url }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="" class="mb-1">
                            {{ __('Seller App Apple Store Link') }}
                        </label>
                        <textarea name="seller_app_store_url" class="form-control" rows="3"
                            placeholder="Enter Seller App Apple Store Link">{{ $generaleSetting?->seller_app_store_url }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!--######## App Versions ##########-->
        <div class="card mt-4">
            <div class="card-header d-flex align-items-center gap-2 py-3">
                <i class="bi bi-phone"></i>
                <h5 class="mb-0">{{ __('App Versions') }}</h5>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-6">
                        <x-input type="number" name="user_android_min_build" label="User App Android Min Build"
                            placeholder="Enter user app minimum Android build number" :value="$generaleSetting?->user_android_min_build" />
                    </div>
                    <div class="col-md-6">
                        <x-input type="number" name="user_ios_min_build" label="User App iOS Min Build"
                            placeholder="Enter user app minimum iOS build number" :value="$generaleSetting?->user_ios_min_build" />
                    </div>
                    <div class="col-md-6">
                        <x-input type="number" name="seller_android_min_build" label="Seller App Android Min Build"
                            placeholder="Enter seller app minimum Android build number" :value="$generaleSetting?->seller_android_min_build" />
                    </div>
                    <div class="col-md-6">
                        <x-input type="number" name="seller_ios_min_build" label="Seller App iOS Min Build"
                            placeholder="Enter seller app minimum iOS build number" :value="$generaleSetting?->seller_ios_min_build" />
                    </div>
                    <div class="col-12">
                        <small class="text-muted">
                            {{ __('If app build number is lower than these values, users will see a forced update bottom sheet in the apps.') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Save Changes') }}
            </button>
        </div>

    </form>
@endsection
