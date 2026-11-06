<div class="dropdown header-desktop-block">
    <button class="select-category-button dropdown-toggle" type="button" data-bs-toggle="dropdown"
            data-bs-display="static" aria-expanded="false">
        <i class="bi bi-grid-fill select-category-button__icon" aria-hidden="true"></i>
        <span>{{ translate('Categories') }}</span>
    </button>
    <ul class="dropdown-menu dropdown--menu">
        @foreach($categories->take(11) as $category)
            <li class="{{ !$isAuctionSurface && $category->childes->count() > 0 ? 'menu-item-has-children' : '' }}">
                <a href="{{ route($categoryRouteName, ['slug' => $category['slug']]) }}">
                    {{ $category['name'] }}
                </a>
                @if(!$isAuctionSurface && $category->childes->count() > 0)
                    <ul class="sub-menu">
                        @foreach($category['childes'] as $subCategory)
                            <li class="{{ $subCategory->childes->count() > 0 ? 'menu-item-has-children' : '' }}">
                                <a href="{{ route('category-products', ['slug' => $subCategory['slug']]) }}">
                                    {{ $subCategory['name'] }}
                                </a>
                                @if($subCategory->childes->count() > 0)
                                    <ul class="sub-menu">
                                        @foreach($subCategory['childes'] as $subSubCategory)
                                            <li>
                                                <a href="{{ route('category-products', ['slug' => $subSubCategory['slug']]) }}">
                                                    {{ $subSubCategory['name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
        <li>
            <a href="{{ $productsRoute }}" class="btn-link text-primary">{{ translate('View All') }}</a>
        </li>
    </ul>
</div>
