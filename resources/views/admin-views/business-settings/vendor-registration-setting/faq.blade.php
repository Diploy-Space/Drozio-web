@extends('layouts.admin.app')

@section('title', translate('FAQs'))
@section('content')
    <div class="content container-fluid">
        @include('admin-views.business-settings.vendor-registration-setting.partial.inline-menu')

        <div class="card mb-3 mb-sm-20">
            <div class="card-body">
                <div class="row align-items-center gy-3">
                    <div class="col-md-9">
                        <div>
                            <h2 class="text-capitalize">{{ translate('FAQs Section') }}</h2>
                            <p class="fs-12 mb-0">
                                {{ translate('In this section you can add some frequently asked questions for vendors') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center gap-3 rounded px-20 py-3 user-select-none bg-section">
                            <span class="fw-semibold text-dark">{{ translate('Status') }}</span>
                            <form action="{{ route('admin.settings.help-topic.feature-status-update') }}" method="post"
                                  id="vendor-registration-faq-status-form" class="no-reload-form">
                                @csrf
                                <label class="switcher mx-auto" for="vendor-registration-faq-status">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="status"
                                        id="vendor-registration-faq-status"
                                        {{ getWebConfig('vendor_registration_faq_status') == 1 ? 'checked' : ''  }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#vendor-registration-faq-status-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/faq-on.png') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/faq-off.png') }}"
                                        data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Faq status')]) }}"
                                        data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Faq status')]) }}"
                                        data-on-message="<p>{{ translate('Turning this on shows the blog in the list.') }}</p>"
                                        data-off-message="<p>{{ translate('Turning this off hides the blog from the list.') }}</p>"
                                        data-on-button-text="{{ translate('Turn On') }}"
                                        data-off-button-text="{{ translate('Turn Off') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column gap-3 gap-sm-20">
                    <div>
                        <h2>{{ translate('Add FAQ') }}</h2>
                        <p>{{ translate('Add as many FAQs as you want from this section.') }}.</p>
                    </div>
                    <form action="{{ route('admin.settings.help-topic.add-new') }}" method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                        @csrf
                        <input hidden name="type" value="vendor_registration">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-3 mb-4">
                                <div class="col-lg-6">
                                    <div class="form-group mb-2">
                                        <label class="form-label" for="">{{ translate('Question') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('A common vendor question about selling on your platform. Keep short.') }}" data-bs-title="{{ translate('A common vendor question about selling on your platform. Keep short.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="text" name="question" class="form-control" placeholder="{{translate('Enter question')}}"  data-required-msg="{{ translate(':field is required', ['field' => translate('Question')]) }}" required="" data-maxlength="50">
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/50</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Priority') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Display order for this FAQ. Lower numbers appear first.') }}" data-bs-title="{{ translate('Display order for this FAQ. Lower numbers appear first.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <div class="select-wrapper">
                                            <select name="ranking" class="form-select bg-white">
                                                @for($index = 1; $index <= 15; $index++)
                                                    <option value="{{ $index }}">{{ $index }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Answer') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('A clear, concise answer that fully addresses the question.') }}" data-bs-title="{{ translate('A clear, concise answer that fully addresses the question.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <textarea class="form-control" name="answer" rows="6" placeholder="{{translate('Write answer').'....'}}" data-maxlength="150"  data-required-msg="{{ translate(':field is required', ['field' => translate('Answer')]) }}" required></textarea>
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/150</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end flex-wrap gap-3">
                                <button type="reset" class="btn btn-secondary px-4 w-120">{{ translate('Reset') }}</button>
                                <button type="submit" class="btn btn-primary px-4 w-120"><i class="fi fi-sr-disk"></i> {{ translate('Save') }}</button>
                            </div>
                        </div>
                    </form>
                    <div class="d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3>
                                {{ translate('FAQ List') }}
                                <span class="badge badge-soft-dark radius-50 fz-12">{{ $helps->count() }}</span>
                            </h3>
                            <form action="" method="get" class="flex-grow-1 max-w-300 min-w-100-mobile">
                                @csrf
                                <div class="input-group">
                                    <input type="search" name="searchValue" class="form-control" placeholder="{{ translate('Search here') }}" value="{{ request('searchValue') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless">
                                <thead class="text-capitalize">
                                    <tr>
                                        <th>{{translate('Serial')}}</th>
                                        <th>{{translate('Question')}}</th>
                                        <th class="min-w-200">{{translate('Answer')}}</th>
                                        <th class="text-center">{{translate('Priority')}}</th>
                                        <th class="text-center">{{translate('Status')}} </th>
                                        <th class="text-center">{{translate('Action')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($helps as $key => $help)
                                        <tr id="data-{{$help['id']}}">
                                            <td>{{$helps->firstItem()+$key}}</td>
                                            <td>
                                                <div class="max-w-200 line-2 text-wrap">
                                                    {{ $help['question'] }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="max-w-280 line-2 text-wrap" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $help['answer'] }}">
                                                    {{ $help['answer'] }}
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $help['ranking'] }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.settings.help-topic.status', ['id'=>$help['id']])}}"
                                                    method="post" id="help-topic-status{{$help['id']}}-form"
                                                    class="helpTopic_status_form no-reload-form">
                                                    @csrf

                                                    <label class="switcher mx-auto" for="help-topic-status{{$help['id']}}">
                                                        <input
                                                            class="switcher_input custom-modal-plugin"
                                                            type="checkbox" value="1" name="status"
                                                            id="help-topic-status{{$help['id']}}"
                                                            {{ $help['status'] == 1 ? 'checked':'' }}
                                                            data-modal-type="input-change-form"
                                                            data-modal-form="#help-topic-status{{$help['id']}}-form"
                                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/faq-on.png') }}"
                                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/faq-off.png') }}"
                                                            data-on-title = "{{translate('Want to Turn ON :item', ['item' => translate('FAQ')]).'?'}}"
                                                            data-off-title = "{{translate('Want to Turn OFF :item', ['item' => translate('FAQ')]).'?'}}"
                                                            data-on-message = "<p>{{translate('Shows this FAQ in the app and website.')}}</p>"
                                                            data-off-message = "<p>{{translate('Hides this FAQ in the app and website.')}}</p>"
                                                            data-on-button-text="{{ translate('Turn On') }}"
                                                            data-off-button-text="{{ translate('Turn Off') }}">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-3">
                                                    <a class="btn btn-outline-info icon-btn edit"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasFaqEdit{{$help['id']}}"
                                                    title="{{ translate('Edit')}}"
                                                    data-id="{{ route('admin.settings.help-topic.update', ['id'=>$help['id']]) }}">
                                                        <i class="fi fi-sr-pencil"></i>
                                                    </a>
                                                    <a class="btn btn-outline-danger icon-btn"
                                                    title="{{ translate('Delete')}}"
                                                    data-id="{{$help['id']}}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal{{$help['id']}}"
                                                    >
                                                        <i class="fi fi-rr-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive mt-4">
                            <div class="px-4 d-flex justify-content-lg-end">
                                {{ $helps->links() }}
                            </div>
                        </div>
                        @if(count($helps) == 0 && !request()->searchValue))
                            @include('layouts.admin.partials._empty-state-svg',['text'=>'no_help_topic_list'],['image'=>'reason'])
                        @endif
                        @if(count($helps) == 0 && request()->searchValue))
                            <div class="p-4 bg-chat rounded text-center">
                                <div class="py-5">
                                    <img src="{{ dynamicAsset('public/assets/back-end/img/empty-blog.png') }}" width="64"
                                         alt="">
                                    <div class="mx-auto my-3 max-w-353px">
                                        {{ translate('Currently no faq available in this state') }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @foreach($helps as $key=> $help)
        @include("admin-views.business-settings.vendor-registration-setting.partial._edit-faq-offcanvas", ['help' => $help])
    @endforeach

    @include("layouts.admin.partials.offcanvas._vendor-reg-faq")
@endsection
@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/business-setting/vendor-registration-setting.js')}}"></script>
@endpush
