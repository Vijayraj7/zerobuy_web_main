<?php

namespace App\Http\Requests;

use App\Models\ChildCategory;
use Illuminate\Validation\Validator;
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
        $thumbnail = $this->product?->media ? 'nullable' : 'required';

        if ($this->is('api/*')) {
            $thumbnail = 'nullable';
        }

        return [
            'condition_status'   => 'required|string',
            'name'               => 'required|string|max:191',
            'description'        => 'required|string',

            'main_category'      => 'required|exists:categories,id',
            // 'sub_categories'     => 'required|exists:sub_categories,id',
            // 'child_categories'   => 'nullable|exists:child_categories,id',

            'sub_category'     => 'nullable',
            'child_category'   => 'nullable',

            'sub_categories'     => 'required|array',
            'sub_categories.*'   => 'exists:sub_categories,id',
            'child_categories'   => 'nullable|array',
            'child_categories.*' => 'exists:child_categories,id',

            'quantity'           => 'required|integer|min:1',
            'min_order_quantity' => 'required|integer|min:1',
            'return_period'      => 'required|integer',
            'mrp'                => 'required|numeric|min:0',
            'selling_price'      => 'nullable|numeric|min:0|lt:mrp',
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

            'bulk_items.*.name'          => 'nullable|string|max:191',
            'bulk_items.*.quantity'      => 'nullable|integer|min:0',
            'bulk_items.*.moq'           => 'nullable|integer|min:0',
            'bulk_items.*.mrp'           => 'nullable|numeric|min:0',
            'bulk_items.*.selling_price' => 'nullable|numeric|min:0',

            'thumbnail'          => "$thumbnail|image|mimes:png,jpg,jpeg,webp|max:2048",
            'additional_images'   => 'nullable|array',
            'additional_images.*' => 'image|mimes:png,jpg,jpeg,webp|max:2048',

            'video_type'         => 'nullable|string|in:youtube,external',
            // 'video_link'         => 'nullable|string',
            'video_link' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    // Must be a YouTube URL
                    if (!preg_match('~^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/~i', $value)) {
                        $fail('The video link must be a valid YouTube URL.');
                        return;
                    }

                    // Must NOT be a Shorts link
                    if (preg_match('~youtube\.com\/shorts\/~i', $value)) {
                        $fail('YouTube Shorts links are not allowed. Please use a normal YouTube video link.');
                        return;
                    }

                    // Must contain a video ID (watch?v= or youtu.be/)
                    if (
                        !preg_match('~(watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{6,})~', $value)
                    ) {
                        $fail('Please enter a valid YouTube video link.');
                    }
                }
            ],

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
        return [
            'name.required'            => __('The name field is required.'),
            'name.max'                 => __('The name may not be greater than 191 characters.'),

            'description.required'     => __('The description field is required.'),

            'main_category.required'   => __('The category field is required.'),
            'main_category.exists'     => __('The selected category is invalid.'),

            'quantity.required'        => __('The quantity field is required.'),
            'quantity.integer'         => __('The quantity must be an integer.'),
            'quantity.min'             => __('The quantity must be at least 1.'),

            'min_order_quantity.required' => __('The min order quantity field is required.'),
            'min_order_quantity.integer'  => __('The min order quantity must be an integer.'),
            'min_order_quantity.min'      => __('The min order quantity must be at least 1.'),

            'return_period.required'   => __('The return period field is required.'),
            'return_period.integer'    => __('The return period must be an integer.'),

            'mrp.required'             => __('The price field is required.'),
            'mrp.numeric'              => __('The price must be a number.'),
            'mrp.min'                  => __('The price must be at least 0.'),

            'selling_price.required'   => __('The selling price field is required.'),
            'selling_price.numeric'    => __('The selling price must be a number.'),
            'selling_price.min'        => __('The selling price must be at least 0.'),
            'selling_price.lt'         => __('The selling price must be less than MRP.'),

            'thumbnail.required'       => __('The thumbnail field is required.'),
            'thumbnail.image'          => __('The thumbnail must be an image.'),
            'thumbnail.mimes'          => __('The thumbnail must be a file of type: png, jpg, jpeg, webp.'),
            'thumbnail.max'            => __('The thumbnail may not be greater than 2048 kilobytes.'),

            'additional_images.*.image' => __('Each additional image must be an image file.'),
            'additional_images.*.mimes' => __('Additional images must be png, jpg, jpeg or webp.'),
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {

            $subCategoryId = $this->input('sub_categories.0');

            if (! $subCategoryId) {
                return;
            }

            // Does this sub category have child categories?
            $hasChildren = ChildCategory::where('sub_category_id', $subCategoryId)
                ->active()
                ->exists();

            if (! $hasChildren) {
                // ✅ No child categories → no validation needed
                return;
            }

            // Child categories exist → at least one must be selected
            $children = $this->input('child_categories');

            if (empty($children) || count($children) === 0) {
                $validator->errors()->add(
                    'child_categories',
                    'Child category is required for the selected sub category.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('sub_category') != null) {
            $this->merge([
                'sub_categories' => $this->normalizeArray($this->input('sub_category')),
                'child_categories' => $this->normalizeArray($this->input('child_category')),
            ]);
        }
    }

    private function normalizeArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_null($value) || $value === '') {
            return [];
        }

        // Handle "1" or "1,2,3"
        if (is_string($value)) {
            return array_values(
                array_filter(
                    explode(',', $value),
                    fn($v) => $v !== ''
                )
            );
        }

        // Handle single integer
        return [(int) $value];
    }
}
