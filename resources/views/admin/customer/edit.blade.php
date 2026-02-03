@extends('layouts.app')

@section('content')
<div class="container-fluid my-3">
    <div class="row gy-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header py-3">
                    <h4 class="card-title m-0">{{ __('Customer Details') }}</h4>
                </div>
                <div class="card-body">
                    <div class="card shadow-sm rounded-12 show-card position-relative overflow-hidden">
                        <div class="card-body shop"> 
                            <div class="card mb-3" style="border-radius: .5rem;">
                                <div class="row g-0">
                                    <div class="col-md-4 gradient-custom text-center text-white" style="border-radius:4px;">
                                        <img src="{{$user->thumbnail}}" alt="Avatar" class="img-fluid mt-4 mb-3" style="width: 100px; border-radius:50px;"> 
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body p-4">
                                            <h5 class="name mb-0 fw-bold">{{$user->name}}</h5>
                                            <hr class="mt-1 mb-1">
                                            <div class="joindate text-muted small">
                                                <i class="fa fa-id-badge me-2"></i>
                                                Customer ID : <span>CST0</span>{{$customerId}}
                                            </div>
                                            <div class="joindate text-muted small">
                                                <i class="fa fa-calendar me-1"></i>
                                                Join Date : {{$user->created_at->format('d-m-Y | h:i A')}}
                                            </div>
                                            <!-- <div class="joindate text-muted small">
                                                <i class="fa fa-clock me-1"></i>
                                                Time : {{$user->created_at->format('h:i A')}}
                                            </div> -->
                                            <div class="action-buttons d-flex gap-1 mt-2">
                                                <!-- <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="view credentials">
                                                    <i class="fa fa-eye"></i>
                                                </a> -->
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewCredentials">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> 

                            <div class="d-flex flex-column gap-2 mt-2">
                                <div class="item">
                                    <div class="personal mt-2">
                                        <h5>{{ _('Total Ordered Amount') }}</h5>
                                        <h3>₹ {{ number_format($totalOrderAmount, 2) }}</h3>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Phone</strong>
                                    <span>{{$user->phone}}</span>
                                </div>
                                <div class="item">
                                    <strong>Status</strong>
                                    @if($user->customer->status=='active')
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Banned</span>
                                    @endif
                                </div>
                                <div class="item">
                                    <strong>Total Following Store</strong>
                                    <div class="ms-2">
                                        <a href="{{route('admin.customer.following-shop', $customerId)}}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see following stores">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <span class="btn btn-secondary btn-sm">{{ $totalFollowingStores }}</span>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Orders</strong>
                                    <div class="ms-2">
                                        <a href="{{route('admin.customer.orders', $customerId)}}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see all orders">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <span class="btn btn-secondary btn-sm">{{ $totalOrdersCount }}</span>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Deivered Orders</strong>
                                    <div class="ms-2">
                                        <span class="btn btn-secondary btn-sm">{{ $totalDelivered }}</span>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Cancelled Order</strong>
                                    <div class="ms-2">
                                        <span class="btn btn-secondary btn-sm">{{ $totalCancelled }}</span>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong class="text-danger">Total Returned Orders</strong>
                                    <div class="ms-2">
                                        <a href="{{route('admin.customer.return-orders', $customerId)}}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see returned orders">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <span class="btn btn-secondary btn-sm">{{ $totalReturnOrders }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="filter cm-content-box box-primary">
                    <div class="cm-content-body form excerpt rounded-0">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                            <h4 class="m-0">{{ __('Latest Orders') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="#" class="table border table-responsive-lg">
                                    <thead>
                                        <tr class="text-center">
                                            <!-- <th>#</th> -->
                                            <th class="text-center">{{ __('Order ID') }}</th>
                                            <th class="text-center">{{ __('Order Date') }}</th>
                                            <th>{{ __('Order Amount') }}</th>
                                            <th class="text-center">{{ __('Status') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                        <tr class="text-center">
                                            <!-- <td></td> -->
                                            <td>ORD{{ $order->id }}</td>
                                            <td>{{ $order->created_at->format('d-m-Y | h:i A') }}</td>
                                            <td>₹ {{ $order->payable_amount }}</td>
                                            <td>
                                                @php
                                                    $status = strtolower(trim($order->order_status->value));
                                                @endphp
                                                @php
                                                    echo match($status) {
                                                        'pending' => '<span class="badge bg-warning">Pending</span>',
                                                        'confirm' => '<span class="badge bg-info">Confirm</span>',
                                                        'shipped' => '<span class="badge bg-primary">Shipped</span>',
                                                        'delivered' => '<span class="badge bg-success">Delivered</span>',
                                                        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                                                        default => '<span class="badge bg-secondary">'.
                                                            $order->order_status->value .'</span>',
                                                    };
                                                @endphp
                                            </td>
                                            <td>
                                                @hasPermission('shop.order.show')
                                                    <a href="{{ route('shop.order.show', $order->id) }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{__('view order details')}}" class="circleIcon btn-outline-primary svg-bg">
                                                        <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="icon" loading="lazy" />
                                                    </a>
                                                @endhasPermission
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $orders->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div> 

            <div class="card mt-3">
                <div class="card-header py-3">
                    <h5 class="m-0">Customer Addresses</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        @forelse($addresses as $address)
                            <div class="col-md-6">
                                <div class="card shadow-sm p-3" style="border-radius:10px;">
                                    <h6 class="fw-bold mb-1">{{ $address->name }}</h6>
                                    <!-- <h6 class="fw-bold mb-1">{{ $address->address_line }}</h6> -->
                                    <p class="mb-1">{{ $address->address_line }} {{ $address->address_line2 }}</p>
                                    <!-- <p class="mb-1">{{ $address->address_line2 }}</p> -->
                                    <p class="mb-1">{{ $address->state }} {{ $address->area }} </p>
                                    <p class="mb-1">{{ $address->post_code }}</p>
                                    <p class="mb-1">Mobile: {{ $address->phone }}</p>

                                    <div class="mt-2">
                                        @if($address->is_default)
                                            <span class="badge bg-success p-2">
                                                <i class="fa fa-check-circle me-1"></i>
                                                Default Delivery Address
                                            </span>
                                        @else
                                            <span class="p-2"></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No addresses found.</p>
                        @endforelse

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
        <form action="{{ route('admin.customer.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <x-input label="First Name" name="name" type="text"
                                    placeholder="Enter Name" required="true" :value="$user->name" />
                                </div>

                                <div class="col-md-6 mt-3">
                                    <x-input label="Last Name" name="last_name" type="text"
                                    placeholder="Enter Name" :value="$user->last_name" />
                                </div>
                            </div>

                            <div class="mt-3">
                                @php
                                    $phone = str_replace(['(', ')', '-', '+'], '', $user->phone);
                                @endphp
                                <x-input type="number" label="Phone Number" name="phone" placeholder="Enter phone number" required="true" :value="$phone" />
                            </div>

                            <div class="mt-3">
                                <x-input type="email" name="email" label="Email" placeholder="Enter Email Address" :value="$user->email" />
                            </div>

                            <!-- <div class="mt-3">
                                <x-select label="Gender" name="gender">
                                    <option value="male" @selected($user->gender == 'male')>Male</option>
                                    <option value="female" @selected($user->gender == 'female')>Female</option>
                                </x-select>
                            </div> -->
                        </div>
                        <div class="col-lg-5">
                            <div class="mt-3 text-center">
                                <img id="modalPreviewProfile" src="{{ $user->thumbnail ?? 'https://placehold.co/500x500/png' }}" class="img-fluid rounded" alt="photo" style="max-width: 180px;">
                            </div>
                            <div class="mt-2">
                                <x-file name="profile_photo" label="User profile (Ratio 1:1)" preview="modalPreviewProfile" data-preview="modalPreviewProfile" />
                            </div>
                            <!-- <div class="mt-3">
                                <x-input type="date" name="date_of_birth" label="Date of Birth" :value="$user->date_of_birth" />
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- VIEW CUSTOMER CREDENTIALS MODAL -->
<div class="modal fade" id="viewCredentials" tabindex="-1" aria-labelledby="viewCredentialsLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="viewCredentialsLabel">{{ __('View Credentials') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <!-- MOBILE -->
             <div id="copyFlash" style="display:none; background:white; color:#28a745; padding:2px 2px; z-index:9999;text-align:center;"> Copied! </div> 
            <div class="input-group mb-2">
                <input type="text" id="copyMobile" value="{{ $user->phone }}" class="form-control" readonly>
                <button class="btn btn-outline-primary" onclick="copyField('copyMobile')">
                    <i class="fa fa-copy"></i>
                </button>
            </div>

            <!-- EMAIL -->
            <div class="input-group mb-2">
                <input type="text" id="copyEmail" value="{{ $user->email }}" class="form-control" readonly>
                <button class="btn btn-outline-primary" onclick="copyField('copyEmail')">
                    <i class="fa fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener("change", function (event) {
        if (event.target.type === "file") {
            const input = event.target;
            const previewId = input.getAttribute("preview") || input.getAttribute("data-preview") || "modalPreviewProfile";
            const previewEl = document.getElementById(previewId);

            if (!previewEl) return;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewEl.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    });

    function showFlash() {
        let flash = document.getElementById('copyFlash');
        flash.style.display = 'block';

        setTimeout(() => {
            flash.style.display = 'none';
        }, 2000);
    }
    function copyField(id) {
        let input = document.getElementById(id);
        input.select();
        navigator.clipboard.writeText(input.value);
        showFlash();
    }
</script>

@endpush
