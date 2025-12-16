<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\ColorRequest;
use App\Models\Color;
use App\Repositories\ColorRepository;
use App\Repositories\SizeRepository;
use Illuminate\Support\Facades\Request;

class ColorController extends Controller
{
    /**
     * Display the colors list.
     */
    public function index()
    {
        $shop = generaleSetting('shop');

        // Get colors
        $colors = $shop->colors()->paginate(20);

        return view('shop.color.index', compact('colors'));
    }


    public function getcolors()
    {
        $shop = generaleSetting('shop');

        // Get colors
        $colors = $shop->colors()->paginate(20);
        $sizes = $shop->sizes()->paginate(20);
        return $this->json('Colors & Sizes getted successfully', [
            'sizes' => $sizes,
            'colors' => $colors,
        ]);
    }

    public function saveColorsAndSizes(Request $request)
    {
        $request->validate([
            'colors' => 'array',
            'sizes' => 'array',
        ]);

        $shop = generaleSetting('shop');

        $colors = ColorRepository::syncFromRequest(
            $request->colors ?? [],
            $shop->id
        );

        $sizes = SizeRepository::syncFromRequest(
            $request->sizes ?? [],
            $shop->id
        );

        return $this->json('Colors & Sizes saved successfully', [
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }



    /**
     * store a new color
     */
    public function store(ColorRequest $request)
    {
        ColorRepository::storeByRequest($request);

        return to_route('shop.color.index')->withSuccess(__('Color created successfully'));
    }

    /**
     * update a color
     */
    public function update(ColorRequest $request, Color $color)
    {
        ColorRepository::updateByRequest($request, $color);

        return to_route('shop.color.index')->withSuccess(__('Color updated successfully'));
    }

    /**
     * status toggle a color
     */
    public function statusToggle(Color $color)
    {
        $color->update([
            'is_active' => ! $color->is_active,
        ]);

        return back()->withSuccess(__('Status updated successfully'));
    }
}
