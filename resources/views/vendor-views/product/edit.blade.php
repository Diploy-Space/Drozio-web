@extends('layouts.vendor.app')

@section('title', translate(request('product-gallery')==1 ?'product_Add' :'product_Edit'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor-product.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2 flex-grow-1">
                {{ translate(request('product-gallery')==1 ?'product_Add' :'product_Edit') }}
            </h2>

            @if(getActiveAIProviderConfigCache())
            <div class="form-control border-0 badge-pill bg-white d-flex justify-content-center align-items-center px-12 py-2 shadow-sm w-auto text-nowrap" id="ai-remaining-count">
                <span class="d-flex gap-2">
                    <span class="fw-bold" id="count">{{$aiRemainingCount ?? 0}}</span> {{translate('Generates left')}}
                    <img width="18" height="18" class="" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right-small.svg') }}" alt="">
                </span>
            </div>
            @endif

        </div>

        <form class="product-form text-start form-advance-validation form-advance-file-validation" action="{{request('product-gallery')==1?route('vendor.retail.products.add') : route('vendor.retail.products.update',$product->id)}}" method="post"
              enctype="multipart/form-data" id="product_form">
            @csrf

            @include('vendor-views.product.update._basic-setup')
            @include("vendor-views.product.update._general-setup")
            @include("vendor-views.product.update._pricing-others")
            @include("vendor-views.product.update._product-variation-setup")
            <div class="row product-image-wrapper gx-2 gy-3 mt-0">
                @include("vendor-views.product.update._additional-images")
                @include("vendor-views.product.update._digital-product-file")
            </div>
            @include("vendor-views.product.update._product-video")
            @include("vendor-views.product.update._seo-section")

            @include('vendor-views.product.partials.ai-sidebar')

            @if(getActiveAIProviderConfigCache())
            <div class="floating-ai-button">
                <button type="button" class="btn btn-lg rounded-circle shadow-lg" data-toggle="modal" data-target="#aiAssistantModal"
                    data-action="main" title="AI Assistant">
                    <span class="ai-btn-animation">
                        <span class="gradientCirc"></span>
                    </span>
                    <span class="position-relative z-1 text-white d-flex flex-column gap-1 align-items-center">
                        <img width="16" height="17" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/hexa-ai.svg') }}" alt="">
                        <span class="fs-12 fw-semibold">{{ translate('Use AI') }}</span>
                    </span>
                </button>
                <div class="ai-tooltip">
                    <span>{{translate('AI Assistant')}}</span>
                </div>
            </div>
            @endif

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn--primary px-5 product-add-requirements-check">
                    @if($product->request_status == 2)
                        {{ translate('Resubmit') }}
                    @else
                        {{ translate(request('product-gallery') ? 'submit' : 'update') }}
                    @endif
                </button>
            </div>
            @if(request('product-gallery'))
                <input hidden name="existing_thumbnail" value="{{$product->thumbnail_full_url['key']}}">
                <input hidden name="existing_meta_image" value="{{$product?->seoInfo?->image_full_url['key'] ?? $product->meta_image_full_url['key']}}">
            @endif

        </form>
    </div>

    <span id="route-vendor-products-sku-combination" data-url="{{ route('vendor.retail.products.sku-combination') }}"></span>
    <span id="route-vendor-products-digital-variation-combination" data-url="{{ route('vendor.retail.products.digital-variation-combination') }}"></span>
    <span id="route-vendor-products-digital-variation-file-delete" data-url="{{ route('vendor.retail.products.digital-variation-file-delete') }}"></span>
    <span id="image-path-of-product-upload-icon" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"></span>
    <span id="image-path-of-product-upload-icon-two" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}"></span>
    <span id="message-enter-choice-values" data-text="{{ translate('Enter choice values') }}"></span>
    <span id="message-upload-image" data-text="{{ translate('Upload image') }}"></span>
    <span id="message-are-you-sure" data-text="{{ translate('Are you sure') }}"></span>
    <span id="message-yes-word" data-text="{{ translate('Yes') }}"></span>
    <span id="message-no-word" data-text="{{ translate('No') }}"></span>
    <span id="message-want-to-add-or-update-this-product" data-text="{{ translate('Want to update this product') }}"></span>
    <span id="message-please-only-input-png-or-jpg" data-text="{{ translate('Please only input a png or jpg type file') }}"></span>
    <span id="message-product-added-successfully" data-text="{{ translate(':item added successfully', ['item' => translate('Product')]) }}"></span>
    <span id="message-discount-will-not-larger-then-variant-price" data-text="{{ translate('The discount price cannot be larger than the variant price') }}"></span>
    <span id="system-currency-code" data-value="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>
    <span id="system-session-direction" data-value="{{ Session::get('direction') }}"></span>

    <span id="message-file-size-too-big" data-text="{{ translate('File size is too large') }}"></span>
    <span id="product-gallery-page" data-page="{{ request('product-gallery') ? 'product-gallery' : '' }}"></span>

@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/tags-input.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/spartan-multi-image-picker/spartan-multi-image-picker-min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor-init.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/product-add-update.js') }}"></script>

    <script>
        "use strict";
        $(document).on('click', '.remove-addition-image-for-product-gallery', function () {
            $(this).closest('.upload-file').remove();
        });
        $(function () {
            $('.product_variation_toggle').each(function () {
                toggleVisibility($(this));
            });

            $(document).on('change', '.product_variation_toggle', function () {
                toggleVisibility($(this));
            });

            function toggleVisibility($toggle) {
                const $wrapper = $toggle.closest('.variation_wrapper');
                const $content = $wrapper.find('.product_variation_content');

                if ($toggle.is(':checked')) {
                    $content.removeClass('d--none').stop(true, true).slideDown(200);
                } else {
                    $content.stop(true, true).slideUp(200, function () {
                        $content.addClass('d--none');
                    });
                }
            }
        });

        let colors = {{ count($product->colors) }};
        let imageCount = {{15-count(json_decode($product->images)) }};
        let thumbnail = '{{productImagePath('thumbnail').'/'.$product->thumbnail ?? dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}';
        $(function () {
            if (imageCount > 0) {
                $("#coba").spartanMultiImagePicker({
                    fieldName: 'images[]',
                    maxCount: colors === 0 ? 15 : imageCount,
                    rowHeight: 'auto',
                    groupClassName: 'col-6 col-md-4 col-xl-3 col-xxl-2',
                    maxFileSize: '',
                    placeholderImage: {
                        image: '{{ dynamicAsset(path: "public/assets/back-end/img/400x400/img2.jpg") }}',
                        width: '100%',
                    },
                    dropFileLabel: "Drop Here",
                    onAddRow: function (index, file) {
                    },
                    onRenderedPreview: function (index) {
                    },
                    onRemoveRow: function (index) {
                    },
                    onExtensionErr: function () {
                        toastMagic.error(messagePleaseOnlyInputPNGOrJPG);
                    },
                    onSizeErr: function () {
                        toastMagic.error(messageFileSizeTooBig);
                    }
                });
            }

            $("#thumbnail").spartanMultiImagePicker({
                fieldName: 'image',
                maxCount: 1,
                rowHeight: 'auto',
                groupClassName: 'col-12',
                maxFileSize: '',
                placeholderImage: {
                    image: '{{ productImagePath('thumbnail').'/'. $product->thumbnail ?? dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}',
                    width: '100%',
                },
                dropFileLabel: "Drop Here",
                onAddRow: function (index, file) {

                },
                onRenderedPreview: function (index) {

                },
                onRemoveRow: function (index) {

                },
                onExtensionErr: function () {
                    toastMagic.error(messagePleaseOnlyInputPNGOrJPG);
                },
                onSizeErr: function () {
                    toastMagic.error(messageFileSizeTooBig);
                }
            });

        });

        setTimeout(function () {
            $('.call-update-sku').on('change', function () {
                getUpdateSKUFunctionality();
            });
        }, 2000)

        $('.remove-addition-image-for-product-gallery').on('click',function (){
            $('#'+$(this).data('section-remove-id')).remove();
        })


        $(document).ready(function () {
            setTimeout(function () {
                let category = $("#category_id").val();
                let sub_category = $("#sub-category-select").attr("data-id");
                let sub_sub_category = $("#sub-sub-category-select").attr("data-id");
                getRequestFunctionality('{{ route('vendor.retail.products.get-categories') }}?parent_id=' + category + '&sub_category=' + sub_category, 'sub-category-select', 'select');
                getRequestFunctionality('{{ route('vendor.retail.products.get-categories') }}?parent_id=' + sub_category + '&sub_category=' + sub_sub_category, 'sub-sub-category-select', 'select');
            }, 100)
        });

        updateProductQuantity();
    </script>

    <script src="{{ dynamicAsset(path: 'public/assets/backend/shared/js/products/product-image-delete.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/shared/js/products/product-images.js') }}"></script>
@endpush
