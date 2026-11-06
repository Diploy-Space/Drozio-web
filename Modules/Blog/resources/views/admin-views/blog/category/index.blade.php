<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCategory" aria-labelledby="offcanvasCategoryLabel" style="--bs-offcanvas-width: 600px;">
    <div class="offcanvas-header bg-section">
        <div>
            <h3 class="mb-1">{{ translate('Category Setup') }}</h3>
            <p class="mb-0 fs-12 text-muted">
                {{ translate('Categories group your posts and become the filters readers see on the blog page.') }}
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4">
            @include("blog::admin-views.blog.category.partials._create-category")
            @include("blog::admin-views.blog.category.partials._edit-category")
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row align-items-center justify-content-between g-3 mb-20">
                    <div class="col-md-4">
                        <h4 class="m-0 d-flex align-items-center gap-2">{{ translate('Category List') }}
                            @if(count($categories) > 0)
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-45">{{ $categories->total() }}</span>
                            @endif
                        </h4>
                    </div>
                    <div class="col-md-8">
                        <form action="javascript:" method="POST" id="search-form" class="mb-0 px-1">
                            @csrf
                            <div class="form-group">
                                <div class="input-group">
                                    <input  id="datatableSearch" type="search" class="form-control" name="searchValue" placeholder="{{ translate('Search by category name') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="" id="categories-table">
                    @include("blog::admin-views.blog.category.partials.table-rows")
                </div>
            </div>
        </div>
    </div>
  </div>
