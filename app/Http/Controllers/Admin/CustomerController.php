<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Str;
use App\Enums\Roles;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ShopPasswordResetRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\AddressRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use DataTables;

class CustomerController extends Controller
{
    public function index()
    {
        return view('admin.customer.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = User::role(Roles::CUSTOMER->value)
                ->with('media','customer')
                ->withCount('orders');

            /* ---------------- DATE FILTER ---------------- */
            if ($request->date) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->whereDate('customers.created_at', $request->date);
                });
            }

            /* ---------------- STATUS TAB FILTER ---------------- */
            if ($request->status) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }
            
            return Datatables::of($query)
            ->addIndexColumn() 
            ->filterColumn('customer_id', function($query, $keyword) {
                $keyword = strtoupper($keyword);      // CST012
                // Extract numbers
                preg_match('/\d+/', $keyword, $m);
                $num = $m[0] ?? null;
                $query->whereHas('customer', function($q) use ($keyword, $num) {
                    // 1) match formatted CST0{id}
                    $q->whereRaw("CONCAT('CST0', id) LIKE ?", ["%{$keyword}%"]);
                    // 2) numeric search also
                    if ($num) {
                        $q->orWhere('id', 'LIKE', "%{$num}%");
                    }
                });
            })
            ->orderColumn('customer_id', function ($query, $order) {
                $query->join('customers', 'users.id', '=', 'customers.user_id')->orderBy('customers.id', $order);
            })

            ->addColumn('created_at', function ($row) {
                return $row->customer?->created_at?->format('d-m-Y | h:i A') ?? '';
            })
            ->addColumn('customer_id', function ($row) {
                return optional($row->customer)->id ? 'CST0' . $row->customer->id : '';
            })
            ->addColumn('profile', function ($row) {
                return '<img src="'.$row->thumbnail.'" width="45" class="rounded-circle">';
            })
            ->addColumn('fullname', function ($row) {
                return Str::limit($row->fullName, 40, '...');
            })
            ->addColumn('phone', function ($row) {
                return '<i class="fa fa-phone"></i> '.$row->phone.
                    '<br><i class="fa fa-envelope"></i> '.$row->email;
            })
            ->addColumn('status', function ($row) {
                if(optional($row->customer)->status == 'active'){
                    return '<span class="badge bg-success">Active</span>';
                }
                return '<span class="badge bg-danger">Banned</span>';
            })
            ->addColumn('actions', function ($row) {
                $id = $row->id;
                $status = optional($row->customer)->status;

                return ' 
                    <a href="'.route('admin.customer.edit', $id).'" class="btn btn-outline-info circleIcon" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Edit"> 
                        <i class="fa fa-eye"></i>
                    </a>

                    <button class="btn btn-outline-danger circleIcon"
                        onclick="confirmToggle('.$id.', `'.$status.'`)">
                        <i class="fa fa-ban"></i>
                    </button> 

                    <button class="btn btn-outline-danger circleIcon" onclick="confirmDelete('.$id.')" data-bs-title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>

                    <button onclick="openResetPasswordModal('.$id.', `'.$row->fullName.'`)"
                        class="btn btn-outline-info circleIcon" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Reset Password">
                        <i class="fa fa-key"></i>
                    </button>
                ';
            })
            
            ->rawColumns(['created_at','customer_id','profile','fullname','phone','status','actions'])
            ->make(true);
        }
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
        $user->load(['customer.addresses', 'media']); // Load addresses also

        $customerId = $user->customer->id;

        $orders = Order::fromSub(
            Order::where('customer_id', $customerId)->latest()->limit(10),
            'orders'
        )->paginate(5);

        return view('admin.customer.edit', [
            'user'               => $user,
            'orders'             => $orders,
            'customerId'        => $customerId,
            'totalOrdersCount'   => Order::where('customer_id', $customerId)->count(),
            'totalOrderAmount'   => Order::where('customer_id', $customerId)->sum('payable_amount'),
            'totalDelivered'     => Order::where('customer_id', $customerId)->where('order_status', 'Delivered')->count(),
            'totalCancelled'     => Order::where('customer_id', $customerId)->where('order_status', 'Cancelled')->count(),
            'addresses'          => $user->customer->addresses,
        ]);
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

    public function customerOrders(Request $request, User $user)
    {
        $customer_id = $user->id;

        $query = Order::with([
                'shop',
                'address',
                'orderProducts'   // <-- needed for sum(quantity)
            ])
            ->where('customer_id', $customer_id);

        if ($request->ajax()) {

            return datatables()->of($query)
                ->addIndexColumn()

                ->addColumn('order_date', fn($row) =>
                    $row->created_at?->format('d-m-Y | h:i A')
                )

                ->addColumn('order_id', function ($row) {
                    return 'ORD0' . $row->id;
                })

                ->addColumn('shop_id', function ($row) {
                    return 'STR0' . $row->shop_id;
                })

                ->addColumn('store_name', fn($row) =>
                    $row->shop?->name
                )

                ->addColumn('customer_name', fn($row) =>
                    $row->address?->name
                )

                ->addColumn('customer_phone', fn($row) =>
                    $row->address?->phone
                )

                ->addColumn('total_quantity', fn($row) =>
                    $row->orderProducts->sum('quantity')
                )

                ->addColumn('payable_amount', function ($row) {
                    return '₹' .$row->payable_amount;
                }) 

                ->addColumn('order_status_badge', function ($row) {

                    $status = strtolower($row->order_status->value);

                    return match ($status) {
                        'pending'     => '<span class="badge bg-warning">Pending</span>',
                        'confirm'     => '<span class="badge bg-info">Accepted</span>',
                        'on the way'  => '<span class="badge bg-primary">Shipped</span>',
                        'delivered'   => '<span class="badge bg-success">Delivered</span>',
                        'cancelled'   => '<span class="badge bg-danger">Cancelled</span>',

                        default       => '<span class="badge bg-secondary">' . $row->order_status->value . '</span>',
                    };
                })

                ->addColumn('actions', fn($row) =>
                    '<a href="'.route('shop.order.show',$row->id).'"
                    class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>'
                )

                ->rawColumns(['order_status_badge','actions'])
                ->make(true);
        }

        return view('admin.customer.cust_orders', compact('user'));
    }


}
