<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Models\DeliverySetting;
use App\Models\DeliveryAmountRule;
use App\Models\DeliveryStateCharge;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliverySettingController extends Controller
{
    /**
     * Get delivery settings for a shop
     */
    public function show(Request $request)
    {
        // $shopId = auth()->user()->shop_id ?? null;

        $shop = generaleSetting('shop');

        $setting = DeliverySetting::with([
            'amountRules',
            'stateCharges',
        ])->where('shop_id', $shop->id)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Delivery settings not found',
            ], 404);
        }

        // return response()->json($setting);
        return $this->json('Delivery Settings', [
            'settings' => $setting,
        ]);
    }


    /**
     * Get states
     */
    public function getStates(Request $request)
    {
        return $this->json('State List', [
            'state' => State::all(),
        ]);
    }


    /**
     * Store or update delivery settings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_mode' => [
                'required',
                Rule::in(['amount_based', 'state_wise', 'manual']),
            ],
            'update_when_shipped' => ['boolean'],

            'amount_rules' => ['array'],
            'amount_rules.*.min_amount' => ['required_if:delivery_mode,amount_based', 'numeric', 'min:0'],
            'amount_rules.*.max_amount' => ['required_if:delivery_mode,amount_based', 'numeric', 'gt:amount_rules.*.min_amount'],
            'amount_rules.*.charge'     => ['required_if:delivery_mode,amount_based', 'numeric', 'min:0'],

            'state_charges' => ['array'],
            'state_charges.*.state'  => ['required_if:delivery_mode,state_wise', 'string', 'max:100'],
            'state_charges.*.charge' => ['required_if:delivery_mode,state_wise', 'numeric', 'min:0'],
        ]);

        // $shopId = auth()->user()->shop_id ?? null;

        $shop = generaleSetting('shop');

        DB::transaction(function () use ($validated, $shop) {

            // Create or update main setting
            $setting = DeliverySetting::updateOrCreate(
                ['shop_id' => $shop->id],
                [
                    'delivery_mode' => $validated['delivery_mode'],
                    'update_when_shipped' => $validated['update_when_shipped'] ?? false,
                ]
            );

            // Clean old rules
            DeliveryAmountRule::where('delivery_setting_id', $setting->id)->delete();
            DeliveryStateCharge::where('delivery_setting_id', $setting->id)->delete();

            // Save amount based rules
            if ($validated['delivery_mode'] === 'amount_based') {
                foreach ($validated['amount_rules'] ?? [] as $rule) {
                    DeliveryAmountRule::create([
                        'delivery_setting_id' => $setting->id,
                        'min_amount' => $rule['min_amount'],
                        'max_amount' => $rule['max_amount'],
                        'charge'     => $rule['charge'],
                    ]);
                }
            }

            // Save state wise charges
            if ($validated['delivery_mode'] === 'state_wise') {
                foreach ($validated['state_charges'] ?? [] as $state) {
                    DeliveryStateCharge::create([
                        'delivery_setting_id' => $setting->id,
                        'state'  => $state['state'],
                        'charge' => $state['charge'],
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Delivery settings saved successfully',
        ]);
    }
}
