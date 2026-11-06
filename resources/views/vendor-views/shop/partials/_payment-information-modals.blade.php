@foreach ($vendorWithdrawMethods as $key => $method)
    <form action="{{ route('vendor.shop.payment-information.default') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $method['id'] }}">
        <div class="modal fade" id="methodDefaultModal{{ $method['id'] }}" tabindex="-1"
             aria-labelledby="methodDefaultModal{{ $method['id'] }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button"
                                class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-20 py-0 mb-30">
                        <div class="d-flex flex-column align-items-center text-center mb-30">
                            <img
                                src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/general-icon.png') }}"
                                width="80" class="mb-20" id="" alt="">
                            <h2 class="modal-title mb-3">
                                {{ translate('Want to change default method') . ' ?' }}
                            </h2>
                            <div class="text-center">
                                {{ translate('Are you sure you want to change the default method') . ' ?' }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-secondary min-w-120"
                                    data-dismiss="modal">
                                {{ translate('No') }}
                            </button>
                            <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                    class="btn btn-danger min-w-120 {{ getDemoModeFormButton(type: 'class') }}">
                                {{ translate('Yes') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form action="{{ route('vendor.shop.payment-information.delete', [$method['id']]) }}"
          method="get">
        @csrf
        <div class="modal fade" id="methodDeleteModal{{ $method['id'] }}" tabindex="-1"
             aria-labelledby="methodDeleteModal{{ $method['id'] }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button"
                                class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-20 py-0 mb-30">
                        <div class="d-flex flex-column align-items-center text-center mb-30">
                            <img
                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png') }}"
                                width="80" class="mb-20" id="" alt="">
                            <h2 class="modal-title mb-3">
                                {{ translate('Want to delete this method') . ' ?' }}
                            </h2>
                            <div class="text-center">
                                {{ translate('Are you sure you want to delete this method') . ' ?' }}
                                {{ translate('It will be permanently deleted from your database') }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-secondary min-w-120"
                                    data-dismiss="modal">
                                {{ translate('No') }}
                            </button>
                            <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                    class="btn btn-danger min-w-120 {{ getDemoModeFormButton(type: 'class') }}"
                            >
                                {{ translate('Yes Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="edit-payment-offcanvas-{{ $method['id'] }} edit-payment-offcanvas offcanvas-sidebar">
        <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>
        <div class="offcanvas-content bg-white shadow d-flex flex-column">
            <div class="offcanvas-header bg-light d-flex justify-content-between align-items-center p-3">
                <h3 class="text-capitalize m-0">{{ translate('Edit payment info') }}</h3>
                <button type="button" class="close" data-dismiss="offcanvas" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form class="d-flex flex-column flex-grow-1 edit-payment-info-form" method="POST"
                action="{{ route('vendor.shop.payment-information.update') }}">
                @csrf
                <input type="hidden" name="id" value="{{ $method?->id }}">
                <div class="offcanvas-body p-3 overflow-auto flex-grow-1">
                    <div class="d-flex gap-2 alert alert-soft-warning mb-3" role="alert">
                        <i class="fi fi-sr-info"></i>
                        <p class="fs-12 mb-0 text-dark">
                            {{ translate('Shows this payment in the dropdown when a withdraw request is sent.') }}
                        </p>
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <div class="form-group">
                            <label class="form-label text-dark">
                                {{ translate('Method name') }}
                                <span class="text-danger">*</span>
                                <i class="fi fi-sr-info cursor-pointer text-muted" data-toggle="tooltip"
                                   title="{{ translate('Provide the method name of this payment method') }}"></i>
                            </label>
                            <input type="text" class="form-control" value="{{ $method?->method_name }}"
                                   placeholder="{{ translate('Method name') }}" name="method_name">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-dark">
                                {{ translate('Select Payment Method') }}
                                <span class="text-danger">*</span>
                            </label>
                            <select name="withdraw_method_id" class="form-control payment_method" required>
                                <option value="">{{ translate('Select Payment Method') }}</option>
                                @foreach ($withdrawalMethods as $withdrawalMethod)
                                    <option value="{{ $withdrawalMethod->id }}"
                                        {{ $method?->withdraw_method?->id == $withdrawalMethod->id ? 'selected' : '' }}>
                                        {{ $withdrawalMethod?->method_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="border rounded bg-white p-3 d-flex justify-content-between gap-3">
                                <h5 class="mb-0 d-flex gap-1 c1">
                                    {{ translate('Status') }}
                                </h5>
                                @if($method?->is_default)
                                    <label class="switcher opacity-50">
                                        <input type="checkbox" value="1" class="switcher_input" {{ $method?->is_active ? 'checked' : '' }} disabled>
                                        <span class="switcher_control"></span>
                                    </label>
                                    <input type="hidden" value="1" name="status">
                                @else
                                    <label class="switcher">
                                        <input type="checkbox" value="1" name="status" class="switcher_input"
                                            {{ $method?->is_active ? 'checked' : '' }}>
                                        <span class="switcher_control"></span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        @php($methodInfo = is_array($method->method_info) ? $method->method_info : json_decode($method->method_info, true))

                        <div class="dynamic_fields_wrapper-edit">
                            @foreach ($method?->withdraw_method?->method_fields as $methodFields)
                                <div class="form-group">
                                    <label class="form-label text-dark">
                                        {{ translate($methodFields['input_name']) }}
                                        <span class="text-danger">{{ $methodFields['is_required'] ? '*' : '' }}</span>
                                    </label>

                                    <input type="{{ $methodFields['input_type'] == 'phone' ? 'tel' : $methodFields['input_type'] }}"
                                           class="form-control"
                                           placeholder="{{ translate($methodFields['placeholder']) }}"
                                           value="{{ $methodInfo[$methodFields['input_name']] ?? "" }}"
                                           name="method_info[{{ $methodFields['input_name'] }}]"
                                        {{ $methodFields['is_required'] ? 'required' : '' }}>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="offcanvas-footer offcanvas-footer-sticky p-3 border-top bg-white d-flex gap-3">
                    <button type="button" data-dismiss="offcanvas" class="btn btn-secondary w-100">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn--primary w-100 edit-btn">
                        {{ translate('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach
