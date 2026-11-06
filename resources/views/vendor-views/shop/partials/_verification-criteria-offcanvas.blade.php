@if ($verification['show_prompt'])
    <div class="offcanvas-sidebar offcanvas-verification-criteria">
        <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

        <div class="offcanvas-content bg-white shadow d-flex flex-column">
            <div class="offcanvas-header bg-light d-flex justify-content-between align-items-center p-3">
                <h3 class="text-capitalize m-0">{{ translate('Verification Criteria') }}</h3>
                <button type="button" class="close" data-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="offcanvas-body p-3 overflow-auto flex-grow-1">
                <p class="fs-14 mb-3">
                    {{ translate('You can give providers a Verified badge when they successfully meet the requirements listed below.') }} {{ translate('This badge helps customers identify trusted and reliable providers on the platform.') }}
                </p>

                @if ($verification['is_qualified'])
                    <div class="alert alert-soft-success d-flex align-items-start gap-2 mb-3" role="alert">
                        <i class="fi fi-sr-check-circle text-success pt-1"></i>
                        <div>
                            <h5 class="mb-1 text-success">{{ translate('You are eligible for the verified badge') }}</h5>
                            <p class="fs-12 mb-0 text-dark">
                                {{ translate('Your shop meets every verification criteria. The admin will review your shop and grant the verified badge.') }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="bg-light p-3 rounded">
                    <h4 class="mb-3">
                        {{ $verification['is_qualified']
                            ? translate('You qualified the criteria')
                            : translate('Criteria to qualify') }}
                    </h4>

                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                        @foreach ($verification['criteria'] as $criterion)
                            <li class="d-flex align-items-start gap-2">
                                <span class="d-inline-flex lh-1 pt-1 {{ $criterion['met'] ? 'text-success' : 'text-muted' }}">
                                    <i class="fi {{ $criterion['met'] ? 'fi-sr-check-circle' : 'fi-rr-cross-circle' }}"></i>
                                </span>
                                <span class="{{ $criterion['met'] ? '' : 'text-muted' }}">
                                    {{ $criterion['leading'] }}<b class="{{ $criterion['met'] ? 'text-dark' : '' }}">{{ $criterion['highlight'] }}</b>{{ $criterion['trailing'] }}
                                    @if ($criterion['current'] !== null)
                                        <span class="d-block fs-12 text-muted">{{ translate('Current') }}: <b>{{ $criterion['current'] }}</b></span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
