<div class="col-lg-4">
    <div class="">
        <div class="d-flex justify-content-center">
            <div class="ext-center">
                <h3 class="mb-2 text-capitalize">
                    {{ $vendorRegistrationHeader?->title ?? translate('Vendor Registration') }}
                </h3>
                <p>{{ $vendorRegistrationHeader?->sub_title ?? translate('Create your own store.').' '.translate('Already have store').'?' }}
                    <a class="text-primary fw-bold" href="{{ route('vendor.auth.login') }}">
                        {{ translate('Login') }}
                    </a>
                </p>
                <div class="my-4 text-center">
                    <img width="308" src="{{ !empty($vendorRegistrationHeader?->image)  ? getStorageImages(path:imagePathProcessing(imageData: $vendorRegistrationHeader?->image, path: 'vendor-registration-setting'),type: 'product') : theme_asset('public/assets/front-end/img/media/seller-registration.png')}}" alt="" class="dark-support">
                </div>
            </div>
        </div>
    </div>
</div>
