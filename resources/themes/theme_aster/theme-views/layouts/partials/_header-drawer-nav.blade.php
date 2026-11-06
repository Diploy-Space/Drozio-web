<ul class="main-nav nav">
    <li>
        <a href="{{ route('categories') }}">{{ translate('Categories') }}</a>
        <ul class="sub_menu">
            @foreach($categories->take(10) as $category)
                <li>
                    <a href="javascript:">
                        <span class="get-view-by-onclick"
                              data-link="{{ route($categoryRouteName, ['slug' => $category['slug']]) }}">
                            {{ $category['name'] }}
                        </span>
                    </a>
                    @if($category->childes->count() > 0)
                        <ul class="sub_menu">
                            @foreach($category['childes'] as $subCategory)
                                <li>
                                    <a href="javascript:">
                                        <span class="get-view-by-onclick" data-link="{{ route('category-products', ['slug' => $subCategory['slug']]) }}">{{ $subCategory['name'] }}</span>
                                    </a>
                                    @if($subCategory->childes->count() > 0)
                                        <ul class="sub_menu">
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
                <a href="{{ $productsRoute }}" class="btn-link text-primary">
                    {{ translate('View All') }}
                </a>
            </li>
        </ul>
    </li>

    @if($web_config['brand_setting'])
        <li>
            @if(count($brands) > 0)
            <a href="javascript:">{{ translate('Brands') }}</a>
            <ul class="sub_menu">
                @foreach($brands->take(9) as $brand)
                    <li>
                        <a href="{{ route('brand-products', ['slug' => $brand['slug']]) }}">
                            {{ $brand->name }}
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('brands') }}" class="btn-link text-primary">
                        {{ translate('View All') }}
                    </a>
                </li>
            </ul>
            @else
                <a href="{{ route('brands') }}">{{ translate('Brands') }}</a>
            @endif
        </li>
    @endif

    @if($web_config['business_mode'] == 'multi')
        <li>
            <a href="javascript:">{{ translate('Stores') }}</a>
            <ul class="sub_menu">
                <li>
                    <a href="{{ route('vendor-shop', ['slug' => $headerInHouseShop?->slug]) }}">
                        {{ Str::limit($headerInHouseShop?->name, 14) }}
                        @include('theme-views.partials._verified-badge', ['isVerified' => isInHouseShopVerified(), 'size' => 14])
                    </a>
                </li>
                @foreach($web_config['shops'] as $shop)
                    <li>
                        <a href="{{ route('vendor-shop',['slug' => $shop['slug']]) }}">
                            {{ Str::limit($shop->name, 14) }}
                            @include('theme-views.partials._verified-badge', ['isVerified' => $shop->hasVerifiedShopBadge(), 'size' => 14])
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('vendors') }}" class="btn-link text-primary">{{ translate('View All') }}</a>
                </li>
            </ul>
        </li>
    @endif

    @if(($headerFeaturedDealCount ?? 0) > 0 || ($web_config['flash_deals'] && count($web_config['flash_deals_products']) > 0) || $web_config['discount_product'] > 0 || $web_config['clearance_sale_product_count'] > 0)
        <li>
            <a href="javascript:">{{ translate('Offers') }}</a>
            <ul class="sub_menu">
                @if(($headerFeaturedDealCount ?? 0) > 0)
                    <li>
                        <a href="{{ route('featured-deal-products') }}">{{ translate('Featured Deal') }}</a>
                    </li>
                @endif
                @if($web_config['flash_deals'] && count($web_config['flash_deals_products']) > 0)
                    <li>
                        <a href="{{ route('flash-deals',['id' => $web_config['flash_deals']['id'] ?? 0]) }}">{{ translate('Flash deal') }}</a>
                    </li>
                @endif
                @if ($web_config['discount_product'] > 0)
                    <li>
                        <a class="d-flex gap-2 align-items-center" href="{{ route('discounted-products') }}">
                            <span>{{ translate('Discounted Products') }}</span>
                            <span><i class="bi bi-patch-check-fill text-warning"></i></span>
                        </a>
                    </li>
                @endif
                @if($web_config['clearance_sale_product_count'] > 0)
                    <li>
                        <a class="gap-2 align-items-center" href="{{ route('clearance-sale-products') }}">
                            <span>{{ translate('Clearance Sale') }}</span>
                            <span><i class="bi bi-patch-check-fill text-warning"></i></span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    @if($web_config['digital_product_setting'] && count($web_config['publishing_houses_header']) > 0)
        @php($firstPublisherID = is_array($web_config['publishing_houses_header']) && isset($web_config['publishing_houses_header']['id']) ? $web_config['publishing_houses_header']['id'] : $web_config['publishing_houses_header']?->first()?->id)
        <li>
            <a class="d-flex gap-2 align-items-center text-capitalize"
               href="{{ count($web_config['publishing_houses_header']) == 1 ? route('products',['data_from' => 'publishing_house', 'publishing_house_id' => $firstPublisherID, 'product_type' => 'digital', 'page' => 1]) : route('products', ['data_from' => 'publishing_house', 'product_type' => 'digital', 'page' => 1]) }}">
                {{ translate('Publication House') }}
            </a>
        </li>
    @endif

    @if($vendorRegistrationVisible)
        <li class="header-mobile-block">
            <a href="{{ route('vendor.auth.registration.index') }}" class="d-flex text-capitalize">
                <div class="fz-16 text-capitalize">{{ translate('Become a vendor') }}</div>
            </a>
        </li>
    @endif
</ul>
