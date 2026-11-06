@extends('layouts.admin.app')

@section('title', translate('ERP Integration'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-start mb-3">
            <i class="fi fi-sr-lightbulb-on text-info mt-1"></i>
            <span>
                {{ translate('Connect your store with an external ERP system.') }}
                {{ translate('Generate a token, then paste the credentials into your ERP.') }}
                <a href="javascript:" class="fw-semibold text-decoration-underline" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSetupGuide">{{ translate('View step by step setup guide') }}</a>.
            </span>
        </div>

        <div class="card mb-3">
            <div class="card-header px-20 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="fi fi-sr-plug d-flex"></i>
                    </span>
                    <div>
                        <h2 class="text-capitalize mb-1">{{ translate('ERP Integration') }}</h2>
                        <p class="mb-0 fs-12">
                            {{ translate('Connect your store to an ERP system with a secure API token.') }}.
                        </p>
                    </div>
                </div>
                <a href="javascript:" class="erp-copy btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1"
                   data-id="#erp-base-url-global">
                    <i class="fi fi-rr-link-alt"></i>
                    {{ translate('Copy Base URL') }}
                </a>
                <span class="d-none" id="erp-base-url-global">{{ url('/') }}</span>
            </div>
            <div class="card-body">
                <h3 class="fs-16 mb-3">{{ translate('Generate API Token') }}</h3>
                <form action="{{ route('admin.settings.third-party.erp.store') }}" method="post">
                    @csrf
                    <div class="p-12 p-sm-20 bg-section rounded-8">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-5">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="erp-token-name">
                                        {{ translate('Token Name') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="erp-token-name" name="name"
                                           value="{{ old('name') }}"
                                           placeholder="{{ translate('Ex') }}: ERP Production" required>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="erp-webhook-url">
                                        {{ translate('Webhook Url') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="url" class="form-control" id="erp-webhook-url" name="webhook_url"
                                           value="{{ old('webhook_url') }}"
                                           placeholder="https://erp.example.com/api/v1/integration/webhook" required>
                                    <p class="fs-12 mb-0 mt-2">
                                        {{ translate('Get this URL from your ERP panel under settings, integration.') }}.
                                    </p>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label d-none d-lg-block">&nbsp;</label>
                                <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary w-100 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}">
                                    {{ translate('Generate Token') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if(session()->has('new_credentials'))
                    @php($newCredentials = session('new_credentials'))
                    <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded-8 p-12 p-sm-20 mt-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fi fi-sr-check-circle text-success fs-18"></i>
                            <h4 class="mb-0 text-success">{{ translate('Token created successfully') }}!</h4>
                        </div>
                        <p class="fs-12 mb-3">
                            {{ translate('Copy the API key and secret now. The secret shows once.') }}.
                        </p>

                        <div class="form-group">
                            <label class="form-label text-uppercase">{{ translate('API Key') }}</label>
                            <div class="form-control d-flex align-items-center justify-content-between gap-2 px-3 py-2 bg-white">
                                <span class="form-ellipsis d-flex" id="erp-api-key"
                                      data-value="{{ $newCredentials['api_key'] }}">{{ str_repeat('•', strlen($newCredentials['api_key'])) }}</span>
                                <div class="d-flex align-items-center gap-3">
                                    <a href="javascript:" class="erp-value-toggle d-flex align-items-center"
                                       data-target="#erp-api-key">
                                        <i class="fi fi-rr-eye-crossed"></i>
                                    </a>
                                    <a href="javascript:" class="erp-copy d-flex align-items-center gap-1 fs-12 fw-semibold"
                                       data-id="#erp-api-key">
                                        <i class="fi fi-rr-duplicate"></i>
                                        {{ translate('Copy') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label text-uppercase">{{ translate('API Secret') }}</label>
                            <div class="form-control d-flex align-items-center justify-content-between gap-2 px-3 py-2 bg-white">
                                <span class="form-ellipsis d-flex" id="erp-api-secret"
                                      data-value="{{ $newCredentials['api_secret'] }}">{{ str_repeat('•', strlen($newCredentials['api_secret'])) }}</span>
                                <div class="d-flex align-items-center gap-3">
                                    <a href="javascript:" class="erp-value-toggle d-flex align-items-center"
                                       data-target="#erp-api-secret">
                                        <i class="fi fi-rr-eye-crossed"></i>
                                    </a>
                                    <a href="javascript:" class="erp-copy d-flex align-items-center gap-1 fs-12 fw-semibold"
                                       data-id="#erp-api-secret">
                                        <i class="fi fi-rr-duplicate"></i>
                                        {{ translate('Copy') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label text-uppercase">{{ translate('Base URL') }}</label>
                            <div class="form-control d-flex align-items-center justify-content-between gap-2 px-3 py-2 bg-white">
                                <span class="form-ellipsis d-flex" id="erp-base-url">{{ url('/') }}</span>
                                <a href="javascript:" class="erp-copy d-flex align-items-center gap-1 fs-12 fw-semibold"
                                   data-id="#erp-base-url">
                                    <i class="fi fi-rr-duplicate"></i>
                                    {{ translate('Copy') }}
                                </a>
                            </div>
                        </div>
                        <p class="fs-12 mb-0">
                            {{ translate('Use this as the base URL when adding an ERP connection.') }}.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header px-20 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="fi fi-sr-shield-check d-flex"></i>
                    </span>
                    <h2 class="text-capitalize mb-0">{{ translate('Api Tokens') }}</h2>
                </div>
                <span class="badge badge-soft-primary">{{ $tokens->count() }} {{ translate('Total') }}</span>
            </div>
            <div class="card-body">
                @if($tokens->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle card-table w-100">
                            <thead class="thead-light thead-50 text-capitalize">
                                <tr>
                                    <th>{{ translate('Serial') }}</th>
                                    <th>{{ translate('Name') }}</th>
                                    <th>{{ translate('Webhook Url') }}</th>
                                    <th class="text-center">{{ translate('Status') }}</th>
                                    <th>{{ translate('Last Used') }}</th>
                                    <th>{{ translate('Created At') }}</th>
                                    <th class="text-center">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tokens as $key => $token)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td class="fw-semibold">{{ $token->name }}</td>
                                        <td>
                                            <span class="form-ellipsis d-inline-block max-w-200" title="{{ $token->webhook_url }}">{{ $token->webhook_url }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($token->is_active)
                                                <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                            @else
                                                <span class="badge badge-soft-danger">{{ translate('Revoked') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $token->last_used_at ? $token->last_used_at->format('M d, Y H:i') : translate('Never') }}</td>
                                        <td>{{ $token->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                @if($token->is_active)
                                                    <button type="button"
                                                            class="btn btn-outline-warning icon-btn custom-modal-plugin"
                                                            title="{{ translate('Revoke') }}"
                                                            data-modal-type="input-change-form"
                                                            data-modal-form="#erp-revoke-form-{{ $token->id }}"
                                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/config-status-change.png') }}"
                                                            data-off-title="{{ translate('Want to revoke this token').'?' }}"
                                                            data-off-message="<p>{{ translate('The ERP connection using this token will stop working immediately') }}</p>"
                                                            data-off-button-text="{{ translate('Revoke') }}">
                                                        <i class="fi fi-rr-ban"></i>
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        class="btn btn-outline-danger icon-btn custom-modal-plugin"
                                                        title="{{ translate('Delete') }}"
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#erp-delete-form-{{ $token->id }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png') }}"
                                                        data-off-title="{{ translate('Want to delete this token').'?' }}"
                                                        data-off-message="<p>{{ translate('This token will be permanently removed and cannot be recovered') }}</p>"
                                                        data-off-button-text="{{ translate('Delete') }}">
                                                    <i class="fi fi-rr-trash"></i>
                                                </button>
                                            </div>

                                            @if($token->is_active)
                                                <form action="{{ route('admin.settings.third-party.erp.revoke') }}" method="post"
                                                      id="erp-revoke-form-{{ $token->id }}" class="d-none">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $token->id }}">
                                                </form>
                                            @endif
                                            <form action="{{ route('admin.settings.third-party.erp.delete') }}" method="post"
                                                  id="erp-delete-form-{{ $token->id }}" class="d-none">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $token->id }}">
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fi fi-rr-key-skeleton-left-right fs-40 text-body-secondary d-inline-flex mb-2"></i>
                        <h4 class="mb-1">{{ translate('No tokens yet') }}</h4>
                        <p class="mb-0 fs-12 text-body-secondary">
                            {{ translate('Generate your first token above to connect your ERP') }}.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('admin-views.third-party.erp._erp-setup-guideline')
@endsection

@push('script')
    <script>
        "use strict";

        $(document).on('click', '.erp-value-toggle', function () {
            let $field = $($(this).data('target'));
            let $icon = $(this).find('i');
            let value = $field.data('value');
            if ($field.data('visible')) {
                $field.text('•'.repeat(value.length)).data('visible', false);
                $icon.removeClass('fi-rr-eye').addClass('fi-rr-eye-crossed');
            } else {
                $field.text(value).data('visible', true);
                $icon.removeClass('fi-rr-eye-crossed').addClass('fi-rr-eye');
            }
        });

        $(document).on('click', '.erp-copy', function () {
            let $field = $($(this).data('id'));
            let value = $field.data('value') || $field.text();
            let $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(value).select();
            document.execCommand('copy');
            $temp.remove();
            toastMagic.success('Copied to clipboard');
        });
    </script>
@endpush
