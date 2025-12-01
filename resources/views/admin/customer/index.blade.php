@extends('layouts.app')
@section('content')

<div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
    <h4>{{ __('All Customers') }}</h4>
    @hasPermission('admin.customer.create')
    <a href="{{ route('admin.customer.create') }}" class="btn py-2 btn-primary">
        <i class="bi bi-patch-plus"></i>
        {{ __('Add Customer') }}
    </a>
    @endhasPermission
</div>

<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body">
            
            <ul class="nav nav-tabs mt-3"> <!-- status ul label added by ancy -->
                @php
                use App\Enums\CustomerStatus;
                $CustomerStatuses = CustomerStatus::cases();
                @endphp
                <li class="nav-item">
                    <a href="{{ route('admin.customer.index') }}"
                    class="nav-link {{ request('status') == null ? 'active' : '' }}">
                        All
                    </a>
                </li>

                @foreach ($CustomerStatuses as $status)
                    <li class="nav-item">
                        <a href="{{ route('admin.customer.index', ['status' => $status->value]) }}"
                        class="nav-link {{ request('status') == $status->value ? 'active' : '' }}">
                            {{ ucfirst($status->value) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive">
                <form method="GET" id="filterForm">
                    <div class="d-flex gap-2 align-items-center flex-wrap mt-3">
                        <!-- Search Date -->
                        <input type="date" name="date" value="{{ request('date') }}" class="form-control"
                            style="width:200px;">
                        <!-- Search -->
                        <button class="btn btn-primary"><i class="fa fa-search"></i></button>
                        <!-- Reset -->
                        <a href="{{ route('admin.customer.index') }}" class="btn btn-danger"><i class="fa fa-refresh"></i></a>
                        <!-- Export Placeholder -->
                        <span id="exportContainer" class="ms-2"></span>
                    </div>
                </form>
                <table id="customerTable" class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Joined Date') }}</th>
                            <th>{{ __('Customer ID') }}</th>
                            <th>{{ __('Profile') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Phone/Email') }}</th>
                            <th>{{ __('Total Orders') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <form action="" method="POST" id="resetPasswordForm">
        @csrf
        <div class="modal fade" id="resetPasswordModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title fs-5">{{ __('Reset Password') }} <span id="userName"></span></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="password1" class="form-label">
                                {{ __('Password') }}
                            </label>
                            <div class="position-relative passwordInput">
                                <input type="password" name="password" id="password1" class="form-control" required="true" placeholder="Enter Password">
                                <span class="eye" onclick="showHidePassword(1)">
                                    <i class="fa fa-eye-slash" id="togglePassword1"></i>
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
                                    <i class="fa fa-eye-slash" id="togglePassword2"></i>
                                </span>
                            </div>
                            <span id="passwordMatch" class="text text-danger d-none"></span>
                            @error('password_confirmation')
                            <p class="text text-danger m-0">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('Close') }}
                        </button>
                        <button type="submit" id="submit" class="btn btn-primary">
                            {{ __('Save changes') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')

<script>
    $(function() {
        var table = $('#customerTable').DataTable({
            responsive: true,
            processing: false,
            serverSide: true, 
            ajax: {
                url: "{{ route('admin.customer.data') }}",
                data: function (d) {
                    d.date = $('input[name="date"]').val();
                    d.status = "{{ request('status') }}";
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'created_at', name: 'id' } ,
                { data: 'customer_id', name: 'customer_id' },
                { data: 'profile', orderable: false, searchable: false },
                { data: 'fullname', name: 'name' },
                { data: 'phone', name: 'email' },
                { data: 'orders_count', name: 'orders_count', searchable: false },
                { data: 'status', orderable: false },
                { data: 'actions', orderable: false, searchable: false },
            ],
            order: [[2, 'desc']],
            // dom: '<"row mb-2 mt-2"'
            //     +'<"col-md-6 d-flex align-items-center" l<"ms-3" B>>' 
            //     +'<"col-md-6 d-flex justify-content-end align-items-center" f>'
            // +'>rtip',
            dom:'<"row mb-2 mt-2"' +
                    '<"col-md-4 d-flex align-items-center" l>' +
                    '<"col-md-4 d-flex justify-content-center" B>' +
                    '<"col-md-4 d-flex justify-content-end" f>' +
                '>' +
                'rtip',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-primary',
                    text: '<i class="fas fa-download"></i> Export',
                    buttons: [
                        { extend: 'copy', text: '<i class="fas fa-copy"></i> Copy' },
                        { extend: 'csv', text: '<i class="fas fa-file-csv"></i> CSV' },
                        { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel' },
                        { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF' },
                        { extend: 'print', text: '<i class="fas fa-print"></i> Print' }
                    ]
                }
            ],
            lengthMenu: [
                [20, 40, 50, 100, -1],
                [20, 40, 50, 100, "All"]
            ],
            initComplete: function () {
                table.buttons().container().appendTo('#exportButtons');
            },
        });
        table.on('init.dt', function () {
            $('.dt-buttons').appendTo('#exportContainer');
        });
    });
</script>

<script>
    function openResetPasswordModal(userId, userName) {
        $('#resetPasswordModal').modal('show');
        $('#userName').html('(' + userName + ')');
        $('#resetPasswordForm').attr('action', `{{ route('admin.customer.reset-password', ':id') }}`.replace(':id', userId));
    }

    function showHidePassword(num) {
        const toggle = document.getElementById("togglePassword" + num);
        const password = document.getElementById("password" + num);

        // toggle the type attribute
        const type = password.getAttribute("type") === "password" ? "text" : "password";
        password.setAttribute("type", type);
        // toggle the icon
        toggle.classList.toggle("fa-eye");
        toggle.classList.toggle("fa-eye-slash");
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

    function confirmToggle(id, status) { //added by ancy
        status = status.toLowerCase(); // Ensure lowercase

        let action = (status === 'active') ? 'Ban' : 'Activate';
        // let confirmButtonColor = (status === 'active') ? '#dc3545' : '#059c33ff';
        let confirmButtonColor = (status === 'active') ? 'var(--bs-danger)' : 'var(--bs-success)';
        Swal.fire({
            title: "Are you sure?",
            text: `Do you want to ${action} this customer?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: `Yes, ${action}`,
            confirmButtonColor: confirmButtonColor,
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/admin/customers/${id}/ban`;
            }
        });
    }
    function confirmDelete(id) {
        Swal.fire({
            title: "Are you sure?",
            text: "This customer will be permanently deleted.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel",
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-danger mx-2",
                cancelButton: "btn btn-secondary mx-2"
            }
        }).then((result) => {
            if (result.isConfirmed) {

                let url = "{{ route('admin.customer.destroy', ':id') }}";
                url = url.replace(':id', id);

                window.location.href = url;
            }
        });
    }
           
</script>
@endpush