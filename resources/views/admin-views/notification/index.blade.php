@php
    use Illuminate\Support\Str;
@endphp
@extends('layouts.admin.app')

@section('title', translate('Send notification'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/push_notification.png') }}" alt="">
                {{ translate('Send notification') }}
            </h2>
        </div>

        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0">
                                {{ translate('Notification List') }}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">
                                    {{ $notifications->total() }}
                                </span>
                            </h3>
                            <div class="d-flex flex-wrap gap-3 align-items-stretch">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group flex-grow-1 max-w-280">
                                        <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                               placeholder="{{ translate('Search by title') }}"
                                               aria-label="{{ translate('Search by title') }}"
                                               value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <button class="btn btn-primary text-nowrap" title="{{ translate('Send notification') }}"
                                        data-bs-toggle="offcanvas" href="#notificationAddOffcanvas">
                                    + {{ translate('Send notification') }}
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless align-middle">
                                <thead class="text-capitalize">
                                    <tr>
                                        <th>{{ translate('Serial') }}</th>
                                        <th>{{ translate('Title') }}</th>
                                        <th>{{ translate('Description') }}</th>
                                        <th class="text-center">{{ translate('Notification count') }}</th>
                                        <th class="text-center">{{ translate('Status') }}</th>
                                        <th class="text-center">{{ translate('Resend') }}</th>
                                        <th class="text-center">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @if(count($notifications) > 0)
                                    @foreach($notifications as $key => $notification)
                                        <tr>
                                            <td>{{ $notifications->firstItem() + $key }}</td>
                                            <td>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <div class="avatar-60 d-flex align-items-center rounded">
                                                        <img class="img-fluid h-100 object-fit-cover w-100 rounded" alt=""
                                                             src="{{ getStorageImages(path: $notification->image_full_url, type: 'backend-basic') }}">
                                                    </div>
                                                    <div class="overflow-hidden max-w-200">
                                                        <div class="fs-14 line-1 max-w-200 text-truncate" data-bs-toggle="tooltip"
                                                             data-bs-placement="right" aria-label="{{ $notification['title'] }}"
                                                             data-bs-title="{{ $notification['title'] }}">{{ $notification['title'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="max-w-300 min-w-200 text-wrap line-2" data-bs-toggle="tooltip" data-bs-placement="top"
                                                     aria-label="{{ $notification['description'] }}"
                                                     data-bs-title="{{ $notification['description'] }}">
                                                    {{ Str::limit($notification['description'], 60) }}
                                                </div>
                                            </td>
                                            <td class="text-center" id="count-{{ $notification['id'] }}">{{ $notification['notification_count'] }}</td>
                                            <td>
                                                <form action="{{ route('admin.retail.notification.update-status') }}" method="post"
                                                      id="notification-status{{ $notification['id'] }}-form"
                                                      class="no-reload-form">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $notification['id'] }}">
                                                    <label class="switcher mx-auto" for="notification-status{{ $notification['id'] }}">
                                                        <input class="switcher_input custom-modal-plugin"
                                                               type="checkbox" value="1" name="status"
                                                               id="notification-status{{ $notification['id'] }}"
                                                               {{ $notification['status'] == 1 ? 'checked' : '' }}
                                                               data-modal-type="input-change-form"
                                                               data-modal-form="#notification-status{{ $notification['id'] }}-form"
                                                               data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/notification-on.png') }}"
                                                               data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/notification-off.png') }}"
                                                               data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Notification Status')]).'?' }}"
                                                               data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Notification Status')]).'?' }}"
                                                               data-on-message="<p>{{ translate('If enabled customers will receive notifications on their devices') }}</p>"
                                                               data-off-message="<p>{{ translate('If disabled customers will not receive notifications on their devices') }}</p>"
                                                               data-on-button-text="{{ translate('Turn On') }}"
                                                               data-off-button-text="{{ translate('Turn Off') }}">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <a href="javascript:" class="btn btn-outline-success icon-btn resend-notification"
                                                       title="{{ translate('Resend notification') }}"
                                                       data-id="{{ $notification['id'] }}">
                                                        <i class="fi fi-rr-refresh"></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-3">
                                                    <a class="btn btn-outline-info icon-btn"
                                                       title="{{ translate('Edit') }}"
                                                       data-bs-toggle="offcanvas" href="#notificationEditOffcanvas-{{ $notification['id'] }}">
                                                        <i class="fi fi-sr-pencil"></i>
                                                    </a>
                                                    <a class="btn btn-outline-danger icon-btn delete-data-without-form"
                                                       title="{{ translate('Delete') }}"
                                                       data-action="{{ route('admin.retail.notification.delete') }}"
                                                       data-id="{{ $notification['id'] }}">
                                                        <i class="fi fi-rr-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive mt-4">
                            <div class="d-flex justify-content-lg-end">
                                {{ $notifications->links() }}
                            </div>
                        </div>

                        @if(count($notifications) == 0)
                            @include('layouts.admin.partials._empty-state', [
                                'text' => 'no_notification_found',
                                'image' => 'default',
                            ])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin-views.notification.offcanvas._notification-add')

    @foreach ($notifications as $notification)
        @include('admin-views.notification.offcanvas._notification-edit', ['notification' => $notification])
    @endforeach

    <span id="get-resend-notification-route-and-text" data-text="{{ translate('Resend notification') }}"
          data-action="{{ route('admin.retail.notification.resend-notification') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/notification.js') }}"></script>
@endpush
