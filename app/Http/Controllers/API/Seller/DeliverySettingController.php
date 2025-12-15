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
            'stateCharges.state',
        ])->where('shop_id', $shop->id)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Delivery settings not found',
            ], 404);
        }

        $selectedIds = $setting?->selected_state_ids ?? [];
        // return response()->json($setting);
        return $this->json('Delivery Settings', [
            'settings' => $setting,
            'selected_states' => State::whereIn('id', $selectedIds)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Get states
     */
    public function getStates(Request $request)
    {
        $shop = generaleSetting('shop');
        $setting = DeliverySetting::where('shop_id', $shop->id)->first();
        $selectedIds = $setting?->selected_state_ids ?? [];

        return $this->json('State List', [
            'states' => State::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'selected_states' => State::whereIn('id', $selectedIds)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'selected_state_ids' => $selectedIds,
        ]);
    }

    public function saveSelectedStates(Request $request)
    {
        $validated = $request->validate([
            'selected_state_ids' => ['required', 'array'],
            'selected_state_ids.*' => ['integer', 'exists:states,id'],
        ]);

        $shop = generaleSetting('shop');

        $setting = DeliverySetting::updateOrCreate(
            ['shop_id' => strval($shop->id)],
            [
                'selected_state_ids' => $validated['selected_state_ids'],
            ]
        );

        return $this->json('Selected states saved successfully', [
            'selected_state_ids' => $validated['selected_state_ids'],
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
            'state_charges.*.state'  => ['required_if:delivery_mode,state_wise', 'integer', 'max:100'],
            'state_charges.*.state_id' => [
                'required_if:delivery_mode,state_wise',
                'integer',
                'exists:states,id',
            ],
            'state_charges.*.charge' => [
                'required_if:delivery_mode,state_wise',
                'numeric',
                'min:0',
            ],
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
                        'state'  => State::where('id', $state['state'])->first()->name,
                        'state_id'  => $state['state'],
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
