<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\SizeRequest;
use App\Models\Size;
use App\Models\TranslateUtility;

class SizeRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Size::class;
    }

    /**
     * store new size.
     *
     * @param  \App\Http\Requests\SizeRequest  $request
     *                                                   return \App\Models\Size
     * */
    public static function storeByRequest(SizeRequest $request): Size
    {
        $shop = generaleSetting('shop');

        $size = self::create([
            'name' => $request->name,
            'shop_id' => $shop->id,
            'is_active' => true,
        ]);

        // create translation
        foreach ($request->names ?? [] as $lang => $name) {
            if (! $lang || ! $name) {
                continue;
            }
            TranslateUtility::create([
                'size_id' => $size->id,
                'name' => $name,
                'lang' => $lang,
            ]);
        }

        return $size;
    }

    /**
     * Update the size.
     *
     * @param  \App\Http\Requests\SizeRequest  $request
     *                                                   return \App\Models\Size
     * */
    public static function updateByRequest(SizeRequest $request, Size $size): Size
    {
        $size->update([
            'name' => $request->name,
        ]);

        // update and create translation
        foreach ($request->names ?? [] as $lang => $name) {
            if (! $lang || ! $name) {
                continue;
            }
            TranslateUtility::updateOrCreate([
                'size_id' => $size->id,
                'lang' => $lang,
            ], [
                'name' => $name,
            ]);
        }

        return $size;
    }

    public static function syncFromRequest(array $sizes, int $shopId)
    {
        $existingIds = Size::where('shop_id', $shopId)
            ->pluck('id')
            ->toArray();

        $requestIds = collect($sizes)
            ->pluck('id')
            ->filter()
            ->toArray();

        // � DELETE removed sizes
        Size::where('shop_id', $shopId)
            ->whereNotIn('id', $requestIds)
            ->delete();

        $saved = [];

        foreach ($sizes as $item) {
            $size = Size::updateOrCreate(
                [
                    'id' => $item['id'] ?? null,
                    'shop_id' => $shopId,
                ],
                [
                    'name' => strtoupper($item['name']),
                    'is_active' => $item['is_active'] ?? true,
                ]
            );

            $saved[] = $size;
        }

        return $saved;
    }
}
