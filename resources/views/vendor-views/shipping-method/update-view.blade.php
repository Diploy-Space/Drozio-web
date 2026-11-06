@extends('layouts.vendor.app')

@section('title', translate('Edit Shipping'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/shipping_method.png')}}" alt="">
            {{translate('Shipping method update')}}
        </h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header text-capitalize">
                    <h5 class="mb-0">{{translate('Shipping method update')}}</h5>
                </div>
                <div class="card-body">
                    <form action="{{route($updateRouteName,[$shippingMethod['id']])}}" method="post"
                          class="text-start form-advance-validation form-advance-inline-validation non-ajax-form-validate" novalidate>
                        @csrf
                        <div class="form-group">
                            <div class="row ">
                                <div class="col-md-12">
                                    <label for="title" class="title-color">{{translate('Title')}}</label>
                                    <input type="text" name="title" id="title" value="{{$shippingMethod['title']}}" class="form-control" maxlength="100"
                                           data-required-msg="{{ translate('The title field is required') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row ">
                                <div class="col-md-12">
                                    <label for="duration" class="title-color">{{translate('Shipping Duration')}}</label>
                                    <input type="text" name="duration" id="duration" value="{{$shippingMethod['duration']}}"
                                           class="form-control" maxlength="20" placeholder="{{translate('Ex').': 4-6 Days'}}"
                                           data-required-msg="{{ translate('The shipping duration field is required') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row ">
                                <div class="col-md-12">
                                    <label for="cost" class="title-color">{{translate('Cost')}}</label>
                                    <input type="number" min="0" step="0.01" max="1000000" name="cost" id="cost" value="{{ usdToDefaultCurrency(amount: $shippingMethod['cost']) }}" class="form-control"
                                           placeholder="{{translate('Ex').':'.usdToDefaultCurrency("10")}}"
                                           data-required-msg="{{ translate('The cost field is required') }}" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn--primary mt-2">{{translate('Update')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
