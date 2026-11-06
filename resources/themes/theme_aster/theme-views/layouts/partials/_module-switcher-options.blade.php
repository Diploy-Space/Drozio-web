<li>
    <div class="mb-3 text-center mx-auto max-w-350px">
        <h6 class="mb-2 fs-17 fw-bold">{{ translate('Choose Your Shopping Mode') }}</h6>
        <p class="mb-0 fs-14 opacity-75">{{ translate('Choose the experience that best matches your shopping purpose.') }}</p>
    </div>
</li>
<li>
    <div class="shopping-mode-menu__list rounded p-10px d-flex flex-column gap-1">
        @foreach(storefrontBusinessModules() as $shoppingMode)
            <a href="{{ $shoppingMode['url'] }}"
               class="shopping-mode-item d-flex align-items-center gap-12px rounded text-reset text-decoration-none {{ $shoppingMode['active'] ? 'active' : '' }}"
               @if($shoppingMode['active']) aria-current="true" @endif>
                <img width="20" height="20" class="svg" alt="{{ $shoppingMode['label'] }}"
                     src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/'.$shoppingMode['icon']) }}">
                <span>
                    <span class="d-block mb-1 fs-16 fw-semibold">{{ $shoppingMode['label'] }}</span>
                    <span class="d-block fs-14 opacity-75">{{ $shoppingMode['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</li>
<li class="mt-3">
    <p class="m-0 fs-14 text-center opacity-75 fst-italic">
        <span class="text-primary">#{{ translate('Note') }} :</span> {{ translate('You can switch between modules anytime from the top menu') }}
    </p>
</li>
