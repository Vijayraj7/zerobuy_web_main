@extends('layouts.app')

@section('header-title', __('Notifications'))

@section('content')
    <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between px-3">
        <h4>
            {{ __('Notifications') }}
        </h4>
        <button type="button" class="btn btn-outline-danger"
            onclick="document.getElementById('deleteAllAdminNotificationsConfirm').style.display='flex'">
            <i class="bi bi-trash"></i> {{ __('Delete All') }}
        </button>
    </div>

    <form id="deleteAllAdminNotificationsForm" action="{{ route('admin.notification.destroyAll') }}" method="GET" class="d-none"></form>

    <div id="deleteAllAdminNotificationsConfirm"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <div style="width:100%;max-width:420px;background:#fff;border-radius:8px;padding:20px;">
            <h5 style="margin:0 0 10px 0;">Confirm Delete</h5>
            <p style="margin:0 0 16px 0;">Are you sure you want to delete all notifications?</p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary"
                    onclick="document.getElementById('deleteAllAdminNotificationsConfirm').style.display='none'">Cancel</button>
                <button type="submit" form="deleteAllAdminNotificationsForm" class="btn btn-danger">Yes, Delete All</button>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="card rounded-12">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Icon') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Message') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notifications as $notification)
                                        <tr>
                                            <td>
                                                <div class="iconBox {{ $notification->type == 'danger' ? 'cardIcon' : 'pdfIcon' }}">
                                                    <i class="bi {{ $notification->icon }}"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.notification.read', $notification->id) }}" class="text-dark {{ $notification->is_read ? 'text-decoration-line-through' : 'fw-bold' }}">{{ $notification->title }}</a>
                                            </td>
                                            <td>{{ $notification->content }}</td>
                                            <td>
                                                <a href="{{ route('admin.notification.destroy', $notification->id) }}"
                                                    class="btn btn-outline-danger circleIcon deleteConfirm"
                                                    data-bs-toggle="tooltip" data-bs-placement="left"
                                                    data-bs-title="{{__('Delete')}}">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{ $notifications->links() }}

            </div>
        </div>
    </div>
@endsection
