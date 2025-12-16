<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\ColorRequest;
use App\Models\Color;
use App\Models\TranslateUtility;

class ColorRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Color::class;
    }

    /**
     * store a new color
     */
    public static function storeByRequest(ColorRequest $request): Color
    {
        $shop = generaleSetting('shop');

        $color = self::create([
            'name' => $request->name,
            'color_code' => $request->color_code,
            'shop_id' => $shop->id,
            'is_active' => true,
        ]);

        // create translation
        foreach ($request->names ?? [] as $lang => $name) {
            if (! $lang || ! $name) {
                continue;
            }
            TranslateUtility::create([
                'color_id' => $color->id,
                'name' => $name,
                'lang' => $lang,
            ]);
        }

        return $color;
    }

    /**
     * update a color
     */
    public static function updateByRequest(ColorRequest $request, Color $color): Color
    {
        $color->update([
            'name' => $request->name,
            'color_code' => $request->color_code,
        ]);

        // update and create translation
        foreach ($request->names ?? [] as $lang => $name) {
            if (! $lang || ! $name) {
                continue;
            }
            TranslateUtility::updateOrCreate([
                'color_id' => $color->id,
                'lang' => $lang,
            ], [
                'name' => $name,
            ]);
        }

        return $color;
    }

    public static function syncFromRequest(array $colors, int $shopId)
    {
        $existingIds = Color::where('shop_id', $shopId)
            ->pluck('id')
            ->toArray();

        $requestIds = collect($colors)
            ->pluck('id')
            ->filter()
            ->toArray();

        // � DELETE removed colors
        Color::where('shop_id', $shopId)
            ->whereNotIn('id', $requestIds)
            ->delete();

        $saved = [];

        foreach ($colors as $item) {
            $color = Color::updateOrCreate(
                [
                    'id' => $item['id'] ?? null,
                    'shop_id' => $shopId,
                ],
                [
                    'name' => $item['name'],
                    'color_code' => $item['color_code'],
                    'is_active' => $item['is_active'] ?? true,
                ]
            );

            $saved[] = $color;
        }

        return $saved;
    }
}
