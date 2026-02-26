@extends('layouts.app')

@section('header-title', __('AI API Configuration'))

@section('content')
    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-xl-8 col-lg-9 m-auto">
                <form action="{{ route('admin.aiPrompt.configure.update') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-header py-3">
                            <h4 class="m-0">{{ __('AI API Configuration') }}</h4>
                        </div>
                        <div class="card-body pb-4">
                            <div class="mb-3">
                                <label class="form-label" for="provider">{{ __('AI Provider') }}</label>
                                <select name="provider" id="provider" class="form-control" required>
                                    <option value="openai"
                                        {{ (old('provider') ?? ($generaleSetting?->ai_provider ?? config('services.ai.provider', 'openai'))) === 'openai' ? 'selected' : '' }}>
                                        {{ __('OpenAI') }}
                                    </option>
                                    <option value="gemini"
                                        {{ (old('provider') ?? ($generaleSetting?->ai_provider ?? config('services.ai.provider', 'openai'))) === 'gemini' ? 'selected' : '' }}>
                                        {{ __('Gemini') }}
                                    </option>
                                </select>
                            </div>

                            <div id="openaiFields">
                                <div class="mb-3">
                                    <x-input type="text" name="api_key" label="OPENAI API KEY" placeholder="ADD OPENAI API KEY" :value="old('api_key', config('openai.api_key'))"/>
                                </div>

                                <div class="mb-4">
                                    <x-input type="text" name="organization" label="OPENAI ORGANIZATION" placeholder="ADD OPENAI ORGANIZATION" :value="old('organization', config('openai.organization'))"/>
                                </div>
                            </div>

                            <div id="geminiFields" class="d-none">
                                <div class="mb-3">
                                    <x-input type="text" name="gemini_api_key" label="GEMINI API KEY" placeholder="ADD GEMINI API KEY" :value="old('gemini_api_key', config('services.gemini.api_key'))"/>
                                </div>

                                <div class="mb-4">
                                    <x-input type="text" name="gemini_model" label="GEMINI MODEL" placeholder="EX: gemini-1.5-flash" :value="old('gemini_model', config('services.gemini.model'))"/>
                                </div>
                            </div>
                        </div>
                        @hasPermission('admin.aiPrompt.configure.update')
                        <div class="card-footer py-3 ">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary py-2">{{ __('Save And Update') }}</button>
                            </div>
                        </div>
                        @endhasPermission
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const providerSelect = document.getElementById('provider');
            const openaiFields = document.getElementById('openaiFields');
            const geminiFields = document.getElementById('geminiFields');
            const openaiApiKey = document.querySelector('input[name="api_key"]');
            const geminiApiKey = document.querySelector('input[name="gemini_api_key"]');

            function toggleProviderFields() {
                const provider = providerSelect.value;
                const isOpenAi = provider === 'openai';

                openaiFields.classList.toggle('d-none', !isOpenAi);
                geminiFields.classList.toggle('d-none', isOpenAi);

                if (openaiApiKey) {
                    openaiApiKey.required = isOpenAi;
                }

                if (geminiApiKey) {
                    geminiApiKey.required = !isOpenAi;
                }
            }

            providerSelect.addEventListener('change', toggleProviderFields);
            toggleProviderFields();
        });
    </script>
@endpush

