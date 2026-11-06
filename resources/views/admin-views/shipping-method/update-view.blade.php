@extends('layouts.admin.app')

@section('title', translate('Shipping Method'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{dynamicAsset(path: 'public/assets/back-end/img/business-setup.png')}}" alt="">
            {{translate('Shipping method update')}}
        </h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @php($updateRouteName = $updateRouteName ?? 'admin.settings.business-settings.shipping-method.update')
                    <form action="{{route($updateRouteName,[$method['id']])}}"
                          class="text-start"
                          method="post">
                        @csrf
                        <div class="form-group">
                            <div class="row ">
                                <div class="col-md-12">
                                    <label class="title-color" for="title">{{translate('Title')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" value="{{$method['title']}}" class="form-control" maxlength="100" placeholder="{{translate('Title')}}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row ">
                                <div class="col-md-12">
                                    <label class="title-color" for="duration">{{translate('Shipping duration')}} <span class="text-danger">*</span></label>
                                    <input type="text" name="duration" id="duration" value="{{$method['duration']}}"
                                           class="form-control" maxlength="20"
                                           placeholder="{{translate('Ex').': 4-6 Days'}}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row ">
                                <div class="col-md-12">
                                    <label class="title-color" for="cost">{{translate('Shipping Cost')}} <span class="text-danger">*</span></label>
                                    <input type="number" min="0" max="1000000" name="cost"
                                           value="{{usdToDefaultCurrency(amount: $method['cost'])}}"
                                           class="form-control"
                                           placeholder="{{translate('Ex').' '.':'.' '.'10'}}$">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                            <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                                <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                                    {{ translate('Reset') }}
                                </button>
                                <button type="submit" class="btn btn-primary px-3 px-sm-4">
                                    <i class="fi fi-sr-disk"></i>
                                    {{ translate('Update') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
