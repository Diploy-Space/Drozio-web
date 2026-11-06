@php($vendorTabList = $seller['status'] === 'pending' ? [] : vendorDetailTabs($seller['id'], $vendorTabScope['module'] ?? \App\Enums\BusinessModule::COMMON, $vendorTabScope['tab'] ?? null))

@if (count($vendorTabList) > 1)
    <div class="page-header border-0 mb-4">
        <div class="position-relative nav--tab-wrapper">
            <ul class="nav nav-pills nav--tab">
                @foreach ($vendorTabList as $vendorTab)
                    <li class="nav-item">
                        <a class="text-capitalize nav-link {{ $vendorTab['is_active'] ? 'active' : '' }}" href="{{ $vendorTab['url'] }}">
                            {{ $vendorTab['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="nav--tab__prev">
                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                    <i class="fi fi-sr-angle-left"></i>
                </button>
            </div>
            <div class="nav--tab__next">
                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                    <i class="fi fi-sr-angle-right"></i>
                </button>
            </div>
        </div>
    </div>
@endif
