<?php

namespace App\Http\Requests;

use App\Models\VerifyManage;
use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class ShopCreateRequest extends FormRequest
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
        $user = null;
        $required = 'required';
        // if ($this->routeIs('admin.shop.update')) {
        //     $user = $this->shop?->user;
        //     $required = 'nullable';
        // }

        $isUpdate = $this->routeIs('admin.shop.update'); // detect update
        $user = $isUpdate ? $this->shop?->user : null;

        $required = $isUpdate ? 'nullable' : 'required'; // only required for create

        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
        });

        $phoneRequired = $verifyManage?->phone_required ? 'required' : 'nullable';
        $phoneRequired = $verifyManage ? $phoneRequired : 'required';

        $min = $verifyManage?->phone_min_length ?? 9;
        $max = $verifyManage?->phone_max_length ?? 16;

        $phoneValidate = [$phoneRequired, 'min_digits:'.$min, 'max_digits:'.$max, 'unique:users,phone,'.$user?->id];

        // validation rules
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => [$required, 'string', 'max:15', 'min:10', 'unique:users,phone,'.$user?->id],
            // 'phone' => $phoneValidate,
            'email' => [$isUpdate ? 'nullable' : 'required', 'string', 'email', 'max:255', 'unique:users,email,'.$user?->id, new EmailRule],
            'password' => [$isUpdate ? 'nullable' : 'required', 'min:6', 'confirmed'],
            'password_confirmation' => [$isUpdate ? 'nullable' : 'required', 'min:6'],
            'address' => ['required', 'string', 'max:150'],

            // Shop images not required on update
            'profile_photo' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,png,jpeg,gif', 'max:2048'],
            'shop_logo' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,png,jpeg,gif', 'max:2048'],
            'shop_banner' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,png,jpeg,gif', 'max:2048'],

            'shop_name' => ['required', 'string', 'max:100'],
            'store_type' => ['required'],
            'whatsapp_number' => ['required', 'string', 'max:15', 'min:10'],
            'state_id'    => ['required', 'exists:states,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'pincode' => ['required', 'string', 'max:10'],
            'min_order_amount' => ['required'],
            'return_policy' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:200'],
            'store_since' => ['required', 'string'],
            'gst_number' => ['nullable', 'string'],

            'bussiness_categories_id' => ['required', 'array', 'min:1'],
            'bussiness_categories_id.*' => ['required', 'exists:business_categories,id'],
            // 'terms_condition_status' => [$isUpdate ? 'nullable' : 'required', 'in:1'],

            'delivery_days' => ['required', 'string'],
            'delivery_state_ids' => ['required', 'array', 'min:1'],
            'delivery_state_ids.*' => ['required', 'integer', 'exists:states,id'],
            'delivery_mode' => ['required', 'in:amount_based,state_wise,manual,provider_api'],
            'delivery_provider' => ['nullable', 'required_if:delivery_mode,provider_api', 'in:shiprocket,delhivery'],
            'provider_api_key' => ['nullable', 'required_if:delivery_mode,provider_api', 'string', 'max:255'],
            'provider_api_secret' => ['nullable', 'required_if:delivery_provider,shiprocket', 'string', 'max:255'],

            'online_payment_enabled' => ['nullable', 'boolean'],
            'cash_on_delivery_enabled' => ['nullable', 'boolean'],
            'online_payment_provider' => ['nullable', 'required_if:online_payment_enabled,1', 'in:razorpay'],
            'razorpay_key_id' => ['nullable', 'required_if:online_payment_provider,razorpay', 'string', 'max:255'],
            'razorpay_key_secret' => ['nullable', 'required_if:online_payment_provider,razorpay', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $request = request();
        if ($request->is('api/*')) {
            $header = strtolower($request->header('accept-language'));
            $lan = (preg_match('/^[a-z]+$/', $header)) ? $header : 'en';
            app()->setLocale($lan);
        }

        return [
            'first_name.required' => __('The first name field is required.'),
            'phone.required' => __('The phone field is required.'),
            'phone.unique' => __('The phone has already been taken.'),
            'email.required' => __('The email field is required.'),
            'email.unique' => __('The email has already been taken.'),
            'password.required' => __('The password field is required.'),
            'password.min' => __('The password must be at least 6 characters.'),
            'password.confirmed' => __('The password and confirmation password do not match.'),
            'profile_photo.image' => __('The profile photo must be an image.'),
            'profile_photo.max' => __('The profile photo must not be greater than 2 MB.'),
            'shop_name.required' => __('The shop name field is required.'),
            'shop_logo.image' => __('The shop logo must be an image.'),
            'shop_logo.max' => __('The shop logo must not be greater than 2 MB.'),
            'shop_banner.image' => __('The shop banner must be an image.'),
            'shop_banner.max' => __('The shop banner must not be greater than 2 MB.'),
            'description.max' => __('The description may not be greater than 200 characters.'),
            'password_confirmation.min' => __('The password confirmation must be at least 6 characters.'),
            'password_confirmation.required' => __('The password confirmation field is required.'),
            'address.max' => __('The address may not be greater than 255 characters.'),

            // ✅ Add custom messages for multi-select/checkbox fields
            'bussiness_categories_id.required' => __('Please select at least one business category.'),
            'bussiness_categories_id.array' => __('Invalid business category selection.'),
            'bussiness_categories_id.min' => __('Please select at least one business category.'),
            'bussiness_categories_id.*.exists' => __('Selected business category is invalid.'),

            'delivery_state_ids.required' => __('Please select at least one delivery state.'),
            'delivery_state_ids.array' => __('Invalid delivery state selection.'),
            'delivery_state_ids.min' => __('Please select at least one delivery state.'),
            'delivery_state_ids.*.exists' => __('Selected delivery state is invalid.'),

            'delivery_mode.required' => __('Please select a delivery charge method.'),
            'delivery_mode.in' => __('Selected delivery charge method is invalid.'),
            'delivery_provider.required_if' => __('Please select a delivery API provider.'),
            'delivery_provider.in' => __('Selected delivery API provider is invalid.'),
            'provider_api_key.required_if' => __('The provider API key field is required for API delivery mode.'),
            'provider_api_secret.required_if' => __('The provider API secret field is required for API delivery mode.'),

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
