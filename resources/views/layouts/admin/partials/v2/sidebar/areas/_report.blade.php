@php
    use App\Utils\Helpers;
    $v2ReportSections = reportMenuSections();
@endphp

@foreach ($v2ReportSections as $v2ReportSection)
    @if (Helpers::module_permission_check($v2ReportSection['permission']) && businessModuleSectionVisible($v2ReportSection['key']))
        <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === $v2ReportSection['key'] ? 'v2-is-on' : '' }}" data-section="{{ $v2ReportSection['key'] }}">
            <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate($v2ReportSection['title']) }}</div></div>
            <div class="v2-ctx-group v2-is-pinned" style="display:none;">
                <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
                <div class="v2-ctx-group-body"></div>
            </div>

            <div class="v2-ctx-group">
                @foreach ($v2ReportSection['items'] as $v2ReportItem)
                    @if ($v2ReportItem['children'])
                        <div class="v2-nav-item v2-has-children {{ Request::is(...$v2ReportItem['patterns']) ? 'v2-is-active' : '' }}" data-item="{{ $v2ReportItem['key'] }}">
                            <a class="v2-nav-btn" href="{{ $v2ReportItem['url'] }}">
                                <span class="v2-nav-label">{{ translate($v2ReportItem['label']) }}</span>
                            </a>
                            <div class="v2-nav-right">
                                <button class="v2-pin-btn" type="button" data-pin="{{ $v2ReportItem['key'] }}" aria-label="Pin"></button>
                                <span class="v2-nav-chev">
                                    <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                                </span>
                            </div>
                        </div>
                        <div class="v2-nav-children v2-is-collapsed" data-children-for="{{ $v2ReportItem['key'] }}">
                            @foreach ($v2ReportItem['children'] as $v2ReportChild)
                                <a class="v2-nav-child {{ Request::is(...$v2ReportChild['patterns']) ? 'v2-is-on' : '' }}" href="{{ $v2ReportChild['url'] }}">
                                    <span class="v2-nav-child-dot" data-tone="{{ $v2ReportChild['tone'] }}"></span>
                                    <span class="v2-nav-child-label">{{ translate($v2ReportChild['label']) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a class="v2-nav-item {{ Request::is(...$v2ReportItem['patterns']) ? 'v2-is-active' : '' }}" data-item="{{ $v2ReportItem['key'] }}" href="{{ $v2ReportItem['url'] }}">
                            <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2ReportItem['label']) }}</span></span>
                            <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="{{ $v2ReportItem['key'] }}" aria-label="Pin"></button></div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
@endforeach
