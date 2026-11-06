@php($metaImagePath = $category?->seo?->image_full_url['path'] ?? null)
@php($showIcon = $category->position == 0 || ($category->position == 1 && theme_root_path() == 'theme_aster'))

<div class="offcanvas offcanvas-end" tabindex="-1" id="categoryDetailsOffcanvas-{{ $category['id'] }}"
     aria-labelledby="categoryDetailsOffcanvasLabel-{{ $category['id'] }}" style="--bs-offcanvas-width: 500px;">
    <div class="offcanvas-header bg-body">
        <h3 class="mb-0" id="categoryDetailsOffcanvasLabel-{{ $category['id'] }}">
            @if ($category->position == 1)
                {{ translate('Sub Category') }}
            @elseif ($category->position == 2)
                {{ translate('Sub Sub Category') }}
            @else
                {{ translate('Category') }}
            @endif
            #{{ $category['id'] }}
        </h3>
        <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
            <i class="fi fi-rr-cross d-flex"></i>
        </button>
    </div>

    <div class="offcanvas-body d-flex flex-column gap-20">
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex align-items-center gap-3 mb-20">
                @if ($showIcon)
                    <div class="avatar-60 d-flex align-items-center rounded overflow-hidden">
                        <img class="w-100 h-100 object-fit-cover" alt=""
                             src="{{ getStorageImages(path: $category->icon_full_url, type: 'backend-category') }}">
                    </div>
                @endif
                <h3 class="mb-0 overflow-wrap-anywhere">{{ $category['defaultname'] }}</h3>
            </div>

            <table class="w-100">
                <tbody>
                @if ($category->position == 1)
                    <tr>
                        <td class="py-1 text-nowrap min-w-180">{{ translate('Main Category') }}</td>
                        <td class="py-1 px-2">:</td>
                        <td class="py-1 text-dark fw-medium overflow-wrap-anywhere">{{ $category?->parent?->defaultname ?? translate('Not found') }}</td>
                    </tr>
                @endif
                @if ($category->position == 2)
                    <tr>
                        <td class="py-1 text-nowrap min-w-180">{{ translate('Main Category') }}</td>
                        <td class="py-1 px-2">:</td>
                        <td class="py-1 text-dark fw-medium overflow-wrap-anywhere">{{ $category?->parent?->parent?->defaultname ?? translate('Not found') }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-nowrap min-w-180">{{ translate('Sub Category Name') }}</td>
                        <td class="py-1 px-2">:</td>
                        <td class="py-1 text-dark fw-medium overflow-wrap-anywhere">{{ $category?->parent?->defaultname ?? translate('Not found') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="py-1 text-nowrap min-w-180">{{ translate('Total Product') }}</td>
                    <td class="py-1 px-2">:</td>
                    <td class="py-1 text-dark fw-medium">{{ $category->product_count }}</td>
                </tr>
                @if ($category->position == 0)
                    <tr>
                        <td class="py-1 text-nowrap min-w-180">{{ translate('Total Sub Category') }}</td>
                        <td class="py-1 px-2">:</td>
                        <td class="py-1 text-dark fw-medium">{{ $category->sub_category_count }}</td>
                    </tr>
                @endif
                @if ($category->position != 2)
                    <tr>
                        <td class="py-1 text-nowrap min-w-180">{{ translate('Total Sub Sub Category') }}</td>
                        <td class="py-1 px-2">:</td>
                        <td class="py-1 text-dark fw-medium">{{ $category->sub_sub_category_count }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="py-1 text-nowrap min-w-180">{{ translate('Priority') }}</td>
                    <td class="py-1 px-2">:</td>
                    <td class="py-1 text-dark fw-medium">{{ $category['priority'] }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-nowrap min-w-180">{{ translate('Created Date') }}</td>
                    <td class="py-1 px-2">:</td>
                    <td class="py-1 text-dark fw-medium">{{ $category->created_at?->format('d M, Y | g:i A') }}</td>
                </tr>
                <tr>
                    <td class="py-1 text-nowrap min-w-180">{{ translate('Last Modified Date') }}</td>
                    <td class="py-1 px-2">:</td>
                    <td class="py-1 text-dark fw-medium">{{ $category->updated_at?->format('d M, Y | g:i A') }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <h3 class="mb-3">{{ translate('Meta Title & Description') }}</h3>
            @if ($category?->seo?->title)
                <h5 class="mb-2 overflow-wrap-anywhere">{{ $category->seo->title }}</h5>
            @endif
            @if ($category?->seo?->description)
                <p class="mb-0 overflow-wrap-anywhere">{{ $category->seo->description }}</p>
            @endif
            @if (! $category?->seo?->title && ! $category?->seo?->description)
                <p class="mb-0">{{ translate('N/A') }}</p>
            @endif

            @if ($metaImagePath)
                <h3 class="mb-3 mt-20">{{ translate('Meta images') }}</h3>
                <div class="ratio-2-1 max-w-300 rounded overflow-hidden">
                    <img class="w-100 h-100 object-fit-cover" alt=""
                         src="{{ getStorageImages(path: $category->seo->image_full_url, type: 'backend-banner') }}">
                </div>
            @endif
        </div>
    </div>

    <div class="offcanvas-footer shadow-popup">
        <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
            <button type="button" class="btn btn-danger flex-grow-1" data-bs-dismiss="offcanvas"
                    data-offcanvas-proxy-target="{{ $category['id'] }}">
                {{ translate('Delete') }}
            </button>
            <button type="button" class="btn btn-primary flex-grow-1" data-bs-dismiss="offcanvas"
                    data-offcanvas-proxy-target="category-edit-button-{{ $category['id'] }}">
                {{ translate('Edit Details') }}
            </button>
        </div>
    </div>
</div>
