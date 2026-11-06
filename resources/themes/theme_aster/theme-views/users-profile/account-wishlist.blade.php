@extends('theme-views.layouts.profile')

@section('title', translate('My Wishlists').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    @include('theme-views.partials._account-wishlist-content')
@endsection
