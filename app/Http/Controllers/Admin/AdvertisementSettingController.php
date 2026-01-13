<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdvertisementSetting;

class AdvertisementSettingController extends Controller
{
    public function edit()
    {
        // Only ONE row exists
        $setting = AdvertisementSetting::first();

        // Auto-create if missing
        if (!$setting) {
            $setting = AdvertisementSetting::create([
                'daily_budget' => 0
            ]);
        }

        return view('admin.advertisement.settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'daily_budget' => 'required|numeric|min:1'
        ]);

        $setting = AdvertisementSetting::first();

        $setting->update([
            'daily_budget' => $request->daily_budget
        ]);

        return redirect()
            ->back()
            ->with('success', 'Advertisement daily budget updated successfully');
    }
}
