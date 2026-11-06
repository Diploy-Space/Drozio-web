<form action="{{ route('admin.settings.pages-and-media.social-media-update',  ['id'=>$socialMediaLink['id']]) }}" method="post" id="">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSocialMediaEdit-{{ $socialMediaLink['id'] }}" aria-labelledby="offcanvasSocialMediaEditLabel">
        <div class="offcanvas-header bg-body">
            <h2 class="mb-0">{{ translate('Edit social media link') }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-3 mb-sm-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            {{ translate('Select social media') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Select Social Media" data-bs-title="{{ translate('Select social media') }}">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                        </label>
                        <select class="custom-select" data-placeholder="Select social media" name="name" required>
                            <option></option>
                            <option value="instagram" {{ $socialMediaLink['name'] == 'instagram' ? 'selected' : '' }}>{{ translate('Instagram') }}</option>
                            <option value="facebook" {{ $socialMediaLink['name'] == 'facebook' ? 'selected' : '' }}>{{ translate('Facebook') }}</option>
                            <option value="twitter" {{ $socialMediaLink['name'] == 'twitter' ? 'selected' : '' }}>{{ translate('Twitter') }}</option>
                            <option value="linkedin" {{ $socialMediaLink['name'] == 'linkedin' ? 'selected' : '' }}>{{ translate('LinkedIn') }}</option>
                            <option value="pinterest" {{ $socialMediaLink['name'] == 'pinterest' ? 'selected' : '' }}>{{ translate('Pinterest') }}</option>
                            <option value="google-plus" {{ $socialMediaLink['name'] == 'google-plus' ? 'selected' : '' }}>{{ translate('Google plus') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="name" class="form-label">
                            {{ translate('Social Media Link') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter Social Media Link" data-bs-title="{{ translate('Enter Social Media Link') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="url" name="link" class="form-control" id="link" value="{{ $socialMediaLink['link'] }}"
                               placeholder="{{ translate('Enter Social Media Link') }}" required>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 flex-grow-1">
                    {{ translate('Update') }}
                </button>
            </div>
        </div>
    </div>
</form>

<form action="{{ route('admin.settings.pages-and-media.social-media-delete') }}" method="post">
    <input type="hidden" name="id" value="{{ $socialMediaLink['id'] }}">
    @csrf
    <div class="modal fade" id="deleteModal{{$socialMediaLink['id']}}" tabindex="-1" aria-labelledby="deleteModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png')}}" width="80" class="mb-20" id="" alt="">
                        <h2 class="modal-title mb-3" id="">{{ translate('Are you sure you want to delete this :item', ['item' => translate('Social Media')]) }}?</h2>
                        <div class="text-center" id=""> {{ translate('Once you delete this social media') }}, {{ translate('It will be permanently removed and won\'t be visible to customers') }} </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary max-w-120 flex-grow-1"
                                data-bs-dismiss="modal">{{ translate('No') }}</button>
                        <button type="submit" class="btn btn-danger max-w-120 flex-grow-1"
                                data-bs-dismiss="modal">{{ translate('Yes Delete') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
