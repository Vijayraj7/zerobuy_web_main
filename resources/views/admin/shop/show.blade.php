@extends('layouts.app')

@section('header-title', __('Shop Details'))

@section('content')

    <div class="container-fluid">

        <div class="card"> 
            <div class="card-body">
                @include('admin.shop.header-nav')

                <div class="row mb-3">
                    <div class="col-lg-4 mt-3">
                        <div class="card rounded-12 position-relative overflow-hidden">

                            <div class="card-body shop p-2">
                                <div class="banner"><img class="img-fit" src="{{ $shop->banner }}" /></div>
                                <div class="main-content">
                                    <div class="logo">
                                        <img class="img-fit" src="{{ $shop->logo }}" /><br>
                                    </div>
                                    <div class="personal"> 
                                        <span class="name h5 mb-1 d-flex justify-content-between align-items-center w-100 gap-2">
                                            {{ $shop->name }}
                                            @if($shop->is_verified==1)  
                                            <i class="fa fa-check-circle" style="color:green"></i>
                                            @else 

                                            @endif 
                                        </span>
                                        <div class="mt-0">
                                            @if($shop->user?->is_active==1)
                                            <span class="badge bg-success clear-badge-font">Active</span> 
                                            @else
                                            <span class="badge bg-danger clear-badge-font">Inactive</span>
                                            @endif

                                            @if($shop->is_branded==1)
                                            <span class="badge bg-success clear-badge-font"><i class="bi bi-shop"></i> Branded Store</span> 
                                            @else 
                                            
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2 align-items-center ">
                                            <div>
                                                @foreach (range(1, 5) as $rating)
                                                    @if ($shop->averageRating >= $rating)
                                                        <i class="fa-solid fa-star text-warning"></i>
                                                    @else
                                                        <i class="fa-regular fa-star text-secondary"></i>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <div>
                                                <span class="fw-bold">{{ $shop->averageRating }}</span>
                                                ({{ $shop->reviews->count() }} {{ __('Reviews') }})
                                            </div>
                                        </div>
                                        <div class="joindate text-muted small">
                                            <i class="fa fa-calendar me-1"></i>
                                            Join Date : {{$shop->created_at->format('d-m-Y | h:i A')}}
                                        </div>
                                        <div class="mt-2">
                                            <a href="/shops/{{ $shop->id }}" target="blank"
                                                class="btn btn-outline-primary btn-sm">
                                                {{ __('View Live') }}
                                            </a>
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ResetPasswordModal">
                                                <i class="bi bi-shield-lock-fill"></i>
                                                {{ __('Reset Password') }}
                                            </button>
                                            @hasPermission('admin.shop.edit')
                                                <a href="{{ route('admin.shop.edit', $shop->id) }}" class="btn btn-outline-primary btn-sm" data-bs-toggle-tooltip="tooltip" data-bs-placement="top" data-bs-title="Edit">
                                                <i class="fa fa-edit"></i>
                                                </a> 
                                            @endhasPermission

                                            @hasPermission('admin.shop.show')
                                                <a href="javascript:void(0);" class="btn btn-outline-primary btn-sm"  data-bs-toggle="modal" data-bs-target="#viewCredentials" data-bs-toggle-tooltip="tooltip" data-bs-placement="top" data-bs-title="View">
                                                <i class="fa fa-eye"></i>
                                                </a>  
                                            @endhasPermission
                                        </div>
                                    </div>
                                </div> 
                            </div>  
                        </div> 
                        <div class="card mt-3">
                            <div class="card-header d-flex align-items-center justify-content-start gap-2 py-3">
                                <i class="bi bi-shop fz-18"></i>
                                <h4 class="fz-20 m-0"> {{ __('Shop Information') }}</h4> 
                            </div>
                            <div class="card-body">
                                <div>
                                    <span class="fw-medium"><b>{{ ucfirst(strtolower($shop->store_type)) }} Store</b> | </span>
                                    <span><b>{{ $shop->district }} , {{ $shop->state }}</b></span>
                                </div>

                                @if(!empty($shop->store_since))
                                <div class="mt-2">
                                    <span class="fw-medium">{{ __('Store Since') }} : </span>
                                    <span><b>{{ date('d-m-Y', strtotime($shop->store_since)) }}</b></span>
                                </div>
                                @endif

                                <div class="mt-2">
                                    <span class="fw-medium">{{ __('Estimated Delivery') }} : </span>
                                    <span><b>{{ $shop->estimated_delivery_time }}</b></span>
                                </div>

                                <div class="mt-2">
                                    <span class="fw-medium">{{ __('Shop ID') }} : <b>STR0{{ $shop->id }}</b></span> | 
                                    <span class="fw-medium">{{ __('GST No.') }} : <b>{{ $shop->gst_number ?? 'No' }}</b></span>
                                    <span></span>
                                </div> 

                                <div class="mt-2">
                                    <span class="fw-medium">{{ __('Shop Description') }} :</span>
                                    <span>{{ $shop->description }}</span>
                                </div>

                                <div class="mt-2">
                                    <span class="badge badge-primary clear-badge-font">₹{{ $shop->min_order_amount }} Min, Order</span>
                                    <!-- <span class="badge badge-info clear-badge-font"><i class="fa fa-refresh"></i> Return Policy</span> -->
                                    <span class="badge badge-info clear-badge-font" role="button" data-bs-toggle="modal" data-bs-target="#termsModal"><i class="fa fa-refresh"></i> Return Policy</span>
                                    <!-- <span class="badge badge-success clear-badge-font"><i class="fa fa-sack-dollar"></i> GST Available</span> -->
                                     <span class="badge {{ $shop->gst_number ? 'badge-success' : 'badge-secondary' }} clear-badge-font"><i class="fa fa-sack-dollar"></i> {{ $shop->gst_number ? 'GST Available' : 'GST Not Available' }} </span>
                                    <span class="badge badge-danger clear-badge-font"><i class="fa fa-award"></i> Truste</span>  
                                </div>
                            </div>
                        </div>
                        <div class="card mt-3">
                            <div class="row mb-1">
                                <div class="col-lg-12 mt-3">
                                    <div class="card-header d-flex align-items-center justify-content-start gap-2 py-3">
                                        <i class="fas fa-crown fz-18"></i>
                                        <h4 class="fz-20 m-0"> {{ __('Subscription Details') }}</h4>
                                    </div>
                                    @php
                                        $percent = $totalDays > 0 ? round(($daysLeft / $totalDays) * 100) : 0;
                                    @endphp 
                                    <div class="card-body">
                                        @if($subscription)
                                            <div class="mt-2">
                                                <span class="fw-medium">Status :
                                                    <b class="text-success">Active</b> |
                                                </span>
                                                <span class="fw-medium">Plan :
                                                    <b>{{ $subscription->plan?->name ?? 'N/A' }}</b>
                                                </span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="fw-medium">Sale Limit :
                                                    <b>{{ $subscription->sale_limit ?? 'Unlimited' }}</b> |
                                                </span>
                                                <span class="fw-medium">Remaining Sales :
                                                    <b>{{ $subscription->remaining_sales ?? 'Unlimited' }}</b>
                                                </span>
                                            </div>

                                            <div class="mt-2">
                                                <span class="fw-medium">Activation :
                                                    <b>{{ optional($subscription->starts_at)->format('d-m-Y') }}</b> |
                                                </span>
                                                <span class="fw-medium">Expire :
                                                    <b>{{ optional($subscription->ends_at)->format('d-m-Y') }}</b>
                                                </span>
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                No Active Subscription
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-1">  
                                    <div class="col-lg-4 mt-3">
                                        <div class="card-body">
                                            @if($subscription)
                                            <div class="justify-content-center align-items-center">
                                                <div class="mt-4 badge bg-primary rounded-pill px-3 py-1 mt-2 mb-2"> 
                                                    <div class="mt-2 mb-2">
                                                        <h5 class="fw-medium text-white">Plan Validity</h5>
                                                        <div class="mt-1">
                                                            <h5 class="text-white"><b>{{ $subscription->plan?->duration ? ucwords(daysToLargestUnit($subscription->plan?->duration)) : 'Unlimited' }}</b></h5>
                                                        </div>
                                                    </div>
                                                </div> 
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-8 mt-1">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <div id="daysLeftChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>  
                        </div>   
                    </div>
                    <div class="col-lg-8 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-4 col-xl-3">
                                        <div class="dashboard-box item-1">
                                            <h2 class="count">{{ showCurrency($totalSales) }}</h2>
                                            <h3 class="title">{{ __('Total Sales') }}</h3>
                                            <div class="icon">
                                                <img src="{{ asset('assets/icons-admin/chart-trend-up-green.svg') }}" alt="icon" loading="lazy" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4 col-xl-3">
                                        <div class="dashboard-box item-2">
                                            <h2 class="count">{{ $shop->products->count() }}</h2>
                                            <h3 class="title">{{ __('Total Products') }}</h3>
                                            <div class="icon">
                                                <img src="{{ asset('assets/icons-admin/dashboard-product.svg') }}" alt="icon" loading="lazy" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4 col-xl-3">
                                        <div class="dashboard-box item-3">
                                            <h2 class="count">{{ $shop->orders->count() }}</h2>
                                            <h3 class="title">{{ __('Total Orders') }}</h3>
                                            <div class="icon">
                                                <img src="{{ asset('assets/icons-admin/dashboard-order.svg') }}" alt="icon" loading="lazy" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4 col-xl-3">
                                        <div class="dashboard-box item-4">
                                            <h2 class="count">{{ showCurrency($shop->user?->wallet?->balance) }}</h2>
                                            <h3 class="title">{{ __('Wallet Balance') }}</h3>
                                            <div class="icon">
                                                <img src="{{ asset('assets/icons-admin/wallet.svg') }}" alt="icon" loading="lazy" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        @php
                            $orderIcons = [
                                'pending'   => asset('assets/icons-admin/clock.svg'),
                                'shipped'   => asset('assets/icons-admin/truck.svg'),
                                'delivered' => asset('assets/icons-admin/box-check.svg'),
                                'cancelled' => asset('assets/icons-admin/shopping-cart-times.svg'),
                                'returned'  => asset('assets/icons-admin/rotate-circle.svg'),
                            ];

                            $orderLabels = [
                                'pending'   => __('Pending'),
                                'shipped'   => __('Shipped'),
                                'delivered' => __('Delivered'),
                                'cancelled' => __('Cancelled'),
                                'returned'  => __('Returned'),
                            ];
                        @endphp

                        <div class="row">
                            <div class="col-lg-3">
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="cardTitleBox">
                                            <h5 class="card-title chartTitle">
                                                {{ __('Order Overview') }}
                                            </h5>
                                        </div>
                                        <div class="orderOverviewList">
                                            @foreach($orderOverview as $key => $count)
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ $orderIcons[$key] }}" width="36">
                                                        <span>{{ $orderLabels[$key] }}</span>
                                                    </div>
                                                    <span class="fw-semibold">{{ $count }}</span>
                                                </div>
                                            @endforeach
                                        </div>    
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9">
                               <div class="card shadow-sm p-4 mt-3"> 
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">

                                            <h6 class="fw-bold mb-0">Sales & Order Summary</h6>

                                            <div class="d-flex gap-1">
                                                <span class="btn btn-primary btn-sm">Sales</span>
                                                <span class="btn btn-success btn-sm">Order</span>
                                                <span class="btn btn-danger btn-sm">Return</span>
                                            </div>

                                            <div class="btn-group p-3">
                                                <button class="btn btn-outline-secondary btn-sm">Today</button>
                                                <button class="btn btn-outline-secondary btn-sm">Week</button>
                                                <button class="btn btn-outline-secondary btn-sm">Month</button>
                                                <button class="btn btn-success btn-sm">Year</button>
                                            </div>
                                        </div>
                                    </div> 

                                    <div id="salesOrderChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESET PASSWORD -->
    <form action="{{ route('admin.shop.reset.password', $shop->id) }}" method="POST">
        @csrf
        <div class="modal fade" id="ResetPasswordModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title fs-5">{{ __('Reset Password') }} ({{ $shop->name }})</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="password1" class="form-label">
                                {{ __('Password') }}
                            </label>
                            <div class="position-relative passwordInput">
                                <input type="password" name="password" id="password1" class="form-control" required="true"
                                    placeholder="Enter Password">
                                <span class="eye" onclick="showHidePassword(1)">
                                    <i class="fa fa-eye-slash fa-eye" id="togglePassword1"></i>
                                </span>
                            </div>
                            @error('password')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password2" class="form-label">
                                {{ __('Confirm Password') }}
                            </label>
                            <div class="position-relative passwordInput">
                                <input type="password" name="password_confirmation" id="password2" class="form-control"
                                    required="true" placeholder="Enter Password again">
                                <span class="eye" onclick="showHidePassword(2)">
                                    <i class="fa fa-eye-slash fa-eye" id="togglePassword2"></i>
                                </span>
                            </div>
                            <span id="passwordMatch" class="text text-danger d-none"></span>
                            @error('password_confirmation')
                                <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="submit" class="btn btn-primary">
                            {{ __('Save changes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form> 

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
                        <input type="text" id="copyMobile" value="{{ $shop->user?->phone }}" class="form-control" readonly>
                        <button class="btn btn-outline-primary" onclick="copyField('copyMobile')">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>

                    <!-- EMAIL -->
                    <div class="input-group mb-2">
                        <input type="text" id="copyEmail" value="{{ $shop->user?->email }}" class="form-control" readonly>
                        <button class="btn btn-outline-primary" onclick="copyField('copyEmail')">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TERMS & CONDITIONS MODAL -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y:auto;">
                    @if (!empty($sellerTerms))
                        {!! $sellerTerms->description !!}
                    @else
                        <p class="text-danger">Terms & Conditions not available.</p>
                    @endif
                    
                    @if ($shop->terms_condition_status == 1)
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" checked disabled>
                            <label class="form-check-label fw-bold text-success">Agreed</label>
                        </div>
                    @endif
                </div> 
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> 
    <script>
        var daysLeft  = {{ $daysLeft }};
        var totalDays = {{ $totalDays }};
        var percent   = totalDays > 0 ? Math.round((daysLeft / totalDays) * 100) : 0;

        new ApexCharts(document.querySelector("#daysLeftChart"), {
            series: [percent], // % only for chart fill
            chart: {
                type: 'radialBar',
                height: 220,
                sparkline: { enabled: true }
            },
            plotOptions: {
                radialBar: {
                    hollow: { size: '65%' },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '14px',
                            offsetY: 25,
                            formatter: () => 'Days Left'
                        },
                        value: {
                            show: true,
                            fontSize: '32px',
                            fontWeight: 700,
                            offsetY: -10,
                            formatter: function () {
                                return daysLeft; // ✅ SHOW REAL DAYS
                            }
                        }
                    }
                }
            },
            stroke: { lineCap: 'round' },
            colors: ['#0A8F6A']
        }).render();
    </script> 

    <!-- <script>
        var options = {
            series: [
                {
                    name: 'Sales',
                    data: [45000, 52000, 95000, 60000, 72000, 38000, 52000, 42000, 30000, 98000, 76000, 62000]
                },
                {
                    name: 'Order',
                    data: [28000, 35000, 62000, 42000, 52000, 30000, 46000, 28000, 22000, 56000, 42000, 36000]
                },
                {
                    name: 'Return',
                    data: [3000, 4500, 7000, 5000, 6000, 3500, 4200, 3200, 2500, 6200, 4800, 3900]
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 6
                }
            },
            colors: ['#1E88E5', '#43A047', '#E53935'],
            dataLabels: { enabled: false },
            stroke: { show: false },
            xaxis: {
                categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: val => '₹ ' + val
                }
            },
            grid: {
                borderColor: '#e0e0e0',
                strokeDashArray: 5
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: val => '₹ ' + val
                }
            }
        };

        new ApexCharts(
            document.querySelector("#salesOrderChart"),
            options
        ).render();
    </script> -->
    <script>
        new ApexCharts(document.querySelector("#salesOrderChart"), {
            series: [
                { name: 'Sales',  data: @json($chartData['sales']) },
                { name: 'Order',  data: @json($chartData['orders']) },
                { name: 'Return', data: @json($chartData['returns']) }
            ],
            chart: { type: 'bar', height: 350, toolbar: { show: false }},
            plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' }},
            colors: ['#1E88E5', '#43A047', '#E53935'],
            xaxis: {
                categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
            },
            dataLabels: { enabled: false }
        }).render();
    </script>


    <script>
        function showHidePassword(num) {
            const toggle = document.getElementById("togglePassword" + num);
            const password = document.getElementById("password" + num);

            // toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // toggle the icon
            toggle.classList.toggle("fa-eye");
        }

        document.getElementById('password2').addEventListener('keyup', function(e) {
            $password1 = document.getElementById('password1').value;
            $password2 = document.getElementById('password2').value;

            $message = document.getElementById('passwordMatch');

            if ($password1 == $password2) {
                document.getElementById('password2').classList.remove('is-invalid');
                $message.classList.add('d-none');
                document.getElementById('submit').disabled = false;
            } else {
                document.getElementById('password2').classList.add('is-invalid');
                $message.classList.remove('d-none');
                $message.innerHTML = "Password doesn't match";
                document.getElementById('submit').disabled = true;
            }
        });

        function copyField(id) {
            let input = document.getElementById(id);
            input.select();
            navigator.clipboard.writeText(input.value);
            showFlash();
        }
    </script>
@endpush
