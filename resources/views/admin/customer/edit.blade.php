@extends('layouts.app')

@section('content')
<div class="container-fluid my-3">
    <div class="row gy-3">

        <div class="col-md-4">
            <div class="card">
                <div class="card-header py-3">
                    <h4 class="card-title m-0">
                        {{ __('Customer Details') }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="card shadow-sm rounded-12 show-card position-relative overflow-hidden">
                        <div class="card-body shop"> 
                            <div class="main-content mt-5">
                                <div class="logo">
                                    <img class="img-fit" src="{{$user->thumbnail}}">
                                </div>
                                <div class="personal">
                                    <span class="name">{{$user->name}}</span>
                                    <span class="joindate">Join Date : {{$user->created_at->format('d-m-Y | h:i A')}}</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2 px-3 mt-2">
                                <div class="item"> 
                                    <div class="personal mt-2">
                                        <h5>{{ _('Total Ordered Amount') }}</h5>
                                        <h3 >₹ {{$totalOrderAmount}}</h3>
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
                                        <span class="btn btn-secondary btn-sm">0</span>
                                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see following stores">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Orders</strong>
                                    <div class="ms-2">
                                        <span class="btn btn-secondary btn-sm">0</span>
                                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see following stores">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Deivered Orders</strong>
                                    <div class="ms-2">
                                        <span class="btn btn-secondary btn-sm">0</span>
                                        <!-- <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see following stores">
                                            <i class="fa fa-eye"></i>
                                        </a> -->
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Cancelled Order</strong>
                                    <div class="ms-2">
                                        <span class="btn btn-secondary btn-sm">0</span>
                                        <!-- <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see following stores">
                                            <i class="fa fa-eye"></i>
                                        </a> -->
                                    </div>
                                </div>
                                <div class="item">
                                    <strong>Total Returned Orders</strong>
                                    <div class="ms-2">
                                        <span class="btn btn-secondary btn-sm">0</span>
                                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="see following stores">
                                            <i class="fa fa-eye"></i>
                                        </a>
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
                                            <td>₹ {{ $order->total_amount }}</td>
                                            <td>
                                                @if($order->order_status=='Pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @elseif($order->order_status=='Confirm')
                                                <span class="badge bg-info">Confirm</span>
                                                @elseif($order->order_status=='Cancelled')
                                                <span class="badge bg-danger">Cancelled</span>
                                                @elseif($order->order_status=='Delivered')
                                                <span class="badge bg-success">Delivered</span>
                                                @else
                                                <span class="badge bg-info">{{ $order->order_status }}</span>
                                                @endif
                                                <!-- {{ $order->order_status }} -->
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
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
        </div>

    </div>
</div>

<!-- <div class="container-fluid my-md-0 my-4">
    <form action="{{ route('admin.customer.update', $user->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <div class="row h-100vh">
            <div class="col-12 m-auto">
                <div class="card rounded-12 border-0 shadow-md">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <h3 class="m-0">{{ __('Edit Customer') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mt-3">
                                            <x-input label="First Name" name="name" type="text" placeholder="Enter Name"
                                                required="true" :value="$user->name" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mt-3">
                                            <x-input label="Last Name" name="last_name" type="text"
                                                placeholder="Enter Name" :value="$user->last_name" />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    @php
                                    $phone = $user->phone;
                                    $phone = str_replace('(', '', $phone);
                                    $phone = str_replace(')', '', $phone);
                                    $phone = str_replace('-', '', $phone);
                                    $phone = str_replace('+', '', $phone);
                                    @endphp
                                    <x-input label="Phone Number" name="phone" type="number"
                                        placeholder="Enter phone number" required="true" :value="$phone" />
                                </div>
                                <div class="mt-3">
                                    <x-input type="email" name="email" label="Email" placeholder="Enter Email Address"
                                        :value="$user->email" />
                                </div>

                                <div class="mt-3">
                                    <x-select label="Gender" name="gender">
                                        <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>
                                            {{ __('Male') }}
                                        </option>
                                        <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>
                                            {{ __('Female') }}
                                        </option>
                                    </x-select>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="mt-3 d-flex align-items-center justify-content-center">
                                    <div class="ratio1x1">
                                        <img id="previewProfile"
                                            src="{{ $user->thumbnail ?? 'https://placehold.co/500x500/png' }}"
                                            alt="photo" width="100%">
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <x-file name="profile_photo" label="User profile (Ratio 1:1)"
                                        preview="previewProfile" />
                                </div>

                                <div class="mt-3">
                                    <x-input type="date" name="date_of_birth" label="Date of Birth"
                                        placeholder="Enter Date of Birth" :value="$user->date_of_birth" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <a href="{{ route('admin.customer.index') }}" class="btn btn-lg btn-outline-secondary">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-lg btn-primary">
                            {{ __('Update') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div> -->
@endsection