<div class="app-v2" id="appV2">
    <script>
    (function () {
        try {
            if (JSON.parse(localStorage.getItem('6v_vendor_v2_collapsed')) === true) {
                document.getElementById('appV2').classList.add('v2-collapsed');
            }
        } catch (e) {}
    })();
    </script>
    @include('layouts.vendor.partials.v2._header')
    @include('layouts.vendor.partials.v2._rail')
    @include('layouts.vendor.partials.v2._side-bar')

    <script>
    (function () {
        var path = window.location.pathname;
        var sections = document.querySelectorAll('#v2-ctxpanel .v2-ctx-section');
        if (!sections.length) return;
        var match = null;
        for (var i = 0; i < sections.length; i++) {
            var sec = sections[i];
            var anchors = sec.querySelectorAll('a[href]');
            for (var j = 0; j < anchors.length; j++) {
                var href = anchors[j].getAttribute('href') || '';
                href = href.replace(/^https?:\/\/[^/]+/, '').split('?')[0].split('#')[0];
                if (!href || href === '/') continue;
                if (path === href || path.indexOf(href.replace(/\/$/, '') + '/') === 0) {
                    match = sec.getAttribute('data-section');
                    break;
                }
            }
            if (match) break;
        }
        if (!match) return;
        for (var k = 0; k < sections.length; k++) {
            sections[k].classList.toggle('v2-is-on', sections[k].getAttribute('data-section') === match);
        }
        var rails = document.querySelectorAll('.v2-rail .v2-rail-btn[data-section]');
        for (var m = 0; m < rails.length; m++) {
            rails[m].classList.toggle('v2-is-active', rails[m].getAttribute('data-section') === match);
        }
    })();
    </script>

    <main class="v2-main" id="content" role="main">
        @yield('content')
        @include('layouts.vendor.partials.image-modal')
    </main>
</div>

<div id="headerMain" class="d-none"></div>
<div id="headerFluid" class="d-none"></div>
<div id="headerDouble" class="d-none"></div>
<div id="sidebarMain" class="d-none"></div>

@include('layouts.vendor.partials._modals')
@include('layouts.vendor.partials._toggle-modal')
@include('layouts.vendor.partials._sign-out-modal')
@include('layouts.vendor.partials._alert-message')
@include('layouts.vendor.partials.v2._setup-popover')
@if ($isAdvancedSearchAvailable)
    @include('layouts.back-end.partials.advanced-search._modal', ['searchUrl' => route('vendor.advanced-search')])
    @include('layouts.back-end.partials.advanced-search._script')
@endif

@php
    $v2InitialPins = [];
    try {
        $__v2Seller = auth('seller')->user();
        if ($__v2Seller) {
            $v2InitialPins = \App\Models\V2SidebarPin::pinsFor(\App\Models\V2SidebarPin::TYPE_SELLER, (int)$__v2Seller->id);
        }
    } catch (\Throwable $e) {}
@endphp
<script>
    window.__v2InitialPins = {!! json_encode($v2InitialPins) !!};
    window.__v2PinsToggleUrl  = "{{ route('vendor.v2.sidebar-pins.toggle') }}";
    window.__v2PinsReplaceUrl = "{{ route('vendor.v2.sidebar-pins.replace') }}";
    window.__v2PinsCsrf       = "{{ csrf_token() }}";
</script>
