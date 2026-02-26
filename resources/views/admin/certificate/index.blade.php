@extends('layouts.app')

@section('header-title', __('Shop Certificates'))

@section('content')

<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="w-100 page-title-heading d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                {{__('Certificates')}}
                <div class="page-title-subheading">
                    {{__('Certificate Lists')}}
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center gap-md-4">  
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#certificateModal"><i class="fa fa-plus"></i> Add Certificate </button> 
            </div>
        </div>
    </div>
</div>   

<div class="container-fluid mt-3">
    <div class="mb-3 card">
        <div class="card-body"> 
            <h4 class="m-0 mt-4">{{ __('All Certificates') }}</h4> 

            <div class="d-flex flex-wrap align-items-center gap-3 mt-3 px-2">
                <!-- Date Filters -->
                <div class="d-flex align-items-center gap-2">
                    <label>From:</label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label>To:</label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                <!-- Reload Button -->
                <button id="reloadBtn" class="btn btn-secondary">
                    <i class="fa fa-rotate-right"></i>
                </button>
                <!-- Export Buttons -->
                <div id="exportButtons"></div>
            </div>


            <div class="table-responsive mt-3"> 
                <table id="certificateTable" class="table table-bordered mt-3 datatableCustomCSS">
                    <thead>
                        <tr> 
                            <th>#</th>
                            <th>{{ __('Create Date') }}</th>
                            <th>{{ __('Thumbnail') }}</th>
                            <th>{{ __('Store ID') }}</th> 
                            <th>{{ __('Store Name') }}</th>  
                            <th>{{ __('State') }}</th>
                            <th>{{ __('Total Products') }}</th>
                            <th>{{ __('Total Orders') }}</th>
                            <th>{{ __('Subscription') }}</th>
                            <th>{{ __('Certificate') }}</th>  
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div> 
</div>

<!-- MODAL -->
<div class="modal fade" id="certificateModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="certificateForm"> @csrf
                <div class="modal-header"> 
                    <h4 class="modal-title">Add/Edit Certificates</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div> 

                <div class="modal-body">
                    <div class="mb-3 d-flex gap-3">
                        <select class="form-control" name="shop_id" id="shop_id" required>
                            <option value="">-- Select Store --</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">
                                    STR0{{$store->id}} - {{ $store->name }}
                                </option>
                            @endforeach
                        </select> 
                    </div>
                    
                    <div class="mt-3 d-flex align-items-center justify-content-center">
                        <div class="ratio1x1">
                            <img class="img-fluid rounded" id="previewCertificate" src="https://placehold.co/500x500/f1f5f9/png" alt="" width="100%">
                        </div>
                    </div>

                    <div class="mt-3"> 
                        <label>Upload Certificate</label>
                        <input type="file" name="certificate" id="certificateInput" class="form-control mt-2" accept="image/*" {{ empty($certificate) ? 'required' : '' }}>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div> 
@endsection

