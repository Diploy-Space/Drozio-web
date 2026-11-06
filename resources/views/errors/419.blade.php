@extends('errors::minimal')

@section('title', translate('Page Expired'))

@section('message')
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-12">
                <div class="text-primary">
                    @include('errors.419-icon')
                </div>

                <h2 class="text-center pt-3">{{ translate('Page Expired') }}</h2>

                <p class="text-center h4 lead py-2">
                    {{ translate('We are sorry') }}, {{ translate('Your session has expired') }}
                    <br>
                    {{ translate('Please go back and try again') }}
                </p>
                <div class="d-flex flex-wrap justify-content-center">
                    <a class="btn btn-primary font-weight-bold m-1" href="{{ url()->previous() }}">
                        <span class="mr-2"><i class="fa fa-arrow-left" aria-hidden="true"></i></span>
                        {{ translate('Go Back') }}
                    </a>
                    <a class="btn btn-outline-primary font-weight-bold m-1" href="{{ app('router')->has('home') ? route('home') : url('/') }}">
                        <span class="mr-2"><i class="fa fa-home" aria-hidden="true"></i></span>
                        {{ translate('Home') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
