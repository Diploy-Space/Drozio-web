@extends('layouts.admin.app')

@section('title', translate(request('product-gallery') == 1 ? 'product_Add' : 'product_Edit'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}"
                     alt="{{ translate('Product Edit') }}">
                {{ translate(request('product-gallery') == 1 ?'product_Add' : 'product_Edit') }}
            </h2>
        </div>

        <form class="product-form text-start form-advance-validation form-advance-file-validation" enctype="multipart/form-data" id="product_form" method="POST"
              action="{{ request('product-gallery') == 1 ? route('admin.retail.products.add') : route('admin.retail.products.update', $product->id) }}" novalidate="novalidate">
            @csrf
            @include('admin-views.product.update._basic-setup')
            @include("admin-views.product.update._general-setup")
            @include("admin-views.product.update._pricing-others")
            @include("admin-views.product.update._product-variation-setup")
            <div class="row product-image-wrapper g-3 mt-0">
                @include("admin-views.product.update._additional-images")
                @include("admin-views.product.update._digital-product-file")
            </div>
            @include("admin-views.product.update._product-video")
            @include("admin-views.product.update._seo-section")
            @include("admin-views.product.partials.ai-sidebar")

            <div class="d-flex justify-content-end flex-wrap gap-3 mt-3 mx-1">
                <button type="button" class="btn btn-primary px-5 product-add-requirements-check">
                    @if($product->request_status == 2)
                        {{ translate('Edit & Approved') }}
                        @elseif($product->request_status == 0)
                        {{ translate('Edit & Approved') }}
                    @else
                        {{ translate(request('product-gallery') ? 'submit' : 'update') }}
                    @endif
                </button>
            </div>
        </form>

        @if(getActiveAIProviderConfigCache())
        <div class="floating-ai-button">
            <button type="button" class="btn btn-lg rounded-circle shadow-lg" data-bs-toggle="modal" data-bs-target="#aiAssistantModal"
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
                <span>{{ translate('AI Assistant') }}</span>
            </div>
        </div>
        @endif
    </div>

    <span id="product-add-update-messages"
          data-are-you-sure="{{ translate('Are you sure') }}"
          data-want-to-add="{{ translate('Want to update this product') }} ?"
          data-yes-word="{{ translate('Yes') }}"
          data-no-word="{{ translate('No') }}"
    ></span>
    <span id="message-product-added-successfully" data-text="{{ translate(':item updated successfully', ['item' => translate('Product')]) }}"></span>
    <span id="message-enter-choice-values" data-text="{{ translate('Enter choice values') }}"></span>

    <span id="route-admin-products-sku-combination" data-url="{{ route('admin.retail.products.sku-combination') }}"></span>
    <span id="route-admin-products-digital-variation-combination" data-url="{{ route('admin.retail.products.digital-variation-combination') }}"></span>
    <span id="route-admin-products-digital-variation-file-delete" data-url="{{ route('admin.retail.products.digital-variation-file-delete') }}"></span>
    <span id="image-path-of-product-upload-icon" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"></span>
    <span id="product-gallery-page" data-page="{{ request('product-gallery') ? 'product-gallery' : '' }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor-init.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/products/product-add-update-utils.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/products/product-add-update.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/products/product-add-update-ajax.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/shared/js/products/product-image-delete.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/shared/js/products/product-images.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/product-title-autofill.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/product-title-autofill.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/product-description-autofill.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/general-setup.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/product-pricing.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/product-variation-setup.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/AI/products/seo-section-auto-fill.js') }}"></script>

    <script>
        $(document).ready(function () {
            updateProductQuantity();
            setTimeout(function () {
                let category = $("#category_id").val();
                let sub_category = $("#sub-category-select").attr("data-id");
                let sub_sub_category = $("#sub-sub-category-select").attr("data-id");
                getRequestFunctionality('{{ route('admin.retail.products.get-categories') }}?parent_id=' + category + '&sub_category=' + sub_category, 'sub-category-select', 'select');
                getRequestFunctionality('{{ route('admin.retail.products.get-categories') }}?parent_id=' + sub_category + '&sub_category=' + sub_sub_category, 'sub-sub-category-select', 'select');
            }, 100)
        });

    </script>

    <script>
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
    </script>
@endpush
