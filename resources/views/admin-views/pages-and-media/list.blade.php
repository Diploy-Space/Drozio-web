@extends('layouts.admin.app')

@section('title', translate('Page Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/system-setting.png') }}" alt="">
                {{ translate('Pages & Media Setup') }}
            </h2>
        </div>

        @include('admin-views.pages-and-media._pages-and-media-inline-menu')

        <div class="d-flex flex-column gap-20 mb-20">
            <div>
                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                    <span>
                        {{ translate('Add, edit and enable or disable your business pages.') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">
                        {{ translate('Business page list') }}
                    </h3>

                    <div class="d-flex gap-10 justify-content-sm-end">
                        <form action="{{ route('admin.settings.pages-and-media.list') }}" method="get">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="search" class="form-control" name="search"
                                           value="{{ request('search') }}"
                                           placeholder="{{ translate('Search by name') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div>
                            <a class="btn btn-primary btn-sm text-nowrap" href="{{ route('admin.settings.pages-and-media.add') }}">
                                <i class="fi fi-sr-add"></i>
                                {{ translate('Add New Page') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive pb-3">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Serial')}}</th>
                            <th>{{ translate('Page Name') }}</th>
                            <th class="text-center">{{ translate('Availability') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($businessPages as $key => $businessPage)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 text-capitalize">
                                        {{ $businessPage['title'] }}
                                        @if($businessPage['default_status'])
                                            <span class="badge text-bg-info badge-info badge-sm">
                                                {{ translate('Default') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if (in_array($businessPage['slug'], ['terms-and-conditions', 'about-us', 'privacy-policy']))
                                        <label class="switcher mx-auto" id="default-language-status-alert"
                                               data-bs-toggle="tooltip"
                                               data-bs-title="{{ translate('This status cannot be changed because the page is a default page.') }}">
                                            <input type="checkbox" class="switcher_input" checked disabled>
                                            <span class="switcher_control"></span>
                                        </label>
                                    @else
                                        <form action="{{ route('admin.settings.pages-and-media.update-status') }}"
                                              method="post" id="language-id-{{ $businessPage['id'] }}-form" class="no-reload-form reload-true">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $businessPage['id'] }}" required>
                                            <label class="switcher mx-auto" for="language-id-{{$businessPage['id']}}">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" value="1" name="status"
                                                    id="language-id-{{$businessPage['id']}}"
                                                    {{ $businessPage['status'] == 1 ? 'checked' : '' }}
                                                    data-modal-type="input-change-form"
                                                    data-modal-form="#language-id-{{ $businessPage['id'] }}-form"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/self-registrations-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/self-registrations-off.png') }}"
                                                    data-on-title="{{ translate('Want to Turn ON').' '. $businessPage['title'].' '.translate('Status').'?' }}"
                                                    data-off-title="{{ translate('Want to Turn OFF').' '. $businessPage['title'].' '.translate('Status').'?' }}"
                                                    data-on-message="<p>{{ translate('If enabled') }}, {{ translate('This page will be available throughout the user section.') }}</p>"
                                                    data-off-message="<p>{{ translate('If disabled') }}, {{ translate('This page will be unavailable throughout the user section.') }}</p>"
                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-3">
                                   <span class="{{ $businessPage['status'] != 1 ? 'd-inline-block' : '' }}"
                                         @if($businessPage['status'] != 1)
                                            data-bs-toggle="tooltip"
                                            data-bs-title="{{ translate('This page is not available on the website, please turn on the status.') }}"
                                          @endif
                                   >
                                        <a class="btn btn-outline-primary  icon-btn {{ $businessPage['status'] != 1 ? 'disabled' : '' }}"
                                           target="_blank"
                                           href="{{ $businessPage['status'] == 1 ? route('business-page.view', ['slug' => $businessPage['slug']]) : '#' }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </a>
                                    </span>
                                        <a class="btn btn-outline-info icon-btn"
                                           href="{{route('admin.settings.pages-and-media.update', ['slug' => $businessPage['slug']]) }}">
                                            <i class="fi fi-sr-pencil"></i>
                                        </a>

                                        @if (!$businessPage['default_status'])
                                            <button data-bs-toggle="modal"
                                                    data-bs-target="#cleanErrorLog{{ $businessPage['id'] }}"
                                                    type="button"
                                                    class="btn btn-outline-danger btn-outline-danger-dark icon-btn">
                                                <i class="fi fi-rr-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    @foreach($businessPages as $key => $businessPage)
    <div class="modal fade" id="cleanErrorLog{{ $businessPage['id'] }}" tabindex="-1"
         aria-labelledby="cleanErrorLog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button"
                            class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <form action="{{ config('app.mode') != 'demo' ? route('admin.settings.pages-and-media.delete') : 'javascript:'}}"
                          method="post">
                        @csrf
                        <input type="hidden" name="slug" value="{{ $businessPage['slug'] }}">
                        <div class="d-flex flex-column align-items-center text-center mb-30">
                            <img
                                src="{{dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png')}}"
                                width="80" class="mb-20" id="" alt="">
                            <h2 class="modal-title mb-3">
                                {{ translate('Want to delete this page').' ?' }}
                            </h2>
                            <div class="text-center">
                                {{ translate('Are you sure you want to delete the page').' ?' }}
                                {{ translate('It will be permanently deleted from your database') }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-secondary max-w-120 flex-grow-1"
                                    data-bs-dismiss="modal">
                                {{ translate('No') }}
                            </button>
                            <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                    class="btn btn-danger max-w-120 flex-grow-1 {{ getDemoModeFormButton(type: 'class') }}"
                                    data-bs-dismiss="modal">
                                {{ translate('Yes Delete') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @include("layouts.admin.partials.offcanvas._page-setup")
@endsection
