@extends('theme-views.layouts.app')

@section('header')
    @include(storefrontAccountHeaderView())
@endsection

@section('app-bar')
    @include(storefrontAccountAppBarView())
@endsection
