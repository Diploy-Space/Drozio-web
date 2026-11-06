@php($filterApplied = request()->filled('category_id') || request()->filled('publish_date'))

<form action="{{ url()->current() }}" method="GET" class="bg-section rounded p-12 p-sm-20">
    <div class="row g-3 align-items-end">
        <div class="col-md-6 col-lg-4">
            <div class="form-group mb-0">
                <label for="category_id" class="form-label">
                    {{ translate('Select category') }}
                </label>
                <select class="custom-select" name="category_id" id="category_id"
                        data-url-prefix=""
                        data-element-id=""
                        data-element-type="select">
                    <option value="" selected disabled>
                        {{ translate('Select category') }}
                    </option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{request('category_id')==$category->id ? 'selected' :''}}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="form-group mb-0">
                <label for="publish_date" class="form-label">
                    {{ translate('Publish Date') }}
                </label>
                <div class="position-relative">
                    <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                    <input type="text" name="publish_date" id="publish_date"
                           class="js-daterangepicker-with-range form-control cursor-pointer"
                           value="{{request('publish_date')}}"
                           placeholder="{{ translate('Select Date') }}" autocomplete="off"
                           readonly>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="d-flex justify-content-md-end gap-2 gap-sm-3">
                @if($filterApplied)
                    <a href="{{ route('admin.settings.content.blog.view') }}" class="btn btn-secondary min-w-120">
                        {{ translate('Reset') }}
                    </a>
                @endif
                <button type="submit" class="btn btn-primary min-w-120">
                    <i class="fi fi-sr-filter"></i>
                    {{ translate('Filter') }}
                </button>
            </div>
        </div>
    </div>
</form>
