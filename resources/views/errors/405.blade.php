@extends('errors::minimal')

@section('title', translate('Method Not Allowed'))

@section('message')
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-12">
                <div class="text-primary">
                    @include('errors.405-icon')
                </div>

                <h2 class="text-center pt-3">{{ translate('Method Not Allowed') }}</h2>

                <p class="text-center h4 lead py-2">
                    {{ translate('We are sorry') }}, {{ translate('The request method is not allowed for this url') }}
                    <br>
                    {{ translate('Please go back to the homepage') }}
                </p>
                <div class="text-center">
                    <a class="btn btn-primary font-weight-bold" href="{{ app('router')->has('home') ? route('home') : url('/') }}">
                        <span class="mr-2"><i class="fa fa-home" aria-hidden="true"></i></span>
                        {{ translate('Home') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
