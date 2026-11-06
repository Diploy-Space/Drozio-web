<div class="modal-header border-0 pb-0 d-flex justify-content-end">
    <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
            class="tio-clear"></i></button>
</div>
<div class="modal-body px-4 px-sm-5 text-center">
    <div class="mb-3 text-center">
        <img width="75" src="{{dynamicAsset(path: 'public/assets/back-end/img/shift.png')}}" alt="">
    </div>

    <h3>{{ $companyName }} {{ translate('Switched') }} {{ $data->title }}</h3>
    <p class="text-muted">{{ translate('At') }} {{ $data->created_at->diffforHumans() }}</p>
    <p class="p-3">
        {{ translate('Hello') }} {{ translate('Sir') }}/{{ translate('Mam') }}
        {{ translate('We have updated our website theme') }}! {{ translate('Please take a moment to review the changes in your shop.') }} {{ translate('Resize and re-upload any images that no longer fit your shop.') }}
    </p>

    <p class="mb-5 px-5"><span class="text-danger">{{ translate('Note') }} :</span>
        {{ translate('Your care for the visual integrity of our website is invaluable.') }}
    </p>

    <div class="d-flex flex-column gap-2 justify-content-center align-items-center" id="notify_all_the_sellers_area">
        <a class="fs-16 btn btn--primary px-sm-5 w-fit-content" target="_blank" href="{{ route('vendor-shop',['slug'=>$shop->slug]) }}">{{ translate('Visit Shop') }}</a>
    </div>
</div>