@push('scripts') 
    <script>
        $(document).ready(function () {
            $('#shop_id').select2({
                dropdownParent: $('#certificateModal'),
                width: '100%'
            });
        }); 

        // DataTable 
        $(function() {  
            let startDate = "";
            let endDate = "";

            let table = $('#certificateTable').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,

                ajax: {
                    url: "{{ route('admin.certificate.index') }}",
                    data: function(d) { 
                        d.startDate = startDate;
                        d.endDate = endDate;
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'create_date', name: 'start_date', orderable: false, searchable: false },
                    { data: 'shop_thumbnail', orderable: false, searchable: false },
                    { data: 'store_code', name: 'store_code' },                 
                    { data: 'store_name', name: 'store_name' }, 
                    { data: 'state', name: 'state' }, 
                    { data: 'total_products', name: 'total_products' },
                    { data: 'total_orders', name: 'total_orders' }, 
                    { data: 'subscription', name: 'subscription' },   
                    { data: 'certificate_image', orderable: false },
                    { data: 'status', name: 'status' }, 
                    { data: 'actions', orderable: false, searchable: false },  
                ],

                order: [[2, 'desc']],
                dom:'<"row"' + '<"col-md-6 d-flex align-items-center" l>' + '<"col-md-6 d-flex justify-content-end" f>' + '>' + 'rtip',
                // dom: 'Bfrtip',
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
                initComplete: function () {
                    table.buttons().container().appendTo('#exportButtons');
                },
            }); 

            // Auto Date Filtering
            $('#startDate, #endDate').change(function() {
                startDate = $('#startDate').val();
                endDate   = $('#endDate').val();
                table.ajax.reload(null, false);
            });

            // Reload Button
            $('#reloadBtn').click(function () {
                $('#startDate').val('');
                $('#endDate').val('');
                startDate = "";
                endDate = "";  
                table.ajax.reload();
            });
        });  

        $('#certificateForm').submit(function (e) {
            e.preventDefault();

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('admin.certificate.store') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#certificateModal').modal('hide');
                    $('#certificateTable').DataTable().ajax.reload();
                    Toast.fire({ icon: 'success', title: res.message });
                },
                error: function (xhr) {
                    Toast.fire({
                        icon: 'error',
                        title: xhr.responseJSON.message ?? 'Validation error'
                    });
                }
            });
        });

        $(document).on('click', '.editBtn', function () {
            let id = $(this).data('id');
            resetCertificateForm();

            $.get("{{ url('admin/certificates') }}/" + id, function (res) {
                $('#certificateForm').append(
                    '<input type="hidden" name="id" value="' + res.id + '">'
                );

                $('#shop_id').val(res.shop_id).trigger('change');
                $('#certificateInput').prop('required', false);

                if (res.image) {
                    $('#previewCertificate').attr('src', res.image);
                }

                $('#certificateModal').modal('show');
            });
        });


        // $(document).on('click', '.editBtn', function () {
        //     let id = $(this).data('id');

        //     $.get("{{ url('admin/certificates') }}/" + id, function (res) {
        //         $('#certificateForm')[0].reset();
        //         $('#certificateForm').append('<input type="hidden" name="id" value="'+res.id+'">');

        //         $('#shop_id').val(res.shop_id).trigger('change');

        //         if (res.image) {
        //             $('#previewCertificate').attr('src', res.image);
        //         }

        //         $('#certificateModal').modal('show');
        //     });
        // });

        $(document).on('change', '.toggle-status', function () {
            let id = $(this).data('id');
            $.post("{{ url('admin/certificates/status') }}/" + id, {
                _token: "{{ csrf_token() }}"
            });
        });

        $(document).on('click', '.deleteBtn', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This certificate will be permanently deleted",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('admin/certificates') }}/" + id,
                        method: "DELETE",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function () {
                            $('#certificateTable').DataTable().ajax.reload();
                            Swal.fire('Deleted!', 'Certificate removed.', 'success');
                        }
                    });
                }
            });
        }); 

        $('#certificateInput').on('change', function (e) {
            let reader = new FileReader();
            reader.onload = e => $('#previewCertificate').attr('src', e.target.result);
            reader.readAsDataURL(this.files[0]);
        });

        function resetCertificateForm() {
            $('#certificateForm')[0].reset();
            $('#certificateForm input[name="id"]').remove();
            $('#shop_id').val('').trigger('change');
            $('#previewCertificate').attr(
                'src',
                'https://placehold.co/500x500/f1f5f9/png'
            );
            $('#certificateInput').prop('required', true);
        }

        // When clicking Add button
        $('[data-bs-target="#certificateModal"]').on('click', function () {
            resetCertificateForm();
        });

        // When modal closed
        $('#certificateModal').on('hidden.bs.modal', function () {
            resetCertificateForm();
        });



    </script>
@endpush
