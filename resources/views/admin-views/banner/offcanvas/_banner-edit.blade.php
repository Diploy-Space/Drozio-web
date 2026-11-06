{{-- form-advance-inputs-validation is deliberately absent: it makes every select2 field
     required, including the hidden resource panels. banner.js validates instead. --}}
<form action="{{ route('admin.retail.banner.update', [$banner['id']]) }}" method="post" enctype="multipart/form-data"
      class="banner-form form-advance-validation form-advance-file-validation non-ajax-form-validate"
      data-msg-banner-type="{{ translate('Please select a banner type') }}"
      data-msg-resource-type="{{ translate('Please select a resource type') }}"
      data-msg-url="{{ translate('Please enter a valid banner url') }}"
      data-msg-resource-product="{{ translate('Please select a product') }}"
      data-msg-resource-category="{{ translate('Please select a category') }}"
      data-msg-resource-shop="{{ translate('Please select a shop') }}"
      data-msg-resource-brand="{{ translate('Please select a brand') }}"
      novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="bannerEditOffcanvas-{{ $banner['id'] }}"
         aria-labelledby="bannerEditOffcanvasLabel" style="--bs-offcanvas-width: 500px;">

        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h2 class="mb-0 flex-grow-1">{{ translate('Edit banner') }}</h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    @include('admin-views.banner.partials._banner-form-fields', ['banner' => $banner])
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    @include('admin-views.banner.partials._banner-image-field', ['banner' => $banner])
                </div>
            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary flex-grow-1">{{ translate('Update') }}</button>
            </div>
        </div>
    </div>
</form>
