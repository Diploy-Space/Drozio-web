<form action="{{ route('admin.settings.help-topic.update', ['id'=>$help['id']]) }}" method="post" id="update-form-submit">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasFaqEdit-{{ $help['id'] }}" aria-labelledby="offcanvasFaqEditLabel">
        <div class="offcanvas-header bg-body">
            <div>
                <h2 class="mb-1">{{ translate('Edit FAQ') }}</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-3 mb-sm-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group">
                        <label class="form-label" for="">{{ translate('Question') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Update Question" data-bs-title="Update Question">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                        </label>
                        <input type="text" class="form-control" name="question" id="question-filed" value="{{ $help['question'] }}"
                               placeholder="{{translate('Type Question')}}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="">{{ translate('Answer') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Update Answer" data-bs-title="Update Answer">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                        </label>
                        <textarea class="form-control" name="answer"  id="answer-field"
                                  rows="5" placeholder="{{translate('Type Answer')}}">{{ $help['answer'] }}</textarea>
                    </div>
                    <div class="form-group pb-3">
                        <label class="form-label" for="">{{ translate('Priority') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Update Priority" data-bs-title="Update Priority">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                        </label>
                        <div class="select-wrapper">
                            <select class="form-select bg-white" name="ranking" id="ranking-field">
                                @foreach($rankings as $ranking)
                                    <option value="{{ $ranking }}" {{ $help['ranking'] == $ranking ? 'selected' : '' }}>{{ $ranking }}</option>
                                @endforeach
                            </select>
                        </div>
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
<form action="{{ route('admin.settings.help-topic.delete') }}" method="post">
    <input type="hidden" name="id" value="{{ $help['id'] }}">
    @csrf
    <div class="modal fade" id="deleteModal{{$help['id']}}" tabindex="-1" aria-labelledby="deleteModal" aria-hidden="true">
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
                        <h2 class="modal-title mb-3" id="">{{ translate('Want to delete this faq') }}?</h2>
                        <div class="text-center" id="">
                            {{ translate('Are you sure you want to delete this :item', ['item' => translate('FAQ')]) }}? {{ translate('Once you delete the FAQ') }},
                            {{ translate('It will be permanently removed and won\'t be visible to customers') }}.
                        </div>
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
