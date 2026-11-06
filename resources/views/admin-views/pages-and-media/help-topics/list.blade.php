@extends('layouts.admin.app')

@section('title', translate('FAQ'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/new/back-end/img/Pages.png')}}" width="20" alt="">
                {{translate('Pages')}}
            </h2>
        </div>
        @include('admin-views.pages-and-media._pages-and-media-inline-menu')

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column gap-20">
                    <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                        <h3 class="mb-0 d-flex align-items-center gap-2">
                            {{ translate('List of FAQ') }}
                            @if(count($helps) > 0)
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-45">{{ $helps->total() }}</span>
                            @endif
                        </h3>
                        <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 justify-content-end">
                            <form action="{{ route('admin.settings.help-topic.list') }}" method="get" class="flex-grow-1 max-w-300 min-w-100-mobile">
                                <div class="input-group">
                                    <input type="search" name="searchValue" class="form-control" placeholder="{{ translate('Search by question or answer') }}" value="{{ request('searchValue') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <button class="btn btn-primary text-nowrap" type="button"
                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasFaqAdd">
                                <i class="fi fi-sr-plus"></i>
                                {{ translate('Add FAQ') }}
                            </button>
                        </div>
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
                                    <tr id="data-{{$help->id}}">
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <div class="max-w-200 line-2 text-wrap">
                                                {{ $help['question'] }}
                                            </div>
                                        </td>
                                        <td

                                        >
                                            <div class="max-w-280 line-2 text-wrap" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $help['answer'] }}">
                                                {{ $help['answer'] }}
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $help['ranking'] }}</td>

                                        <td>
                                            <form action="{{ route('admin.settings.help-topic.status', ['id'=>$help['id']])}}"
                                                method="get" id="help-topic-status{{$help['id']}}-form"
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
                                                <a class="btn btn-outline-info icon-btn"
                                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasFaqEdit-{{ $help['id'] }}"
                                                    title="{{ translate('Edit')}}"
                                                    data-id=""
                                                    >
                                                        <i class="fi fi-sr-pencil"></i>
                                                </a>
                                                <a title="Delete" class="btn btn-outline-danger icon-btn"
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
                    @if(count($helps)==0)
                        @include('layouts.admin.partials._empty-state-svg',['text'=>'no_faqs_available_in_this_list'],['image'=>'faqs'])
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include("admin-views.pages-and-media.help-topics.partials._faq-add-offcanvas")

    @foreach($helps as $help)
        @include("admin-views.pages-and-media.help-topics.partials._faq-edit-offcanvas", ['help' => $help])
    @endforeach

    @include("layouts.admin.partials.offcanvas._faq-setup")
@endsection

@push('script')
@endpush
