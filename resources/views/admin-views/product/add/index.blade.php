@extends('layouts.admin.app')

@section('title', translate('Add Product'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <h2 class="h1 mb-0">
                {{ translate('Add New Product') }}
            </h2>
             <a class="btn btn-primary text-capitalize"
                href="{{ route('admin.retail.products.product-gallery') }}">
                {{ translate('Add information from gallery') }}
            </a>
        </div>

        <form class="product-form text-start form-advance-validation form-advance-file-validation" action="{{ route('admin.retail.products.store') }}"  method="POST"
              enctype="multipart/form-data" id="product_form" data-ajax="true" novalidate="novalidate">
             @csrf
            @include('admin-views.product.add._basic-setup')
            @include("admin-views.product.add._general-setup")
            @include("admin-views.product.add._pricing-others")
            @include("admin-views.product.add._product-variation-setup")
            <div class="row product-image-wrapper g-3 mt-0">
                @include("admin-views.product.add._additional-images")
                @include("admin-views.product.add._digital-product-file")
            </div>
            @include("admin-views.product.add._product-video")
            @include("admin-views.product.add._seo-section")
            @include("admin-views.product.partials.ai-sidebar")

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" id="reset-btn" class="btn btn-secondary px-4 px-sm-5">
                        {{ translate('Reset') }}
                    </button>
                    <button type="button" class="btn btn-primary px-4 px-sm-5 product-add-requirements-check">
                        {{ translate('Submit') }}
                    </button>
                </div>
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
                <span>{{translate('AI Assistant')}}</span>
            </div>
        </div>
        @endif
    </div>

    <span id="product-add-update-messages"
          data-are-you-sure="{{ translate('Are you sure') }}"
          data-want-to-add="{{ translate('Want to add this product') }} ?"
          data-yes-word="{{ translate('Yes') }}"
          data-no-word="{{ translate('No') }}"
    ></span>
    <span id="message-product-added-successfully" data-text="{{ translate(':item added successfully', ['item' => translate('Product')]) }}"></span>
    <span id="message-enter-choice-values" data-text="{{ translate('Enter choice values') }}"></span>

    <span id="route-admin-products-sku-combination" data-url="{{ route('admin.retail.products.sku-combination') }}"></span>
    <span id="route-admin-products-digital-variation-combination" data-url="{{ route('admin.retail.products.digital-variation-combination') }}"></span>
    <span id="image-path-of-product-upload-icon" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"></span>
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

            $(document).on('input', '.product-title-default-language', function () {
                var $meta = $('#meta_title');
                if (!$meta.data('manual')) {
                    $meta.val($(this).val());
                }
            });
            $('#meta_title').on('input', function () {
                $(this).data('manual', true);
            });

            $(document).on('change', 'input[name="image"]', function () {
                var file = this.files[0];
                if (!file) return;
                var metaInput = document.querySelector('input[name="meta_image"]');
                if (!metaInput || metaInput.files.length > 0) return;
                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    metaInput.files = dt.files;
                    metaInput.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) {}
            });
        });
    </script>
@endpush
