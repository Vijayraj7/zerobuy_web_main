<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentSettingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cash_on_delivery_enabled' => 'nullable|boolean',
            'online_payment_enabled' => 'nullable|boolean',
            'online_payment_provider' => 'nullable|required_if:online_payment_enabled,1|string|in:razorpay',
            'razorpay_key_id' => 'nullable|required_if:online_payment_provider,razorpay|string|max:255',
            'razorpay_key_secret' => 'nullable|required_if:online_payment_provider,razorpay|string|max:255',
        ];
    }

    public function messages()
    {
        $request = request();
        if ($request->is('api/*')) {
            $header = strtolower($request->header('accept-language'));
            $lan = (preg_match('/^[a-z]+$/', $header)) ? $header : 'en';
            app()->setLocale($lan);
        }

        return [
            'online_payment_provider.required_if' => __('Please select an online payment provider.'),
            'online_payment_provider.in' => __('Selected online payment provider is invalid.'),
            'cash_on_delivery_enabled.boolean' => __('Cash on Delivery toggle value is invalid.'),
            'razorpay_key_id.required_if' => __('The Razorpay key ID field is required when Razorpay is selected.'),
            'razorpay_key_secret.required_if' => __('The Razorpay key secret field is required when Razorpay is selected.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $onlineEnabled = (bool) $this->boolean('online_payment_enabled');
            $cashEnabled = $this->has('cash_on_delivery_enabled')
                ? (bool) $this->boolean('cash_on_delivery_enabled')
                : true;

            if (! $onlineEnabled && ! $cashEnabled) {
                $validator->errors()->add(
                    'cash_on_delivery_enabled',
                    __('Either Cash on Delivery or Online Payment must be enabled.')
                );
            }
        });
    }
}
