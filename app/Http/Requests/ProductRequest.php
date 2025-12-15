<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        // $additionThumbnail = $this->product?->medias?->isNotEmpty() ? 'nullable' : 'nullable';
        // $thumbnail = $this->product?->media ? 'nullable' : 'required';
        // if ($this->is('api/*')) {
        //     $additionThumbnail = 'nullable';
        // }
        // return [
        //     'name' => 'required|string|max:191',
        //     'description' => 'required|string',
        //     'short_description' => 'required|string|max:191',
        //     'category' => 'required|exists:categories,id',
        //     'sub_category' => 'nullable|array|exists:sub_categories,id',
        //     'brand' => 'nullable|exists:brands,id',
        //     'code' => 'required|numeric|digits_between:5,25',
        //     'color' => 'nullable|array',
        //     'size' => 'nullable|array',
        //     'size.*.id' => 'nullable|exists:sizes,id',
        //     'size.*.price' => 'nullable|numeric|min:0',
        //     'unit' => 'nullable|exists:units,id',
        //     'buy_price' => 'nullable|numeric|min:0',
        //     'price' => 'required|numeric|min:0',
        //     'discount_price' => 'nullable|numeric|min:0|max:'.$this->price,
        //     'quantity' => 'required|integer|min:0',
        //     'min_order_quantity' => 'nullable|integer|min:0',
        //     'meta_title' => 'nullable|string|max:191',
        //     'meta_description' => 'nullable|string|max:200',
        //     'thumbnail' => "$thumbnail|image|mimes:png,jpg,jpeg,webp|max:2048",
        //     'additionThumbnail' => "$additionThumbnail|array",
        //     'additionThumbnail.*' => 'image|mimes:png,jpg,jpeg,webp|max:2048',

        //     'previousThumbnail' => 'nullable|array',
        //     'previousThumbnail.*.id' => 'nullable|exists:media,id',
        //     'previousThumbnail.*.file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
        // ];

        /////////////////// this added by ancy //////////////////////////
        //-------------------------------------------------------------//
        $thumbnail = $this->product?->media ? 'nullable' : 'required';

        if ($this->is('api/*')) {
            $thumbnail = 'nullable';
        }

        return [ 
            'condition_status'   => 'required|string',
            'name'               => 'required|string|max:191',
            'description'        => 'required|string', 

            'main_category'      => 'required|exists:categories,id',
            'sub_categories'     => 'nullable|array',
            'sub_categories.*'   => 'exists:sub_categories,id',
            // 'child_categories'   => 'nullable|array',
            // 'child_categories.*' => 'exists:child_categories,id', 

            'quantity'           => 'required|integer|min:1',
            'min_order_quantity' => 'nullable|integer|min:1',
            'return_period'      => 'nullable|integer',
            'mrp'                => 'required|numeric|min:0',
            'selling_price'      => 'nullable|numeric|min:0',
            'tax_percentage'     => 'nullable|numeric|min:0', 

            'item_details'         => 'nullable|array',
            'item_details.*.name'  => 'nullable|string|max:191',
            'item_details.*.value' => 'nullable|string|max:191',


            'variants'           => 'nullable|array',
            'variants.*.size_id'  => 'nullable|exists:sizes,id',
            'variants.*.color_id' => 'nullable|exists:colors,id',
            'variants.*.price'    => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',

            'bulk'               => 'nullable|array',
            'bulk.*.min_qty'     => 'required_with:bulk|integer|min:1',
            'bulk.*.max_qty'     => 'required_with:bulk|integer|min:1',
            'bulk.*.price'       => 'required_with:bulk|numeric|min:0',

            'thumbnail'          => "$thumbnail|image|mimes:png,jpg,jpeg,webp|max:2048",
            'additional_images'   => 'nullable|array',
            'additional_images.*' => 'image|mimes:png,jpg,jpeg,webp|max:2048', 

            'video_type'         => 'nullable|string|in:youtube,external',
            'video_link'         => 'nullable|string',

            'meta_title'       => 'nullable|string|max:191',
            'meta_description' => 'nullable|string|max:200',
            'meta_keywords'    => 'nullable|array',
            'meta_keywords.*'  => 'nullable|string|max:50',
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

        // return [
        //     'name.required' => __('The name field is required.'),
        //     'name.max' => __('The name may not be greater than 191 characters.'),
        //     'description.required' => __('The description field is required.'),
        //     'short_description.required' => __('The short description field is required.'),
        //     'short_description.max' => __('The short description may not be greater than 191 characters.'),
        //     'category.required' => __('The category field is required.'),
        //     'category.exists' => __('The selected category is invalid.'),
        //     'code.required' => __('The code field is required.'),
        //     'code.unique' => __('The code has already been taken.'),
        //     'code.numeric' => __('The code must be a number.'),
        //     'code.digits_between' => __('The code must be 5-7 digits.'),
        //     'price.required' => __('The price field is required.'),
        //     'price.numeric' => __('The price must be a number.'),
        //     'price.min' => __('The price must be at least 0.'),
        //     'discount_price.numeric' => __('The discount price must be a number.'),
        //     'discount_price.min' => __('The discount price must be at least 0.'),
        //     'discount_price.max' => __('The discount price must be less than price.'),
        //     'quantity.required' => __('The quantity field is required.'),
        //     'quantity.integer' => __('The quantity must be an integer.'),
        //     'quantity.min' => __('The quantity must be at least 0.'),
        //     'min_order_quantity.required' => __('The min order quantity field is required.'),
        //     'min_order_quantity.integer' => __('The min order quantity must be an integer.'),
        //     'min_order_quantity.min' => __('The min order quantity must be at least 0.'),
        //     'thumbnail.required' => __('The thumbnail field is required.'),
        //     'thumbnail.image' => __('The thumbnail must be an image.'),
        //     'thumbnail.mimes' => __('The thumbnail must be a file of type: png, jpg, jpeg, webp.'),
        //     'thumbnail.max' => __('The thumbnail may not be greater than 2048 kilobytes.'),
        //     'additionThumbnail.required' => __('The addition thumbnail field is required.'),
        //     'additionThumbnail.image' => __('The addition thumbnail must be an image.'),
        //     'additionThumbnail.mimes' => __('The addition thumbnail must be a file of type: png, jpg, jpeg, webp.'),
        //     'additionThumbnail.max' => __('The addition thumbnail may not be greater than 2048 kilobytes.'),
        // ];

        //////////////////////////added by ancy////////////////////////////
        //---------------------------------------------------------------//
        return [
            'name.required'            => __('The name field is required.'),
            'name.max'                 => __('The name may not be greater than 191 characters.'),

            'description.required'     => __('The description field is required.'),

            'main_category.required'   => __('The category field is required.'),
            'main_category.exists'     => __('The selected category is invalid.'),

            'quantity.required'        => __('The quantity field is required.'),
            'quantity.integer'         => __('The quantity must be an integer.'),
            'quantity.min'             => __('The quantity must be at least 1.'),

            'mrp.required'             => __('The price field is required.'),
            'mrp.numeric'              => __('The price must be a number.'),
            'mrp.min'                  => __('The price must be at least 0.'),

            'selling_price.numeric'    => __('The selling price must be a number.'),
            'selling_price.min'        => __('The selling price must be at least 0.'),

            'thumbnail.required'       => __('The thumbnail field is required.'),
            'thumbnail.image'          => __('The thumbnail must be an image.'),
            'thumbnail.mimes'          => __('The thumbnail must be a file of type: png, jpg, jpeg, webp.'),
            'thumbnail.max'            => __('The thumbnail may not be greater than 2048 kilobytes.'),

            'additional_images.*.image' => __('Each additional image must be an image file.'),
            'additional_images.*.mimes' => __('Additional images must be png, jpg, jpeg or webp.'),
        ];
    }
}
