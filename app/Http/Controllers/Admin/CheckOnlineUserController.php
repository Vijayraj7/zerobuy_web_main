<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ShopPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckOnlineUserController extends Controller
{
    public function checkOnlineStatus()
    {
        if (! Auth::check()) {
            return;
        }

        ShopPresenceService::touch(Auth::user());
    }
}
