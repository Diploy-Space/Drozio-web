<div class="modal fade" id="InformationThemeModal" data-bs-backdrop="static"
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="InformationThemeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" id="informationModalContent">
        </div>
    </div>
</div>

@foreach($themes as $key => $theme)
    @if(isset($theme['software_id']))

        @if(theme_root_path() != $key)
        <div class="modal fade" id="shiftThemeModal_{{ $key }}" tabindex="-1"
             aria-labelledby="shiftThemeModalLabel_{{ $key }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div
                        class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 text-center">
                        <form action="{{ route('admin.settings.system-setup.theme.publish') }}" method="post"
                              class="theme-publish-form">
                            <input type="hidden" value="{{ $key }}" name="theme">
                            <div class="mb-3 text-center">
                                <img width="75" src="{{dynamicAsset(path: 'public/assets/back-end/img/shift.png') }}"
                                     alt="">
                            </div>

                            <h3>{{ translate('Do you want to switch to another theme') }}</h3>
                            <p class="mb-5">
                                {{ translate('If you switch to another theme').', '. translate('Everything will be rearranged') }}
                                <br class="d-none d-sm-inline">
                                {{ translate('According to theme') }}
                            </p>
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <button type="button" data-bs-dismiss="modal"
                                        class="fs-16 btn btn-secondary px-sm-5">
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
        @endif

        @if(($key != 'default' && $key != 'theme_aster') && theme_root_path() != $key)
            <div class="modal fade" id="deleteThemeModal_{{ $key }}" tabindex="-1" aria-hidden="true"
                 aria-labelledby="deleteThemeModal_{{ $key }}">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                            <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                    data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body px-4 px-sm-5 text-center">
                                <form action="{{ route('admin.settings.system-setup.theme.delete') }}" method="post"
                                  class="theme-delete-form">
                                @csrf
                                <input type="hidden" value="{{ $key }}" name="theme">
                                <div class="mb-3 text-center">
                                    <img width="75" src="{{ dynamicAsset(path: 'public/assets/back-end/img/delete.png') }}"
                                         alt="">
                                </div>
                                <h3>{{ translate('Are you sure you want to delete the theme').'?' }}</h3>
                                <p class="mb-5">
                                    {{ translate('Once you delete').', '.translate('You will lose this theme') }}
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
    @endif
@endforeach
