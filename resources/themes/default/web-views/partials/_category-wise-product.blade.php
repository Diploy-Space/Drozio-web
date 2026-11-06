@if (count($category['products']) > 0)
<section class="container rtl pb-0 px-max-sm-0">
    <div class="__shadow-2">
        <div class="__p-20px rounded bg-white overflow-hidden">
            <div class="d-flex __gap-6px flex-between align-items-baseline">
                <h2 class="category-product-view-title mb-0 text-truncate" title="{{ $category['name'] }}">
                    <span class="for-feature-title font-bold __text-20px text-uppercase">
                            {{$category['name']}}
                    </span>
                </h2>
                <div class="category-product-view-all flex-shrink-0">
                    <a class="text-capitalize view-all-btn-text"
                       href="{{ route('category-products', ['slug' => $category['slug']]) }}">
                        <span class="view-btn-text">{{ translate('View All') }}</span>
                        <i class="czi-view-list"></i>
                    </a>
                </div>
            </div>

            <div class="mt-2">
                <div class="carousel-wrap-2 d-none d-sm-block">
                    <div class="owl-carousel owl-theme category-wise-product-slider" data-slide-items="6">
                        @each('web-views.partials._category-single-product', $category['products']->take(8), 'product')
                    </div>
                </div>
                <div class="d-sm-none">
                    <div class="row g-2">
                        @foreach($category['products'] as $key=>$product)
                            @if($key < 4)
                                <div class="col-6">
                                    @include('web-views.partials._category-single-product', ['product'=>$product])
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
