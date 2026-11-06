@foreach($languageList as $key => $language)
<form action="{{ route('admin.settings.system-setup.language.update') }}" method="post">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="languageEditModal{{ $language['id'] }}" aria-labelledby="languageEditModal{{ $language['id'] }}Label">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Edit Language') }}</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-3 mb-sm-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group">
                        <label class="form-label" for="language-{{ $language['id'] }}">
                            {{ translate('Language') }}
                        </label>
                        <input type="text" name="name" class="form-control" value="{{ $language['name'] }}"
                               placeholder="{{ translate('Language name') }}" id="language-{{ $language['id'] }}">
                        <input name="type" value="update" hidden>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="">
                            {{ translate('Country code') }}
                        </label>
                        <input type="hidden" name="code" value="{{ $language['code'] }}">
                        <select class="custom-select image-var-select"
                                data-placeholder="Select from dropdown" disabled>
                            <option></option>
                            <?php
                                $languageCode = explode('-', $language['code'])[0];
                                $flagPath = 'public/assets/front-end/img/flags/'.$languageCode.'.png';
                                $countryName = collect(COUNTRIES)->firstWhere('code', strtoupper($languageCode))['name'] ?? $language['name'];
                            ?>
                            @if($languageCode != 'en' && File::exists(base_path($flagPath)))
                                <option value="{{ $languageCode }}" selected
                                        data-image-url="{{ dynamicAsset(path: $flagPath) }}">
                                    {{ strtoupper($language['code']) }} - {{ ucwords($countryName) }}
                                </option>
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="">
                            {{ translate('Direction') }}
                        </label>
                        <div
                            class="min-h-40 d-flex align-items-center gap-3 gap-lg-5 border rounded mb-2 px-3 py-1 bg-white">
                            <div class="form-check d-flex gap-2 align-items-center p-0">
                                <input class="form-check-input radio--input m-0" type="radio"
                                       name="direction" id="direction_left{{ $language['id'] }}"
                                       value="ltr" {{ !isset($language['direction']) || $language['direction'] == 'ltr' ? 'checked' : '' }}>
                                <label class="form-check-label" for="direction_left{{ $language['id'] }}">
                                    {{ translate('Left to Right') }}
                                </label>
                            </div>
                            <div class="form-check d-flex gap-2 align-items-center p-0">
                                <input class="form-check-input radio--input m-0" type="radio"
                                       name="direction" id="direction_right{{ $language['id'] }}"
                                       value="rtl" {{ !isset($language['direction']) || $language['direction'] == 'rtl' ? 'checked' : '' }}>
                                <label class="form-check-label" for="direction_right{{ $language['id'] }}">
                                    {{ translate('Right to Left') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 flex-grow-1">
                    {{ translate('Submit') }}
                </button>
            </div>
        </div>
    </div>
</form>
@endforeach
