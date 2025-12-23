<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\ProductRequest;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\RecentView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Log;

use App\Models\ProductBulkPrice;
use App\Models\ProductItemDetail;
use App\Models\ProductBulkItem;

class ProductRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Product::class;
    }

    public static function recentView(Product $product)
    {
        $user = Auth::guard('api')->user();
        if ($user) {
            RecentView::where('product_id', $product->id)->where('user_id', $user->id)->firstOrCreate([
                'product_id' => $product->id,
                'user_id' => $user->id,
            ])?->update(['updated_at' => now()]);
        }

        return $product;
    }

    /**
     * Sanitizes a string by removing invalid or non-printable characters.
     *
     * @param  string  $input
     * @return string
     */
    public static function sanitizeUnicode($input)
    {
        $cleanedInput = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $input);
        $cleanedInput = preg_replace('/[\xF0-\xF9][\x80-\xBF][\xF0\x9F]{3}/u', '', $input);
        $cleanedInput = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/u', '', $cleanedInput);
        $cleanedInput = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $cleanedInput);
        $cleanedInput = preg_replace('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', '', $cleanedInput);

        return $cleanedInput;
    }

    /**
     * store new product.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     *                                                      return \App\Models\Product
     */
    public static function storeByRequest(ProductRequest $request): Product
    {
        $thumbnail = MediaRepository::storeByRequest($request->thumbnail, 'products', 'thumbnail');

        $shop = generaleSetting('shop');
        $generaleSetting = generaleSetting('setting');
        $approve = $generaleSetting?->new_product_approval ? false : true;

        /**
         * @var \App\Models\User $user
         */
        $user = auth()->user();
        $isAdmin = false;
        if ($user->hasRole('root') || ($generaleSetting?->shop_type == 'single')) {
            $isAdmin = true;
        }

        $videoMedia = self::videoCreateOrUpdate($request);
        $description = Purifier::clean(self::sanitizeUnicode($request->description));

        $keywords = implode(',', $request->meta_keywords ?? []);

        $details = $request->details;
        if (isset($details)) {
            if (is_string($details)) {
                $details = json_decode($details, true) ?: null;
            }
        }

        $product = self::create([
            'shop_id' => $shop?->id,
            'name' => $request->name,
            'description' => $description,
            'short_description' => $request->short_description,
            'brand_id' => $request->brand,
            'unit_id' => $request->unit,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'details' => $details,
            'quantity' => $request->quantity ?? 0,
            'min_order_quantity' => $request->min_order_quantity ?? 1,
            'media_id' => $thumbnail->id,
            'code' => $request->code,
            'buy_price' => $request->buy_price ?? 0,
            'is_active' => $isAdmin ? true : $approve,
            'is_new' => true,
            'is_approve' => $isAdmin ? true : $approve,
            'video_id' => $videoMedia ? $videoMedia->id : null,
            'meta_title' => $request->meta_title,
            'tax_percentage' => $request->gst ?? 0,
            'return_period' => $request->return_period ?? 0,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $keywords ? Str::limit($keywords, 200, '') : null,
        ]);

        foreach ($request->names ?? [] as $key => $value) {
            if (! $key || ! $value) {
                continue;
            }

            $description = array_key_exists($key, $request->descriptions ?? []) ? $request->descriptions[$key] : null;
            $shortDescription = array_key_exists($key, $request->short_descriptions ?? []) ? $request->short_descriptions[$key] : null;

            ProductTranslation::create([
                'product_id' => $product->id,
                'lang' => $key,
                'name' => $value,
                'description' => $description,
                'short_description' => $shortDescription,
            ]);
        }

        if ($request->is('api/*')) {
            if ($request->color && is_array($request->color)) {
                $colors = array_column($request->color, 'id');
                $product->colors()->sync($colors);
            }
        } else {
            foreach ($request->color ?? [] as $color) {
                $product->colors()->attach($color['id'], ['price' => $color['price']]);
            }
        }


        $hasVariants   = $request->filled('variants');
        $hasBulkItems  = $request->filled('bulk_items');
        $hasBulkPrice  = $request->filled('bulk_prices');

        if ($hasVariants) {
            DB::transaction(function () use ($product, $request) {

                // ✅ sync variants
                $variants = collect($request->variants);


                $variants->each(function ($v) use ($product) {
                    ProductVariant::create(
                        [
                            'product_id' => $product->id,
                            'size_id'  => $v['size']['id'],
                            'color_id' => $v['color']['id'],
                            'price'    => $v['price'],
                            'quantity' => $v['quantity'],
                        ]
                    );
                });
            });
        } elseif ($hasBulkItems) {
            DB::transaction(function () use ($product, $request) {

                // ✅ sync bulk items
                $bulkItems = collect($request->bulk_items);

                $bulkItems->each(function ($item) use ($product) {
                    if (empty($item['name'])) return;

                    ProductBulkItem::create(
                        [
                            'product_id' => $product->id,
                            'name'          => $item['name'],
                            'quantity'      => $item['quantity'] ?? 0,
                            'moq'           => $item['moq'] ?? 1,
                            'mrp'           => $item['mrp'] ?? 0,
                            'selling_price' => $item['selling_price'] ?? 0,
                        ]
                    );
                });
            });
        } elseif ($hasBulkPrice) {
            DB::transaction(function () use ($product, $request) {

                // ✅ sync bulk price
                $bulkPrices = collect($request->bulk_prices);

                $bulkPrices->each(function ($b) use ($product) {
                    if (!isset($b['min_qty'], $b['max_qty'], $b['price'])) return;

                    ProductBulkPrice::create(
                        [
                            'product_id' => $product->id,
                            'min_qty' => (int) $b['min_qty'],
                            'max_qty' => (int) $b['max_qty'],
                            'price'   => (float) $b['price'],
                        ]
                    );
                });
            });
        }


        $product->categories()->sync($request->category ?? []);
        $product->subcategories()->sync($request->sub_category ?? []);
        $product->childCategories()->sync($request->child_category ?? []);

        if ($request->is('api/*')) {
            if ($request->size && is_array($request->size)) {
                foreach ($request->size ?? [] as $size) {
                    $price = 0;
                    $product->sizes()->attach($size, ['price' => $price]);
                }
            }
        } else {
            foreach ($request->size ?? [] as $size) {
                $product->sizes()->attach($size['id'], ['price' => $size['price']]);
            }
        }

        foreach ($request->additionThumbnail ?? [] as $additionThumbnail) {
            $thumbnail = MediaRepository::storeByRequest($additionThumbnail, 'products', 'thumbnail', 'image');
            $product->medias()->attach($thumbnail->id);
        }

        return $product;
    }

    /**
     * Update the product.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     *                                                      return \App\Models\Product
     */
    public static function updateByRequest(ProductRequest $request, Product $product): Product
    {
        $thumbnail = $product->media;
        if ($request->hasFile('thumbnail') && $thumbnail) {
            $thumbnail = MediaRepository::updateByRequest(
                $request->thumbnail,
                'products',
                'image',
                $thumbnail
            );
        }

        if ($request->hasFile('thumbnail') && $thumbnail == null) {
            $thumbnail = MediaRepository::storeByRequest($request->thumbnail, 'products', 'image');
        }

        $generaleSetting = generaleSetting('setting');
        $approve = $generaleSetting?->update_product_approval ? false : true;

        /**
         * @var \App\Models\User $user
         */
        $user = auth()->user();
        $isAdmin = false;
        if ($user->hasRole('root') || ($generaleSetting?->shop_type == 'single')) {
            $isAdmin = true;
        }

        $videoMedia = self::videoCreateOrUpdate($request, $product);
        $description = Purifier::clean(self::sanitizeUnicode($request->description));
        $keywords = implode(',', $request->meta_keywords ?? []);

        $details = $request->details;
        if (isset($details)) {
            if (is_string($details)) {
                $details = json_decode($details, true) ?: null;
            }
        }

        self::update($product, [
            'name' => $request->name,
            'description' => $description,
            'short_description' => $request->short_description,
            'brand_id' => $request->brand ?? null,
            'unit_id' => $request->unit ?? null,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'details' => $details,
            'quantity' => $request->quantity ?? 0,
            'min_order_quantity' => $request->min_order_quantity ?? 1,
            'media_id' => $thumbnail ? $thumbnail->id : null,
            // 'code' => $request->code,
            'tax_percentage' => $request->gst ?? 0,
            'return_period' => $request->return_period ?? 0,
            'condition_status' => $request->condition_status ?? 'New',
            'buy_price' => $request->buy_price ?? 0,
            'is_active' => $isAdmin ? true : $approve,
            'is_new' => false,
            'is_approve' => $isAdmin ? true : $approve,
            'video_id' => $videoMedia ? $videoMedia->id : null,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $keywords ? Str::limit($keywords, 200, '') : null,
        ]);

        foreach ($request->names ?? [] as $key => $value) {
            if (! $key || ! $value) {
                continue;
            }

            $description = array_key_exists($key, $request->descriptions ?? []) ? $request->descriptions[$key] : null;
            $shortDescription = array_key_exists($key, $request->short_descriptions ?? []) ? $request->short_descriptions[$key] : null;

            ProductTranslation::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'lang' => $key,
                ],
                [
                    'name' => $value,
                    'description' => $description,
                    'short_description' => $shortDescription,
                ]
            );
        }

        $product->colors()->detach();
        if ($request->is('api/*')) {
            $colors = [];
            if ($request->color && is_array($request->color)) {
                $colors = array_column($request->color, 'id');
            }
            $product->colors()->attach($colors);
        } else {
            foreach ($request->color ?? [] as $color) {
                $product->colors()->attach($color['id'], ['price' => $color['price']]);
            }
        }

        $hasVariants   = $request->filled('variants');
        $hasBulkItems  = $request->filled('bulk_items');
        $hasBulkPrice  = $request->filled('bulk_prices');

        if ($hasVariants) {
            DB::transaction(function () use ($product, $request) {

                // ❌ delete others
                $product->bulkItems()->delete();
                $product->bulkPrices()->delete();

                // ✅ sync variants
                $variants = collect($request->variants);

                $product->variants()
                    ->whereNotIn('id', $variants->pluck('id')->filter())
                    ->delete();

                $variants->each(function ($v) use ($product) {
                    $product->variants()->updateOrCreate(
                        ['id' => $v['id'] ?? null],
                        [
                            'size_id'  => $v['size']['id'],
                            'color_id' => $v['color']['id'],
                            'price'    => $v['price'],
                            'quantity' => $v['quantity'],
                        ]
                    );
                });
            });
        } elseif ($hasBulkItems) {
            DB::transaction(function () use ($product, $request) {

                // ❌ delete others
                $product->variants()->delete();
                $product->bulkPrices()->delete();

                // ✅ sync bulk items
                $bulkItems = collect($request->bulk_items);

                $product->bulkItems()
                    ->whereNotIn('id', $bulkItems->pluck('id')->filter())
                    ->delete();

                $bulkItems->each(function ($item) use ($product) {
                    if (empty($item['name'])) return;

                    $product->bulkItems()->updateOrCreate(
                        ['id' => $item['id'] ?? null],
                        [
                            'name'          => $item['name'],
                            'quantity'      => $item['quantity'] ?? 0,
                            'moq'           => $item['moq'] ?? 1,
                            'mrp'           => $item['mrp'] ?? 0,
                            'selling_price' => $item['selling_price'] ?? 0,
                        ]
                    );
                });
            });
        } elseif ($hasBulkPrice) {
            DB::transaction(function () use ($product, $request) {

                // ❌ delete others
                $product->variants()->delete();
                $product->bulkItems()->delete();

                // ✅ sync bulk price
                $bulkPrices = collect($request->bulk_prices);

                $product->bulkPrices()
                    ->whereNotIn('id', $bulkPrices->pluck('id')->filter())
                    ->delete();

                $bulkPrices->each(function ($b) use ($product) {
                    if (!isset($b['min_qty'], $b['max_qty'], $b['price'])) return;

                    $product->bulkPrices()->updateOrCreate(
                        ['id' => $b['id'] ?? null],
                        [
                            'min_qty' => (int) $b['min_qty'],
                            'max_qty' => (int) $b['max_qty'],
                            'price'   => (float) $b['price'],
                        ]
                    );
                });
            });
        } else {
            $product->variants()->delete();
            $product->bulkItems()->delete();
            $product->bulkPrices()->delete();
        }




        $product->categories()->sync($request->category ?? []);
        $product->subcategories()->sync($request->sub_category ?? []);
        $product->childCategories()->sync($request->child_category ?? []);

        $product->sizes()->detach();
        if ($request->is('api/*')) {
            if ($request->size && is_array($request->size)) {
                foreach ($request->size ?? [] as $size) {
                    $price = 0;
                    $product->sizes()->attach($size, ['price' => $price]);
                }
            }
        } else {
            foreach ($request->size ?? [] as $size) {
                $product->sizes()->attach($size['id'], ['price' => $size['price']]);
            }
        }

        if ($request->is('api/*')) {
            self::updateAdditionThumbnails($request->previousThumbnail, $product);
        } else {
            foreach ($request->additionThumbnail ?? [] as $additionThumbnail) {
                $thumbnail = MediaRepository::storeByRequest($additionThumbnail, 'products', 'thumbnail', 'image');
                $product->medias()->attach($thumbnail->id);
            }

            self::updatePreviousThumbnail($request->previousThumbnail);
        }

        return $product;
    }

    private static function videoCreateOrUpdate($request, $product = null): ?Media
    {
        $media = $product?->videoMedia;
        $uploadVideoRequest = $request->uploadVideo;

        if (! $uploadVideoRequest || ! is_countable($uploadVideoRequest)) {
            return $media;
        }

        $type = $uploadVideoRequest['type'];
        $url = isset($uploadVideoRequest[$type . '_' . 'url']) ? $uploadVideoRequest[$type . '_' . 'url'] : null;

        if ($media && $type == 'file' && isset($uploadVideoRequest['file']) && is_file($uploadVideoRequest['file'])) {
            return MediaRepository::updateByRequest(
                $uploadVideoRequest['file'],
                'products',
                'file',
                $media
            );
        } elseif ($media && $type != 'file' && $url != null) {

            // Replace the width and height attributes in the iframe
            $customWidth = '100%';
            $customHeight = '650';
            $customizedIframe = preg_replace(['/width="(\d+(%?))"/', '/height="(\d+(%?))"/'], ["width=\"$customWidth\"", "height=\"$customHeight\""], $url);

            $media->update([
                'src' => $customizedIframe,
                'type' => $type,
            ]);

            return $media;
        }

        if (! $media && $type == 'file' && isset($uploadVideoRequest['file']) && is_file($uploadVideoRequest['file'])) {
            return MediaRepository::storeByRequest(
                $uploadVideoRequest['file'],
                'products',
                'file'
            );
        } elseif (! $media && $type != 'file' && $url != null) {

            $width = '100%';
            $height = '650';
            $customizedIframe = preg_replace(['/width="(\d+(%?))"/', '/height="(\d+(%?))"/'], ["width=\"$width\"", "height=\"$height\""], $url);

            return Media::create([
                'src' => $customizedIframe,
                'type' => $type,
            ]);
        }

        return $media;
    }

    /**
     * store new product from bulk import.
     */
    public static function bulkItemStore($rows, $folders = null)
    {
        $invalidRows = [];

        $shop = generaleSetting('shop');
        $rootShop = generaleSetting('rootShop');

        $total = 0;

        $folders = $folders !== null ? array_keys($folders) : [];

        $galleryPath = 'gallery/shop' . $shop->id;

        foreach ($rows as $row) {

            $createData = [];

            for ($i = 0; $i <= 13; $i++) {

                if ($i == 1) {
                    $createData['name'] = $row[$i];
                } elseif ($i == 2) {

                    $explodeThumbnails = explode(',', $row[$i]);

                    $thumbnails = [];
                    foreach ($explodeThumbnails as $thumbnail) {
                        $storeFile = null;
                        foreach ($folders as $folder) {
                            if (Storage::disk('public')->exists($galleryPath . '/' . $folder)) {
                                $files = File::files(Storage::disk('public')->path($galleryPath . '/' . $folder));
                                foreach ($files as $file) {
                                    if (basename($file) == $thumbnail) {
                                        $storeFile = $file;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($storeFile) {
                            $thumbnails[] = $storeFile;
                        }
                    }
                    $createData['thumbnails'] = $thumbnails;
                } elseif ($i == 3) {
                    $selectCategories = explode(',', $row[$i]);
                    $categories = [];
                    foreach ($selectCategories as $categoryName) {

                        $category = $rootShop->categories()->where('name', $categoryName)->first();

                        if ($category) {
                            $categories[] = $category->id;
                        }
                    }
                    $createData['categories'] = $categories;
                } elseif ($i == 4) {
                    $selectedSubCategories = explode(',', $row[$i]);
                    $subCategories = [];
                    foreach ($selectedSubCategories as $subCategoryName) {
                        $subCategory = $rootShop->subcategories()->where('name', $subCategoryName)->first();
                        if ($subCategory) {
                            $subCategories[] = $subCategory->id;
                        }
                    }
                    $createData['subCategories'] = $subCategories;
                } elseif ($i == 5) {
                    $brand = $rootShop->brands()->where('name', $row[$i])->first();
                    $createData['brand'] = $brand ? $brand->id : null;
                } elseif ($i == 6) {
                    $selectColors = explode(',', $row[$i]);
                    $colors = [];
                    foreach ($selectColors as $colorName) {
                        $color = $rootShop->colors()->where('name', $colorName)->first();
                        if ($color) {
                            $colors[] = $color->id;
                        }
                    }
                    $createData['colors'] = $colors;
                } elseif ($i == 7) {
                    $selectSizes = explode(',', $row[$i]);
                    $sizes = [];
                    foreach ($selectSizes as $sizeName) {
                        $size = $rootShop->sizes()->where('name', $sizeName)->first();
                        if ($size) {
                            $sizes[] = $size->id;
                        }
                    }
                    $createData['sizes'] = $sizes;
                } elseif ($i == 8) {
                    $createData['price'] = $row[$i];
                } elseif ($i == 9) {
                    $createData['discount_price'] = $row[$i];
                } elseif ($i == 10) {
                    $createData['sku'] = $row[$i];
                } elseif ($i == 11) {
                    $createData['stock_quantity'] = $row[$i];
                } elseif ($i == 12) {
                    $createData['short_description'] = $row[$i];
                } elseif ($i == 13) {
                    $createData['description'] = $row[$i];
                }
            }

            if ($createData['name'] != null && $createData['price'] != null && count($createData['categories']) != 0) {

                if ($createData['price'] < $createData['discount_price']) {
                    $createData['discount_price'] = $createData['price'];
                }

                self::storeBulkProduct($createData);

                $total = $total + 1;
            }
        }

        return $total;
    }

    /**
     * store new product from bulk import.
     *
     * @return Product
     */
    private static function storeBulkProduct($data)
    {
        $shop = generaleSetting('shop');
        $generaleSetting = generaleSetting('setting');
        $approve = $generaleSetting?->new_product_approval ? false : true;

        /**
         * @var \App\Models\User $user
         */
        $user = auth()->user();
        $isAdmin = false;
        if ($user->hasRole('root') || ($generaleSetting?->shop_type == 'single')) {
            $isAdmin = true;
        }

        $thumbnail = $data['thumbnails'] ? $data['thumbnails'][0] : null;

        $media = self::storeMedia($thumbnail);

        $additionalThumbnails = $data['thumbnails'] ? array_slice($data['thumbnails'], 1) : [];

        $medias = [];
        foreach ($additionalThumbnails as $thumbnail) {
            $hasMedia = self::storeMedia($thumbnail);
            if ($hasMedia) {
                $medias[] = $hasMedia;
            }
        }

        $product = self::create([
            'shop_id' => $shop?->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? 'description',
            'short_description' => $data['short_description'] ?? 'short description',
            'brand_id' => $data['brand'] ?? null,
            'price' => $data['price'] ?? 0,
            'discount_price' => $data['discount_price'] ?? 0,
            'quantity' => $data['stock_quantity'] ?? 1,
            'min_order_quantity' => 1,
            'media_id' => $media,
            'is_active' => $isAdmin ? true : $approve,
            'is_new' => true,
            'is_approve' => $isAdmin ? true : $approve,
            'code' => $data['sku'] ?? random_int(100000, 999999),
        ]);

        $product->categories()->sync($data['categories'] ?? []);
        $product->subCategories()->sync($data['subCategories'] ?? []);
        $product->colors()->sync($data['colors'], []);
        $product->sizes()->sync($data['sizes'], []);

        $product->medias()->attach($medias);

        return $product;
    }

    public static function storeMedia($thumbnail)
    {
        if ($thumbnail != null) {

            $realPath = $thumbnail->getRealPath();

            $path = 'thumbnails';

            $fileName = random_int(100000, 999999) . date('YmdHis') . '.' . pathinfo($realPath, PATHINFO_EXTENSION);

            $storagePath = Storage::disk('public')->putFileAs($path, $thumbnail, $fileName);

            $media = Media::create([
                'name' => pathinfo($storagePath, PATHINFO_FILENAME),
                'src' => $storagePath,
                'type' => 'image',
                'original_name' => basename($realPath),
                'extension' => pathinfo($storagePath, PATHINFO_EXTENSION),
            ]);

            return $media->id;
        }

        return null;
    }

    /**
     * Update the previous thumbnails.
     *
     * @param  array  $previousThumbnails  The array of previous thumbnails
     */
    private static function updatePreviousThumbnail($previousThumbnails)
    {
        foreach ($previousThumbnails ?? [] as $thumbnail) {
            if (array_key_exists('file', $thumbnail) && array_key_exists('id', $thumbnail) && $thumbnail['file'] != null) {
                $media = Media::find($thumbnail['id']);

                MediaRepository::updateByRequest(
                    $thumbnail['file'],
                    'products',
                    'image',
                    $media
                );
            }
        }
    }

    /**
     * Update the additional thumbnails.
     *
     * @param  array  $additionalThumbnails  The array of additional thumbnails
     * @param  Product  $product
     */
    private static function updateAdditionThumbnails($additionalThumbnails, $product)
    {
        $ids = [];

        foreach ($additionalThumbnails ?? [] as $additionThumbnail) {
            if (array_key_exists('file', $additionThumbnail) && $additionThumbnail['file'] != null) {

                $media = MediaRepository::storeByRequest($additionThumbnail['file'], 'products', 'thumbnail', 'image');

                $ids[] = $media->id;

                $product->medias()->attach($media->id);
            }

            if (array_key_exists('id', $additionThumbnail) && $additionThumbnail['id'] != null && $additionThumbnail['id'] != 0) {
                $ids[] = $additionThumbnail['id'];
            }
        }

        $previousMedias = $product->medias()->whereNotIn('id', $ids)->get();

        foreach ($previousMedias as $media) {

            $product->medias()->detach($media->id);

            if (Storage::exists($media->src)) {
                Storage::delete($media->src);
            }

            $media->delete();
        }
    }

    public static function storeByProductRequest(Request $request, array $data): Product
    {
        $thumbnail = MediaRepository::storeByRequest($request->thumbnail, 'products', 'thumbnail');

        $shop = generaleSetting('shop');
        $generaleSetting = generaleSetting('setting');
        $approve = $generaleSetting?->new_product_approval ? false : true;

        $user = auth()->user();
        $isAdmin = false;
        if ($user->hasRole('root') || ($generaleSetting?->shop_type == 'single')) {
            $isAdmin = true;
        }

        $keywords = null;
        if (!empty($data['meta_keywords']) && is_array($data['meta_keywords'])) {
            $keywords = implode(',', array_map('trim', $data['meta_keywords']));
        }

        DB::beginTransaction();

        try {
            /** -----------------------------
             * 1. Create Product
             * ----------------------------- */
            $product = Product::create([
                'shop_id'            => $shop?->id,
                'name'               => $data['name'],
                'description'        => $data['description'] ?? null,
                'condition_status'   => $data['condition_status'],
                'quantity'           => $data['quantity'],
                'min_order_quantity' => $data['min_order_quantity'] ?? 1,
                'return_period'      => $data['return_period'] ?? null,
                'price'              => $data['mrp'],
                'discount_price'     => $data['selling_price'] ?? null,
                'tax_percentage'     => $data['tax_percentage'] ?? null,
                'media_id'           => $thumbnail->id,
                'is_active'          => $isAdmin ? true : $approve,
                'is_new'             => true,
                'is_approve'         => $isAdmin ? true : $approve,
                'meta_title'         => $data['meta_title'] ?? null,
                'meta_description'   => $data['meta_description'] ?? null,
                'meta_keywords'      => $keywords,
            ]);
            /** -----------------------------
             * 2. Categories
             * ----------------------------- */
            if (!empty($data['main_category'])) {
                $product->categories()->sync([$data['main_category']]);
            }
            if (!empty($data['sub_categories']) && is_array($data['sub_categories'])) {
                $product->subcategories()->sync($data['sub_categories']);
            }
            if (!empty($data['child_categories']) && is_array($data['child_categories'])) {
                $product->childCategories()->sync($data['child_categories']);
            }
            // if (!empty($data['child_categories'])) {
            //     $rows = [];
            //     foreach ($data['child_categories'] as $cc) {
            //         $rows[] = [
            //             'product_id'        => $product->id,
            //             'child_category_id' => $cc,
            //         ];
            //     }
            //     DB::table('product_child_categories')->insert($rows);
            // }
            /** -----------------------------
             * 3. Item Details
             * ----------------------------- */
            if (!empty($data['item_details'])) {
                foreach ($data['item_details'] as $item) {

                    if (
                        empty($item['name'] ?? null) ||
                        empty($item['value'] ?? null)
                    ) {
                        continue;
                    }

                    ProductItemDetail::create([
                        'product_id' => $product->id,
                        'item_name'  => $item['name'],
                        'item_value' => $item['value'],
                    ]);
                }
            }
            /** -----------------------------
             * 4. Bulk Pricing
             * ----------------------------- */
            if (!empty($data['bulk'])) {
                foreach ($data['bulk'] as $b) {
                    if (!isset($b['min_qty'], $b['max_qty'], $b['price'])) continue;

                    ProductBulkPrice::create([
                        'product_id' => $product->id,
                        'min_qty'    => (int) $b['min_qty'],
                        'max_qty'    => (int) $b['max_qty'],
                        'price'      => (float) $b['price'],
                    ]);
                }
            }
            /** -----------------------------
             * 5. Variants
             * ----------------------------- */
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $v) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'size_id'    => $v['size_id'] ?? null,
                        'color_id'   => $v['color_id'] ?? null,
                        'price'      => $v['price'] ?? 0,
                        'quantity'   => $v['quantity'] ?? 0,
                    ]);
                }
            }
            /** -----------------------------
             * 6. Video
             * ----------------------------- */
            if (!empty($data['video_link']) && !empty($data['video_type'])) {
                $media = Media::create([
                    'type'          => $data['video_type'],
                    'name'          => basename($data['video_link']),
                    'original_name' => basename($data['video_link']),
                    'src'           => $data['video_link'],
                    'extention'     => null,
                ]);
                $product->update(['video_id' => $media->id]);
            }
            /** -----------------------------
             * 7. Additional Images
             * ----------------------------- */
            foreach ($request->file('additional_images', []) as $additionThumbnail) {
                $thumbnail = MediaRepository::storeByRequest($additionThumbnail, 'products', 'thumbnail', 'image');
                $product->medias()->attach($thumbnail->id);
            }

            /** ---- 4. Bulk Items ---- */
            if (!empty($data['bulk_items'])) {
                foreach ($data['bulk_items'] as $item) {
                    if (empty($item['name'])) continue; // skip empty rows

                    ProductBulkItem::create([
                        'product_id'    => $product->id,
                        'name'          => $item['name'],
                        'quantity'      => $item['quantity'] ?? 0,
                        'moq'           => $item['moq'] ?? 1,
                        'mrp'           => $item['mrp'] ?? 0,
                        'selling_price' => $item['selling_price'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            return $product;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ProductRepository store error: ' . $e->getMessage());
            throw $e;
        }
    }
}
