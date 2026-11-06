<div class="table-responsive height-30vh">
    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
        <thead class="thead-light thead-50 text-capitalize">
        <tr>
            <th>{{ translate('SL') }}</th>
            <th>
                <div class="d-flex align-items-center gap-1">
                    <span>{{ translate('Category') }}</span>
                    <span class="trx-y-2" data-bs-toggle="tooltip" data-bs-placement="top"
                          data-bs-title="{{ translate('The badge counts the posts using this category.') }}">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                    </span>
                </div>
            </th>
            <th class="text-center">
                <div class="d-flex justify-content-center gap-1">
                    <span>{{ translate('Status') }}</span>
                    <span class="trx-y-2" data-bs-toggle="tooltip" data-bs-placement="top"
                          data-bs-title="{{ translate('Turn this off to keep the category without offering it on new posts.') }}">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                    </span>
                </div>
            </th>
            <th class="text-center">{{ translate('Action') }}</th>
        </tr>
        </thead>
        <tbody>
        @if($categories->count() > 0)
            @foreach($categories as $key => $category)
                <tr>
                    <td>{{ ($categories->currentPage() - 1) * $categories->perPage() + $key + 1 }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2 blog-category-name" title="{{ $category->name }}">
                            <span class="blog-category-name__text">{{ $category->name }}</span>
                            @if($category->blogs_count > 0)
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-45 flex-shrink-0">{{ $category->blogs_count }}</span>
                            @else
                                <span class="badge badge-soft-secondary fw-semibold flex-shrink-0">{{ translate('Not used') }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <form action="{{ route('admin.settings.content.blog.category.status-update') }}" class="blog-category-status-form"
                                  method="get" id="blog-list-status-{{ $category->id }}-form">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">

                                <label class="switcher mx-auto" for="blog-category-status-{{ $category->id }}">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="status"
                                        id="blog-category-status-{{ $category->id }}"
                                        {{ $category->status == 1 ? 'checked' : '' }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#blog-list-status-{{ $category->id }}-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-on.png') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-off.png') }}"
                                        data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Category status')]) }}"
                                        data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Category status')]) }}"
                                        data-on-message="<p>{{ translate('The category can be chosen when writing a post.') }}</p>"
                                        data-off-message="<p>{{ translate('The category stays, but it is no longer offered on new posts.') }}</p>"
                                        data-on-button-text="{{ translate('Turn On') }}"
                                        data-off-button-text="{{ translate('Turn Off') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </form>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex justify-content-center gap-2">
                            <a class="btn btn-outline-primary icon-btn edit-category-btn"
                               data-bs-toggle="tooltip" data-bs-title="{{ translate('Rename') }}"
                               data-route="{{ route('admin.settings.content.blog.category.info') }}"
                               data-id="{{ $category->id }}">
                                <i class="fi fi-rr-pencil"></i>
                            </a>
                            <a class="btn btn-outline-danger icon-btn delete-category"
                               data-bs-toggle="tooltip" data-bs-title="{{ translate('Delete') }}"
                               data-route="{{ route('admin.settings.content.blog.category.delete') }}"
                               data-id="{{ $category->id }}">
                                <i class="fi fi-rr-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="4">
                    <div class="text-center p-4">
                        <img class="mb-3" src="{{ dynamicAsset('public/assets/back-end/img/empty-blog.png') }}"
                             alt="" width="64">
                        <p class="text-muted mb-0">
                            {{ translate('There is currently no blog category available') }}
                        </p>
                    </div>
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

@if($categories->count() > 0)
    <div class="card-footer border-0">
        <div class="d-flex justify-content-end">
            {{ $categories->appends(request()->except('page'))->render() }}
        </div>
    </div>
@endif
