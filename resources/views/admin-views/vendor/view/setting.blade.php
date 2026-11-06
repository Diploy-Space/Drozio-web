@extends('layouts.admin.app')

@section('title', $seller?->shop?->name ?? translate('Not found'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.vendor.partials._detail-header')

        <form action="{{ route('admin.people.vendors.update-setting', ['id' => $seller['id']]) }}" method="post"
              id="vendor-setting-form">
            @csrf
            <div class="row g-3">
                <div class="col-lg-6">
                    @include('admin-views.vendor.partials._commission-card', [
                        'commission' => $seller['sales_commission_percentage'],
                    ])
                </div>

                <div class="col-lg-6">
                    @include('admin-views.vendor.partials._gst-card')
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column gap-20">
                            @include('admin-views.vendor.partials._setting-section-heading', [
                                'sectionTitle' => translate('Vendor POS'),
                                'sectionDescription' => translate('If enabled this vendor can access POS from the website and vendor app'),
                                'sectionToggle' => [
                                    'id' => 'seller-pos',
                                    'name' => 'seller_pos',
                                    'checked' => $seller['pos_status'] == 1,
                                ],
                            ])
                        </div>
                    </div>
                </div>

                @if ($builderPublished)
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column gap-20">
                                @include('admin-views.vendor.partials._setting-section-heading', [
                                    'sectionTitle' => translate('Builder storefront'),
                                    'sectionDescription' => translate('If enabled this vendor can open the website builder and config the custom website'),
                                    'sectionToggle' => [
                                        'id' => 'builder-status',
                                        'name' => 'builder_status',
                                        'checked' => (bool) $builderConfig?->website_visibility,
                                    ],
                                ])

                                @if (!empty($builderConfig?->domain))
                                    <div class="p-12 p-sm-20 bg-section rounded d-flex flex-wrap gap-2 align-items-center">
                                        <span class="fw-medium text-dark">{{ translate('Domain') }}</span>
                                        <a href="{{ (request()->secure() ? 'https://' : 'http://').$builderConfig->domain }}"
                                           class="text-decoration-underline fw-semibold" target="_blank">
                                            {{ $builderConfig->domain }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @include('admin-views.vendor.partials._setting-form-actions')
        </form>
    </div>
@endsection
