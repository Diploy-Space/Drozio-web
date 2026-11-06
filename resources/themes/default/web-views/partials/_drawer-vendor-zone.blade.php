<li class="sh-tree__section">
    <button class="sh-tree__head" type="button" data-sh-tree="#{{ $panelId }}" aria-expanded="false">
        {{ translate('Vendor zone') }}
        <i class="fi fi-rr-angle-down"></i>
    </button>
    <div class="sh-tree__panel" id="{{ $panelId }}">
        <a class="sh-tree__item" href="{{ route('vendor.auth.registration.index') }}">
            <i class="fi fi-rr-shop sh-tree__icon" aria-hidden="true"></i>
            <span>{{ translate('Become a vendor') }}</span>
        </a>
        <a class="sh-tree__item" href="{{ route('vendor.auth.login') }}">
            <i class="fi fi-rr-sign-in-alt sh-tree__icon" aria-hidden="true"></i>
            <span>{{ translate('Vendor Login') }}</span>
        </a>
    </div>
</li>
