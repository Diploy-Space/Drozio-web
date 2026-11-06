@if (count($products) > 0)
    @foreach ($products as $key => $product)
        <div class="col-lg-12 select-clearance-product-item">
            <div class="mt-20">
                <div
                    class="media gap-3 p-3 radius-5 cursor-pointer justify-content-between align-items-center flex-wrap flex-xl-nowrap"
                    data-id="1">
                    <div class="d-flex align-items-center gap-3">
                        <img class="avatar avatar-xl border" width="75"
                             src="{{ getStorageImages(path:$product->thumbnail_full_url , type: 'backend-basic') }}"
                             class="rounded border-gray-op" alt="">
                        <div class="media-body d-flex flex-column gap-1">
                            <h6 class="product-id" hidden>{{$product['id']}}</h6>
                            <h6 class="title-color fz-13 mb-1 product-name line--limit-1 text-start">
                                {{$product['name']}}
                            </h6>
                            <div class="fs-12 title-color">
                                <div class="border-between wrap">
                                    <span class="parent">
                                        <span class="opacity--70">
                                            {{ translate('Price') }}:
                                        </span>
                                        <strong>
                                            {{ webCurrencyConverter(amount:$product->unit_price-(getProductDiscount(product: $product, price: $product->unit_price))) }}
                                        </strong>
                                    </span>
                                    @if($product->discount > 0)
                                        <del class="opacity--70">
                                            {{ webCurrencyConverter(amount: $product->unit_price) }}
                                        </del>
                                    @endif
                                    @if($product?->product_type !== 'digital')
                                        <span class="parent">
                                            <span class="opacity--70">{{ translate('Stock') }}:</span>
                                            {{ $product->current_stock }}
                                        </span>
                                    @endif
                                </div>
                                <div class="border-between wrap">
                                    <span class="parent">
                                        <span class="opacity--70">{{ translate('Category') }}:</span>
                                        {{isset($product->category) ? $product->category->name : translate('Not found') }}
                                    </span>

                                    @if($product?->product_type !== 'digital')
                                        <span class="parent">
                                            <span class="opacity--70"> {{ translate('Brand') }}:</span>
                                            {{isset($product->brand) ? $product?->brand?->name : translate('Not found') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="text-center p-4">
        <img class="mb-3 w-60px" src="{{dynamicAsset(path: 'public/assets/back-end/img/empty-state-icon/default.png')}}"
             alt="{{translate('Image description')}}">
        <p class="mb-0">{{ translate('No Product Found')}}</p>
    </div>
@endif

