<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Roles;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ShopPasswordResetRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\AddressRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
public function index(Request $request, $status = null)
{
    // Normalize status
    if ($status) {
        $status = strtolower($status);
    }

    $search = $request->input('search');
    $date   = $request->input('date');
    $length = $request->input('length', 20); // pagination size

    $customers = User::role(Roles::CUSTOMER->value)
        ->with('media')
        ->with('customer')
        ->withCount('orders')

        // STATUS FILTER
        ->when($status, function ($query) use ($status) {
            $query->whereHas('customer', function ($q) use ($status) {
                $q->where('status', $status);
            });
        })

        // SEARCH FILTER
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%")
                  ->orWhere('phone', 'LIKE', "%$search%");
            });
        })

        // DATE FILTER
        ->when($date, function ($query) use ($date) {
            $query->whereDate('created_at', $date);
        })

        ->latest('id')
        ->paginate($length)
        ->withQueryString();

    return view('admin.customer.index', compact('customers', 'status'));
}



    public function create()
    {
        return view('admin.customer.create');
    }

    public function store(RegistrationRequest $request)
    {
        // Create a new user
        $user = UserRepository::registerNewUser($request);

        // Create a new customer
        $customer = CustomerRepository::storeByRequest($user);

        // create wallet
        WalletRepository::storeByRequest($user);

        // Store address 
        AddressRepository::storeForAdminCreate($request, $customer); //added by ancy
        
        $user->assignRole(Roles::CUSTOMER->value);

        return to_route('admin.customer.index')->withSuccess(__('Created successfully'));
    }

    public function edit(User $user)
    {
        return view('admin.customer.edit', compact('user'));
    }

    public function update(User $user, UserRequest $request)
    {
        UserRepository::updateByRequest($request, $user);

        return to_route('admin.customer.index')->withSuccess(__('Updated successfully'));
    }

    public function destroy(User $user)
    {
        $media = $user->media;

        if ($media && Storage::exists($media->src)) {
            Storage::delete($media->src);
        }

        $user->wallet()?->delete();
        $user->syncPermissions([]);
        $user->syncRoles([]);

        $delTime = now()->format('YmdHis');

        $user->update([
            'phone' => $user->phone.'_deleted:'.$delTime,
            'email' => $user->email.'_deleted:'.$delTime,
            'deleted_at' => now(),
        ]);

        $media?->delete();

        return back()->withSuccess(__('Deleted successfully'));
    }

    public function resetPassword(User $user, ShopPasswordResetRequest $request)
    {
        // Update the user password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->withSuccess(__('Password updated successfully'));
    }

    public function statusToggle(User $user) //added by ancy
    {
        $customer = $user->customer;
        if (!$customer) {
            return back()->with('error', 'Customer not found.');
        }
        $customer->status = ($customer->status === 'active') ? 'banned' : 'active';
        $customer->save();

        return back()->with('success', 'Customer status updated successfully.');
    }

}
