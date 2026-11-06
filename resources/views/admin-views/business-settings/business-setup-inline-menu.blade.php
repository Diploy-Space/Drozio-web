{{-- Tabs for the business module on screen. Which tabs, where each links and which
     one is active are all resolved by businessSetupTabs() in app/Utils/business-module-helper.php. --}}
<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab p-0" id="pills-tab" role="tablist">
        @foreach (businessSetupTabs() as $tab)
            <li class="nav-item">
                <a class="nav-link {{ $tab['is_active'] ? 'active' : '' }}" href="{{ $tab['url'] }}">
                    {{ $tab['label'] }}
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
        <button class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>
</div>
