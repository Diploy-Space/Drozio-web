<div class="col-md-4 show-for-digital-product h-100">
    <div class="card card-body h-100">
        <div class="mb-20">
           <h2 class="mb-1">{{ translate('Product Preview File') }}</h2>
           <p class="fs-12 mb-0">
               {{ translate('Upload a short preview.') }}
               <span class="text-info">{{ translate('Pdf, Mp4, Mp3, size : Max 10 MB') }}</span>
           </p>
        </div>
        <div class="bg-section rounded-10 p-3 d-flex justify-content-center align-items-center">
            <div class="image-uploader overflow-visible" style="--size: 100px;"

                data-pdf-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-file.svg') }}"
                data-mp3-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-mp3.svg') }}"
                data-mp4-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-mp4.svg') }}"
                data-default-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-cloud.svg') }}"
            >

                @php
                    $file = $product->retailDetail?->preview_file;
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                    if ($ext === 'pdf') {
                        $icon = dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-file.svg');
                    } elseif ($ext === 'mp3') {
                        $icon = dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-mp3.svg');
                    } elseif ($ext === 'mp4') {
                        $icon = dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-mp4.svg');
                    } else {
                        $icon = dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-cloud.svg');
                    }
                @endphp

                <input type="file" name="preview_file" class="image-uploader__zip" id="input-file" accept=".pdf,.mp3,.mp4" data-max-size="{{ getFileUploadMaxSize(type: 'file')  }}">

                <div class="image-uploader__zip-preview gap-1 overflow-hidden">
                    <img src="{{ $icon }}"
                        class="mx-auto upload-preview-icon h-30" width="28" alt="">
                    <div class="image-uploader__title fs-10 fw-medium text-info overflow-wrap-anywhere line-2">
                        @if ($product->preview_file_full_url['path'])
                            <span class="text-body">
                                {{ $product->retailDetail?->preview_file }}
                            </span>
                        @elseif(request('product-gallery') && $product?->retailDetail?->preview_file)
                            {{ translate('Upload File') }}
                        @else
                            {{ translate('Upload File') }}
                        @endif

                        @if (request('product-gallery'))
                            <input type="hidden" name="existing_preview_file"
                                value="{{ $product?->retailDetail?->preview_file }}">
                            <input type="hidden" name="existing_preview_file_storage_type"
                                value="{{ $product?->retailDetail?->preview_file_storage_type }}">
                        @endif
                    </div>
                </div>

                @if ($product->preview_file_full_url['path'])
                    <span
                        class="btn btn-danger btn-circle p-0 collapse show zip-remove-btn zip-remove-btn__outside delete_preview_file_input"
                        style="--size: 21px;"
                        data-route="{{ route('admin.retail.products.delete-preview-file') }}">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <i class="fi fi-rr-cross-small d-flex"></i>
                        </div>
                    </span>
                @else
                    <span class="btn btn-danger btn-circle p-0 collapse zip-remove-btn zip-remove-btn__outside" style="--size: 21px;">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <i class="fi fi-rr-cross-small d-flex"></i>
                        </div>
                    </span>
                @endif
            </div>
        </div>

    </div>
</div>

<input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">@if (request('product-gallery'))
    <input type="hidden" id="clone-product-gallery" value="1">
@endif
