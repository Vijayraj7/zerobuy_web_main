<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ShopDetailsResource;
use App\Http\Resources\ShopResource;
use App\Models\Category;
use App\Models\Shop;
use App\Repositories\ProductRepository;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;

class ShopFollowerController extends Controller
{
    /**
     * Get all shops with pagination and filtering options.
     *
     * @param  Request  $request  The request object
     * @return Some_Return_Value The JSON response
     */
    public function index(Request $request)
    {
        $orderStatus = $request->order_status;

        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $customer = auth()->user()->customer;

        $followings = $customer->followings();

        // Response
        return $this->json('orders', [
            'total' => $total,
            'status_wise_orders' => $followings,
        ]);
    }
}
