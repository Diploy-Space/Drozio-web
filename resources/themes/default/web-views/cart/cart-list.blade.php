@extends('layouts.front-end.app')

@section('title', translate('My Shopping Cart'))

@push('css_or_js')
    <meta property="og:image" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="og:title" content="{{$web_config['company_name']}} "/>
    <meta property="og:url" content="{{config('app.url')}}">
    <meta property="og:description" content="{{ $web_config['meta_description'] }}">
    <meta property="twitter:card" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="twitter:title" content="{{$web_config['company_name']}}"/>
    <meta property="twitter:url" content="{{config('app.url')}}">
    <meta property="twitter:description" content="{{ $web_config['meta_description'] }}">
@endpush

@push('page_css')
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/shop-cart.css') }}">
@endpush

@section('content')
    <div class="container sc-page rtl text-align-direction" id="cart-summary"
         @if(session()->has(\App\Enums\SessionKey::CHECKOUT_BLOCKERS)) data-flag-blockers="true" @endif>
        @include(VIEW_FILE_NAMES['products_cart_details_partials'])
    </div>

    <span id="get-cart-select-cart-items" data-route="{{ route('cart.select-cart-items') }}"></span>
@endsection

@push('script')
    <script>
        $(document).ready(function () {
            cartQuantityInitialize();
        });
    </script>
@endpush
