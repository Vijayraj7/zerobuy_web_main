<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
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
            // 'state_id' => 'required|exists:states,id',
            'product_id' => 'nullable|exists:products,id',
            'cart_id' => 'nullable|exists:carts,id',
            'quantity' => 'nullable|integer',
            'color' => 'nullable|exists:colors,id',
            'size' => 'nullable|exists:sizes,id',
            'unit' => 'nullable|string',
            'variant_id' => 'nullable|exists:product_variants,id',
            'bulk_item_id' => 'nullable|exists:product_bulk_items,id',
            'bulk_price_id' => 'nullable|exists:product_bulk_prices,id',
            'bulk_items' => 'nullable|array',
            'bulk_items.*.id' => 'required_with:bulk_items|exists:product_bulk_items,id',
            'bulk_items.*.buyqnty' => 'required_with:bulk_items|integer|min:1',
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
            'product_id.required' => __('The product id is required.'),
            'product_id.exists' => __('The selected product id is invalid.'),
            'quantity.integer' => __('The quantity must be an integer.'),
        ];
    }
}
