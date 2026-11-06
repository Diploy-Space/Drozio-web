<div class="d-flex justify-content-between align-items-start gap-3">
    <div>
        <h2 class="text-capitalize mb-1">{{ $sectionTitle }}</h2>
        @isset($sectionDescription)
            <p class="mb-0">{{ $sectionDescription }}</p>
        @endisset
    </div>
    @isset($sectionToggle)
        <label class="switcher flex-shrink-0" for="{{ $sectionToggle['id'] }}">
            <input class="switcher_input" type="checkbox" value="1" name="{{ $sectionToggle['name'] }}"
                   id="{{ $sectionToggle['id'] }}" {{ $sectionToggle['checked'] ? 'checked' : '' }}>
            <span class="switcher_control"></span>
        </label>
    @endisset
</div>
