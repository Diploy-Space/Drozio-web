@extends('layouts.admin.app')

@section('title', translate('Deal of the day'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/deal_of_the_day.png') }}" alt="">
                {{ translate('Deal of the day') }}
            </h2>
        </div>

        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0">
                                {{ translate('Deal of the day') }}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">
                                    {{ $deals->total() }}
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
                                <button class="btn btn-primary text-nowrap" title="{{ translate('Add new deal') }}"
                                        data-bs-toggle="offcanvas" href="#dealOfTheDayAddOffcanvas">
                                    + {{ translate('Add new deal') }}
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless align-middle">
                                <thead class="text-capitalize">
                                    <tr>
                                        <th>{{ translate('Serial') }}</th>
                                        <th>{{ translate('Title') }}</th>
                                        <th>{{ translate('Product Information') }}</th>
                                        <th class="text-center">{{ translate('Status') }}</th>
                                        <th class="text-center">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @if(count($deals) > 0)
                                    @foreach($deals as $key => $deal)
                                        <tr>
                                            <td>{{ $deals->firstItem() + $key }}</td>
                                            <td>
                                                <div class="max-w-250 text-truncate fw-semibold" data-bs-toggle="tooltip"
                                                     data-bs-placement="right" aria-label="{{ $deal['title'] }}"
                                                     data-bs-title="{{ $deal['title'] }}">{{ $deal['title'] }}</div>
                                            </td>
                                            <td>
                                                @if($deal->product)
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <div class="avatar-60 d-flex align-items-center rounded">
                                                            <img class="img-fluid h-100 object-fit-cover w-100 rounded" alt=""
                                                                 src="{{ getStorageImages(path: $deal->product->thumbnail_full_url, type: 'backend-basic') }}">
                                                        </div>
                                                        <div class="overflow-hidden max-w-200">
                                                            <div class="fs-14 line-1 max-w-200 text-truncate" data-bs-toggle="tooltip"
                                                                 data-bs-placement="right" aria-label="{{ $deal->product->name }}"
                                                                 data-bs-title="{{ $deal->product->name }}">{{ $deal->product->name }}</div>
                                                        </div>
                                                    </div>
                                                @else
                                                    {{ translate('Not selected') }}
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('admin.retail.deal.day-status-update') }}" method="post"
                                                      id="deal-of-the-day{{ $deal['id'] }}-form" class="deal-of-the-day-status-form">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $deal['id'] }}">
                                                    <label class="switcher mx-auto" for="deal-of-the-day{{ $deal['id'] }}">
                                                        <input class="switcher_input custom-modal-plugin"
                                                               type="checkbox" value="1" name="status"
                                                               id="deal-of-the-day{{ $deal['id'] }}"
                                                               {{ $deal['status'] == 1 ? 'checked' : '' }}
                                                               data-modal-type="input-change-form"
                                                               data-modal-form="#deal-of-the-day{{ $deal['id'] }}-form"
                                                               data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/deal-of-the-day-status-on.png') }}"
                                                               data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/deal-of-the-day-status-off.png') }}"
                                                               data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Deal of the Day Status')]).'?' }}"
                                                               data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Deal of the Day Status')]).'?' }}"
                                                               data-on-message="<p>{{ translate('Shows this deal of the day on the website and app. Only one deal runs at a time, so the deal running now will be turned off.') }}</p>"
                                                               data-off-message="<p>{{ translate('Hides this deal of the day on the website and app.') }}</p>"
                                                               data-on-button-text="{{ translate('Turn On') }}"
                                                               data-off-button-text="{{ translate('Turn Off') }}">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-3">
                                                    <a class="btn btn-outline-info icon-btn" title="{{ translate('Edit') }}"
                                                       data-bs-toggle="offcanvas" href="#dealOfTheDayEditOffcanvas-{{ $deal['id'] }}">
                                                        <i class="fi fi-sr-pencil"></i>
                                                    </a>
                                                    <a class="btn btn-outline-danger icon-btn delete-data-without-form"
                                                       title="{{ translate('Delete') }}"
                                                       data-action="{{ route('admin.retail.deal.day-delete') }}"
                                                       data-id="{{ $deal['id'] }}">
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
                                {{ $deals->links() }}
                            </div>
                        </div>

                        @if(count($deals) == 0)
                            @include('layouts.admin.partials._empty-state', [
                                'text' => 'no_deal_found',
                                'image' => 'default',
                            ])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin-views.deal.offcanvas._day-add')

    @foreach($deals as $deal)
        @include('admin-views.deal.offcanvas._day-edit', ['deal' => $deal])
    @endforeach
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/search-product.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/deal.js') }}"></script>
@endpush
