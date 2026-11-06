@extends('layouts.admin.app')

@section('title', translate($title))

@section('content')
    {{-- Placeholder for an admin screen whose menu, route and controller exist but
         whose implementation is still to come. Keeps the sidebar complete without
         shipping a page that errors. See docs/menu-refactor/sidebar-menu-plan.md §9. --}}
    <div class="px-3 px-lg-4 pt-2 pt-lg-3 pb-4">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize">{{ translate($title) }}</h2>
        </div>

        <div class="card">
            <div class="card-body text-center py-5">
                <h3 class="mb-2">{{ translate('This feature is under development') }}</h3>
                <p class="fs-14 text-muted mb-0">
                    {{ translate('The menu route and controller for this feature are ready') }}.
                    {{ translate('The screen itself will be built in a later release') }}.
                </p>
            </div>
        </div>
    </div>
@endsection
