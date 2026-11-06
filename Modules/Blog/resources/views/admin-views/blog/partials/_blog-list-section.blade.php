<div class="table-responsive datatable-custom">
    <table class="table table-hover table-borderless align-middle blog-list-table">
        <thead class="text-capitalize">
        <tr>
            <th>{{ translate('ID') }}</th>
            <th>{{ translate('Title') }}</th>
            <th>{{ translate('Category') }}</th>
            <th>{{ translate('Writer') }}</th>
            <th>{{ translate('Publish Date') }}</th>
            <th>
                <div class="d-flex align-items-center gap-1">
                    <span>{{ translate('Stage') }}</span>
                    <span class="trx-y-2" data-bs-toggle="tooltip" data-bs-placement="top"
                          data-bs-title="{{ translate('A draft is not on the website yet. Publish it to make it visible.') }}">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                    </span>
                </div>
            </th>
            <th>
                <div class="d-flex justify-content-center gap-1">
                    <span>{{ translate('Visible') }}</span>
                    <span class="trx-y-2" data-bs-toggle="tooltip" data-bs-placement="top"
                          data-bs-title="{{ translate('Turn this off to hide a published post without deleting it.') }}">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                    </span>
                </div>
            </th>
            <th class="text-center">{{ translate('Action') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($blogs as $blog)
            <tr>
                <td>#{{ $blog?->readable_id }}</td>
                <td>
                    <div class="blog-title-cell" title="{{ $blog->title }}">{{ $blog->title }}</div>
                </td>
                <td>{{ $blog?->category?->name ?? translate('Uncategorised') }}</td>
                <td class="blog-writer-cell" title="{{ $blog?->writer }}">{{ $blog?->writer ?: translate('Not set') }}</td>
                <td class="text-nowrap">
                    {{ $blog->is_published ? date('d M, y', strtotime($blog->publish_date)) : translate('Not published') }}
                </td>
                <td>
                    @if($blog->is_published)
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold text-capitalize">{{ translate('Published') }}</span>
                    @else
                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold text-capitalize">{{ translate('Draft') }}</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex justify-content-center">
                        <form action="{{ route('admin.settings.content.blog.blog-status-update', $blog->id) }}" class="single-blog-status-form"
                              method="post" id="blog-list-status{{$blog->id}}-form"
                              data-id="blog-list-status-form">
                            @csrf
                            <input type="hidden" name="product_id" value="">
                            <label class="switcher mx-auto" for="single-blog-status-{{ $blog->id }}">
                                <input
                                    class="switcher_input custom-modal-plugin"
                                    type="checkbox" value="1" name="status"
                                    id="single-blog-status-{{ $blog->id }}"
                                    {{$blog['status'] == 1 ? 'checked' : ''}}
                                    {{ $blog->is_published ? '' : 'disabled' }}
                                    data-modal-type="input-change-form"
                                    data-modal-form="#blog-list-status{{$blog->id}}-form"
                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-on.png') }}"
                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-off.png') }}"
                                    data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Blog status')]) }}"
                                    data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Blog status')]) }}"
                                    data-on-message="<p>{{ translate('Turning this on shows the blog in the list.') }}</p>"
                                    data-off-message="<p>{{ translate('Turning this off hides the blog from the list.') }}</p>"
                                    data-on-button-text="{{ translate('Turn On') }}"
                                    data-off-button-text="{{ translate('Turn Off') }}">
                                @if($blog->is_published)
                                    <span class="switcher_control"></span>
                                @else
                                    <span class="switcher_control" data-bs-toggle="tooltip"
                                          data-bs-title="{{ translate('Available once the post is published.') }}"></span>
                                @endif
                            </label>
                        </form>
                    </div>
                </td>
                <td>
                    <div class="d-flex gap-3 align-items-center justify-content-center">
                        @if($blog->is_draft == 1)
                            <a class="btn btn-outline-success icon-btn"
                               data-bs-toggle="tooltip" data-bs-title="{{ translate('Continue the draft') }}"
                               href="{{ route('admin.settings.content.blog.draft-edit', $blog->id) }}">
                                <i class="fi fi-rr-memo"></i>
                            </a>
                        @endif
                        @if($blog->is_published)
                            <a class="btn btn-outline-primary icon-btn"
                               data-bs-toggle="tooltip" data-bs-title="{{ translate('Edit') }}"
                               href="{{ route('admin.settings.content.blog.edit', ['id' => $blog->id]) }}">
                                <i class="fi fi-rr-pencil"></i>
                            </a>
                        @else
                            <span class="btn btn-outline-primary icon-btn cursor-pointer disabled"
                                  data-bs-toggle="tooltip"
                                  data-bs-title="{{ translate('Available once the post is published.') }}">
                                <i class="fi fi-rr-pencil"></i>
                            </span>
                        @endif
                        <a class="btn btn-outline-danger icon-btn delete-data-without-form"
                           data-bs-toggle="tooltip" data-bs-title="{{ translate('Delete') }}"
                           data-id="{{ $blog->id }}" data-action="{{ route('admin.settings.content.blog.delete') }}">
                            <i class="fi fi-rr-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="table-responsive">
    <div class="px-4 d-flex justify-content-lg-end">
        {{ $blogs->links() }}
    </div>
</div>
