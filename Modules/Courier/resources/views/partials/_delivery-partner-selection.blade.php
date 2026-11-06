@php($bs = (int) ($courierBs ?? 5))
@php($panelBg = $bs === 5 ? 'bg-section' : 'bg-light')
@php($boldClass = $bs === 5 ? 'fw-bold' : 'font-weight-bold')
@php($toneClasses = [
    'success' => $bs === 5 ? 'text-bg-success'   : 'badge-success',
    'danger'  => $bs === 5 ? 'text-bg-danger'    : 'badge-danger',
    'warning' => $bs === 5 ? 'text-bg-warning'   : 'badge-warning',
    'info'    => $bs === 5 ? 'text-bg-info'      : 'badge-info',
    'neutral' => $bs === 5 ? 'text-bg-secondary' : 'badge-secondary',
])

@php($courierManual = $courierOrder['manual_assignment'] ?? null)
@php($courierCanDispatch = (bool) ($courierOrder['can_dispatch'] ?? true))
@php($courierCurrentPartner = $courierShipment['delivery_partner'] ?? ($courierManual['partner'] ?? null))

@if (!empty($courierShipment) || !empty($courierManual))
    <style>
        .courier-summary-value {
            text-align: end;
        }
    </style>
@endif

@if (!empty($courierShipment))
    @php($toneClass = $toneClasses[$courierShipment['status_tone']] ?? $toneClasses['neutral'])

    <div id="courier-shipment-summary">
        <label class="form-label {{ $boldClass }} mb-2 d-block">{{ translate('3rd Party Delivery Partner') }}</label>

        <div class="{{ $panelBg }} rounded p-3 d-flex flex-column gap-2 fs-12">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="text-muted">{{ translate('Delivery Partner') }}</span>
                <strong class="text-capitalize courier-summary-value">{{ $courierShipment['delivery_partner'] }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="text-muted">{{ translate('Tracking Number') }}</span>
                <strong class="courier-summary-value text-break">{{ $courierShipment['tracking_number'] }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="text-muted">{{ translate('Shipment Status') }}</span>
                <span class="badge {{ $toneClass }} text-capitalize" id="courier-status-badge"
                      data-tone-map="{{ json_encode($toneClasses) }}">
                    {{ translate($courierShipment['shipment_status_label']) }}
                </span>
            </div>
            @if (!is_null($courierShipment['delivery_fee']))
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="text-muted">{{ translate('Delivery Fee') }}</span>
                    <strong>{{ $courierShipment['delivery_fee'] }}</strong>
                </div>
            @endif
            @if ($courierShipment['dispatched_at'])
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="text-muted">{{ translate('Dispatched On') }}</span>
                    <span>{{ $courierShipment['dispatched_at'] }}</span>
                </div>
            @endif
            @if (!empty($courierShipment['tracking_url']))
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="text-muted">{{ translate('Live Tracking') }}</span>
                    <a href="{{ $courierShipment['tracking_url'] }}" target="_blank" rel="noopener">{{ translate('Open Link') }}</a>
                </div>
            @endif
        </div>

        @if ($courierShipment['can_track'])
            <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-3 d-flex align-items-center justify-content-center gap-2"
                    id="courier_track_btn"
                    data-track-url="{{ route($courierRoutes['track'], ['consignmentId' => $courierShipment['consignment_id']]) }}">
                <i class="fi fi-rr-time-past"></i>
                <span id="courier_track_btn_label">{{ translate('View Tracking History') }}</span>
            </button>

            <div class="d-none mt-3" id="courier_track_panel">
                <div class="{{ $panelBg }} rounded p-3">
                    <ol class="list-unstyled mb-0 courier-timeline" id="courier_track_result"></ol>
                </div>
            </div>

            <style>
                .courier-timeline > li {
                    position: relative;
                    padding-inline-start: 1.25rem;
                    padding-bottom: 0.875rem;
                }

                .courier-timeline > li:last-child {
                    padding-bottom: 0;
                }

                .courier-timeline > li::before {
                    content: '';
                    position: absolute;
                    inset-inline-start: 0;
                    top: 0.35rem;
                    width: 0.5rem;
                    height: 0.5rem;
                    border-radius: 50%;
                    background: var(--bs-primary, #377dff);
                }

                .courier-timeline > li:not(:last-child)::after {
                    content: '';
                    position: absolute;
                    inset-inline-start: 0.22rem;
                    top: 0.85rem;
                    bottom: 0;
                    width: 1px;
                    background: rgba(0, 0, 0, .12);
                }

                .courier-timeline > li.is-latest::before {
                    box-shadow: 0 0 0 3px rgba(55, 125, 255, .2);
                }

                .courier-timeline > li.is-note {
                    padding-inline-start: 0;
                    padding-bottom: 0;
                }

                .courier-timeline > li.is-note::before,
                .courier-timeline > li.is-note::after {
                    content: none;
                }
            </style>

            <script>
                (function () {
                    const btn = document.getElementById('courier_track_btn');
                    const label = document.getElementById('courier_track_btn_label');
                    const panel = document.getElementById('courier_track_panel');
                    const list = document.getElementById('courier_track_result');
                    const badge = document.getElementById('courier-status-badge');
                    if (!btn || !panel || !list) return;

                    const toneMap = badge ? JSON.parse(badge.dataset.toneMap) : {};
                    const showText = @json(translate('View Tracking History'));
                    const hideText = @json(translate('Hide Tracking History'));
                    const emptyText = @json(translate('No tracking updates yet'));
                    const failText = @json(translate('Failed to load tracking'));
                    let loaded = false;

                    function note(text, isError) {
                        list.innerHTML = '';
                        const li = document.createElement('li');
                        li.className = 'is-note ' + (isError ? 'text-danger' : 'text-muted');
                        li.textContent = text;
                        list.appendChild(li);
                    }

                    function syncBadge(status) {
                        // The badge follows the shipment's own status, refreshed from the carrier's
                        // status lookup — never a tracking event, because carriers such as RedX ship
                        // events with no status field at all.
                        if (!badge || !status || status.status === 'unknown') return;
                        Object.values(toneMap).forEach(cls => badge.classList.remove(cls));
                        badge.classList.add(toneMap[status.tone] || toneMap.neutral);
                        badge.textContent = status.label || status.status;
                    }

                    function render(events) {
                        list.innerHTML = '';
                        events.forEach(function (event, index) {
                            const li = document.createElement('li');
                            if (index === 0) li.className = 'is-latest';

                            const head = document.createElement('div');
                            head.className = 'd-flex justify-content-between align-items-start gap-2';

                            const status = document.createElement('strong');
                            status.className = 'text-capitalize';
                            status.textContent = event.label || event.status;
                            head.appendChild(status);

                            if (event.time) {
                                const time = document.createElement('span');
                                time.className = 'text-muted fs-12 text-nowrap flex-shrink-0';
                                time.textContent = event.time;
                                head.appendChild(time);
                            }
                            li.appendChild(head);

                            if (event.description && event.description !== (event.label || event.status)) {
                                const desc = document.createElement('div');
                                desc.className = 'text-muted fs-12';
                                desc.textContent = event.description;
                                li.appendChild(desc);
                            }
                            list.appendChild(li);
                        });
                    }

                    function open() {
                        panel.classList.remove('d-none');
                        if (label) label.textContent = hideText;
                    }

                    btn.addEventListener('click', function () {
                        if (!panel.classList.contains('d-none')) {
                            panel.classList.add('d-none');
                            if (label) label.textContent = showText;
                            return;
                        }

                        if (loaded) { open(); return; }

                        btn.disabled = true;
                        open();
                        note(@json(translate('Loading')) + '…', false);

                        fetch(btn.dataset.trackUrl, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                            .then(r => r.json())
                            .then(function (data) {
                                btn.disabled = false;
                                syncBadge(data.status);
                                if (!data.ok) { note(data.message || failText, true); return; }
                                if (!data.events || !data.events.length) { note(emptyText, false); return; }
                                loaded = true;
                                render(data.events);
                            })
                            .catch(function () {
                                btn.disabled = false;
                                note(failText, true);
                            });
                    });
                })();
            </script>
        @endif

        @if ($courierCanRevise)
            <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-3 d-flex align-items-center justify-content-center gap-2"
                    id="courier-open-revise">
                <i class="fi fi-rr-edit"></i>
                {{ translate('Update Delivery Information') }}
            </button>
        @endif
    </div>

    @if ($courierCanSwitch)
        <label class="form-label {{ $boldClass }} mb-2 d-block mt-3">{{ translate('Switch Delivery Partner') }}</label>
        @include('courier::partials._delivery-partner-options')
        <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2"
                id="courier-open-offcanvas" data-courier-intent="switch">
            <i class="fi fi-rr-shipping-fast"></i> {{ translate('Switch Delivery Partner') }}
        </button>

        @include('courier::partials._delivery-partner-switch-modal')
    @endif
@else
    @if (!empty($courierManual))
        <div id="courier-manual-summary">
            <label class="form-label {{ $boldClass }} mb-2 d-block">{{ translate('3rd Party Delivery Partner') }}</label>

            <div class="{{ $panelBg }} rounded p-3 d-flex flex-column gap-2 fs-12">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="text-muted">{{ translate('Delivery Partner') }}</span>
                    <strong class="text-capitalize courier-summary-value">
                        {{ ($courierManual['partner'] ?? null) ?: translate('Not assign yet') }}
                    </strong>
                </div>
                @if (!empty($courierManual['tracking_id']))
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <span class="text-muted">{{ translate('Tracking Number') }}</span>
                        <strong class="courier-summary-value text-break">{{ $courierManual['tracking_id'] }}</strong>
                    </div>
                @endif
                <span class="text-muted">
                    {{ translate('Assigned manually, so shipment status and tracking are unavailable.') }}
                </span>
            </div>
        </div>
    @endif

    @if ($courierCanDispatch)
        @if (empty($courierProviders))
            <div class="alert alert-warning mb-0 fs-12 {{ empty($courierManual) ? '' : 'mt-3' }}">
                {{ translate('No delivery partner is enabled Enable one from Courier Configuration') }}
            </div>
        @else
            <label class="form-label {{ $boldClass }} mb-2 d-block {{ empty($courierManual) ? '' : 'mt-3' }}">{{ translate('Select Delivery Partner') }}</label>
            @include('courier::partials._delivery-partner-options')
            @if (!empty($courierOrder['has_delivery_man']))
                <div class="alert alert-warning fs-12">
                    {{ translate('Sending this order to a partner removes the assigned delivery man.') }}
                </div>
            @endif
            <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2"
                    id="courier-open-offcanvas" data-courier-intent="dispatch">
                <i class="fi fi-rr-shipping-fast"></i> {{ translate('Send to Courier') }}
            </button>
        @endif
    @endif
@endif

@if (filled($courierCurrentPartner))
    @include('courier::partials._self-delivery-switch-modal')
@endif
