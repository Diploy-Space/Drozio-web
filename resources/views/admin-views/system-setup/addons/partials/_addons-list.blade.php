@php($placeholderThumbnail = dynamicAsset(path: 'public/assets/back-end/img/placeholder/placeholder-2-1.png'))
<div class="addon-card-grid" id="addon-card-grid">
    @foreach($addonInfos as $key => $item)
        @php($addon = $item['path'])
        @php($addonData = $item['info'])
        @php($addonName = ucwords(str_replace('_', ' ', $addonData['name'])))
        <div class="card border shadow-none h-100 overflow-hidden addon-card {{ $item['isActive'] ? 'theme-active' : '' }} {{ $item['isVersionSupported'] ? '' : 'addon-unsupported' }}">
            <div class="addon-thumb border-bottom">
                <img class="img-fit onerror-image" alt="{{ $addonName }}" loading="lazy"
                     src="{{ $item['thumbnailUrl'] ?? $placeholderThumbnail }}"
                     data-onerror="{{ $placeholderThumbnail }}">
            </div>
            <div class="card-body p-12 p-sm-3 d-flex flex-column gap-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <h3 class="fw-bold mb-0 fs-16 addon-card-title" title="{{ $addonName }}">
                        {{ $addonName }}
                    </h3>
                    <span class="addon-version fs-12 fw-semibold flex-shrink-0 {{ ! $item['isVersionSupported'] ? 'text-danger' : ($item['isUpdatePending'] ? 'text-warning' : 'text-body-secondary') }}">
                        v{{ $item['displayVersion'] }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 mt-auto addon-card-footer">
                    @if(! $item['isVersionSupported'])
                        <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold text-capitalize d-inline-flex align-items-center gap-1 cursor-pointer"
                              data-bs-toggle="tooltip" data-bs-placement="top"
                              data-bs-title="{{ translate('Version :version is not supported', ['version' => $item['displayVersion']]) }}. {{ translate('Supported versions') }}: {{ $item['supportedVersions'] }}">
                            {{ translate('Update required') }}
                            <i class="fi fi-sr-info"></i>
                        </span>
                    @elseif($item['isUpdatePending'])
                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold text-capitalize d-inline-flex align-items-center gap-1 cursor-pointer"
                              data-bs-toggle="tooltip" data-bs-placement="top"
                              data-bs-title="{{ translate('Version :version files are in place but not installed yet', ['version' => $item['displayVersion']]) }}. {{ translate('Finish the update to apply its database and asset changes') }}">
                            {{ translate('Update pending') }}
                            <i class="fi fi-sr-info"></i>
                        </span>
                    @elseif($item['isActive'])
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold text-capitalize">{{ translate('Active') }}</span>
                    @else
                        <span class="badge badge-soft-secondary fw-semibold text-capitalize">{{ translate('Inactive') }}</span>
                    @endif

                    @if(config('app.mode') != 'demo')
                        <div class="d-flex gap-2 align-items-center flex-shrink-0">
                            @if (! $item['isActive'])
                                <button class="btn btn-outline-danger bg-danger bg-opacity-10 icon-btn"
                                        data-bs-toggle="modal" data-bs-target="#deleteThemeModal_{{ $key }}">
                                    <i class="fi fi-sr-trash"></i>
                                </button>
                            @endif

                            @if($item['isVersionSupported'] && $item['isUpdatePending'])
                                <form action="{{ route('admin.settings.system-setup.addon.complete-installation') }}" method="post" class="addon-complete-installation-form">
                                    @csrf
                                    <input type="hidden" name="path" value="{{ $addon }}">
                                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">{{ translate('Finish update') }}</button>
                                </form>
                            @elseif($item['isVersionSupported'])
                                <label class="switcher mb-0" for="addon-publish-{{ $key }}">
                                    <input type="checkbox" id="addon-publish-{{ $key }}"
                                           class="switcher_input addon-publish-status addon-publish-status-{{ $key }}"
                                           {{ $addonData['is_published'] == 1 ? 'checked' : '' }}
                                           data-bs-toggle="modal" data-bs-target="#shiftThemeModal_{{ $key }}">
                                    <span class="switcher_control"></span>
                                </label>
                            @endif
                        </div>
                    @else
                        <div class="d-flex gap-2 align-items-center flex-shrink-0">
                            <label class="switcher mb-0 {{ getDemoModeFormButton(type: 'class') }}">
                                <input type="checkbox" disabled class="switcher_input" checked>
                                <span class="switcher_control"></span>
                            </label>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@foreach($addonInfos as $key => $item)
    @php($addon = $item['path'])
    @php($addonData = $item['info'])

    @if (! $item['isActive'])
        <div class="modal fade" id="deleteThemeModal_{{ $key }}" tabindex="-1"
             aria-labelledby="deleteThemeModal_{{ $key }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 text-center">
                        <form action="{{ route('admin.settings.system-setup.addon.delete') }}" method="post" class="addon-delete-form">
                            @csrf
                            <input type="hidden" name="path" value="{{ $addon }}">
                            <div class="mb-3 text-center">
                                <img width="75" src="{{ dynamicAsset(path: 'public/assets/back-end/img/delete.png') }}" alt="">
                            </div>
                            <h3>{{ translate('Are you sure you want to delete the').' '.$addonData['name'] }} ?</h3>
                            <p class="mb-5">
                                {{ translate('Once you delete') .','. translate('You will lost the this') .' '. $addonData['name'] }}
                            </p>
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <button type="button" class="fs-16 btn btn-secondary px-sm-5" data-bs-dismiss="modal">
                                    {{ translate('Cancel') }}
                                </button>
                                <button type="submit" class="fs-16 btn btn-danger px-sm-5">
                                    {{ translate('Delete') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="shiftThemeModal_{{ $key }}" tabindex="-1"
         aria-labelledby="shiftThemeModalLabel_{{ $key }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 px-sm-5 text-center">
                    <form action="{{ route('admin.settings.system-setup.addon.publish') }}" method="post" class="addon-publish-form">
                        @csrf
                        <input type="hidden" value="{{ $addon }}" name="path">
                        <div class="mb-3 text-center">
                            <img width="75" src="{{dynamicAsset(path: 'public/assets/back-end/img/shift.png') }}" alt="">
                        </div>
                        <h3>{{ translate('Are you sure').' ?' }}</h3>
                        <p class="mb-5">
                            @if ($addonData['is_published'])
                                {{ translate('Want to inactive this') .' '. $addonData['name'] }}
                            @else
                                {{ translate('Want to activate') .' '. $addonData['name'] }}
                            @endif
                        </p>
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <button type="button" data-bs-dismiss="modal" class="fs-16 btn btn-secondary px-sm-5">
                                {{ translate('No') }}
                            </button>
                            <button type="submit" data-bs-dismiss="modal" class="fs-16 btn btn-primary px-sm-5">
                                {{ translate('Yes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
