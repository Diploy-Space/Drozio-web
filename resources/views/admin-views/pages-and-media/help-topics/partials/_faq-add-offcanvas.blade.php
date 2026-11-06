<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasFaqAdd" aria-labelledby="offcanvasFaqAddLabel">
    <div class="offcanvas-header bg-body">
        <div>
            <h2 class="mb-1">{{ translate('Add FAQ') }}</h2>
            <p class="mb-0 fs-12 text-muted">{{ translate('Set each FAQ and its priority to show it to customers.') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form action="{{ route('admin.settings.help-topic.add-new') }}" method="post" id="addForm"
          class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate d-flex flex-column flex-grow-1"
          novalidate="novalidate">
        @csrf
        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded">
                <div class="form-group">
                    <label class="form-label" for="faq-add-question">{{ translate('Question') }}<span class="text-danger">*</span>
                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                              data-bs-title="{{ translate('Add the questions that are commonly asked by customers.') }}">
                            <i class="fi fi-sr-info"></i>
                        </span>
                    </label>
                    <input type="text" class="form-control" name="question" id="faq-add-question"
                           data-required-msg="{{ translate('Question field is required') }}"
                           placeholder="{{ translate('Type Question') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="faq-add-answer">{{ translate('Answer') }}<span class="text-danger">*</span>
                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                              data-bs-title="{{ translate('Answers to the questions customers commonly ask.') }}">
                            <i class="fi fi-sr-info"></i>
                        </span>
                    </label>
                    <textarea class="form-control" name="answer" id="faq-add-answer" rows="5"
                              data-required-msg="{{ translate('Answer field is required') }}"
                              placeholder="{{ translate('Type Answer') }}" required></textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="faq-add-ranking">{{ translate('Priority') }}
                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                              data-bs-title="{{ translate('Choose a number to set where this FAQ appears.') }}">
                            <i class="fi fi-sr-info"></i>
                        </span>
                    </label>
                    <div class="select-wrapper">
                        <select class="form-select bg-white" name="ranking" id="faq-add-ranking">
                            @foreach($rankings as $ranking)
                                <option value="{{ $ranking }}">{{ $ranking }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 flex-grow-1">
                    <i class="fi fi-sr-disk"></i>
                    {{ translate('Submit') }}
                </button>
            </div>
        </div>
    </form>
</div>
