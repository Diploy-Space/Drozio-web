@extends('layouts.admin.app')

@section('title', translate('Priority Setup').' - '.translate('Blog'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/blog.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/blog-logo.png') }}" alt="">
                {{ translate('Blog') }}
            </h2>
            <p class="mb-0 fs-12 text-muted">
                {{ translate('Choose the order visitors see your blog categories and posts in.') }}
            </p>
        </div>

        @include('blog::admin-views.blog.partials._blog-tab-menu')

        <div class="row g-3">
            @foreach($prioritySections as $section)
                <div class="col-xl-6">
                    <div class="card h-100 {{ $section['parent_class'] }}">
                        <div class="card-header py-3">
                            <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                                <i class="fi {{ $section['icon'] }} text-primary d-flex"></i>
                                {{ $section['title'] }}
                            </h3>
                            <p class="mb-0 fs-12 text-muted">{{ $section['description'] }}</p>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <form action="{{ route('admin.settings.content.blog.priority-setup.index') }}" method="post"
                                  class="d-flex flex-column flex-grow-1">
                                @csrf
                                <input type="hidden" name="type" value="{{ $section['type'] }}">

                                <div class="bg-section rounded p-12 p-sm-20 d-flex flex-column gap-3">
                                    <label class="priority-option d-flex gap-3 justify-content-between align-items-center mb-0 pb-3 border-bottom"
                                           for="{{ $section['parent_class'] }}-default-sorting">
                                        <span class="d-flex flex-column">
                                            <span class="fw-semibold text-dark">{{ translate('Use default sorting') }}</span>
                                            <span class="fs-12 text-muted">{{ $section['default_hint'] }}</span>
                                        </span>
                                        <span class="switcher flex-shrink-0">
                                            <input type="checkbox" id="{{ $section['parent_class'] }}-default-sorting"
                                                   class="switcher_input switcher-input-js" name="default_sorting_status" value="1"
                                                   data-parent-class="{{ $section['parent_class'] }}" data-from="default-sorting"
                                                   {{ $section['default_selected'] ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </span>
                                    </label>

                                    <div>
                                        <label class="priority-option d-flex gap-3 justify-content-between align-items-center mb-0"
                                               for="{{ $section['parent_class'] }}-custom-sorting">
                                            <span class="d-flex flex-column">
                                                <span class="fw-semibold text-dark">{{ translate('Use custom sorting') }}</span>
                                                <span class="fs-12 text-muted">{{ translate('Pick the order yourself from the options below.') }}</span>
                                            </span>
                                            <span class="switcher flex-shrink-0">
                                                <input type="checkbox" id="{{ $section['parent_class'] }}-custom-sorting"
                                                       class="switcher_input switcher-input-js" name="custom_sorting_status" value="1"
                                                       data-parent-class="{{ $section['parent_class'] }}" data-from="custom-sorting"
                                                       {{ $section['custom_selected'] ? 'checked' : '' }}>
                                                <span class="switcher_control"></span>
                                            </span>
                                        </label>

                                        <div class="custom-sorting-radio-list {{ $section['custom_selected'] ? '' : 'd--none' }}">
                                            <div class="bg-body border rounded p-12 p-sm-3 d-flex flex-column gap-3 mt-3">
                                                @foreach($section['options'] as $option)
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <input type="radio" class="show radio--input" name="sort_by"
                                                               value="{{ $option['value'] }}" id="{{ $option['id'] }}"
                                                               {{ $section['sort_by'] === $option['value'] ? 'checked' : '' }}>
                                                        <label class="mb-0 cursor-pointer" for="{{ $option['id'] }}">
                                                            {{ $option['label'] }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-auto pt-4">
                                    <button type="submit" class="btn btn-primary px-5">
                                        <i class="fi fi-sr-disk"></i>
                                        {{ $section['saved'] ? translate('Update') : translate('Save') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/deal.js') }}"></script>
@endpush
