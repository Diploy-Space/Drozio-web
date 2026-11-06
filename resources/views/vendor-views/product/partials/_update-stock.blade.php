<div class="pb-4">
    <a class="d-flex align-items-center gap-3" href="{{ route('vendor.retail.products.view', ['addedBy' => ($product['added_by']=='seller'?'vendor' : 'in-house'), 'id' => $product['id']]) }}">
        <div class="avatar rounded avatar-70 border">
            <img class="avatar-img" src="{{ getStorageImages(path: $product->thumbnail_full_url, type:'backend-product') }}" alt="">
        </div>
        <div>
            <div class="d-block">
                <span class="line--limit-2 h5 text-hover-primary mb-2">
               {{ $product['name'] }}
            </span>
            </div>
            <span class="d-block font-size-sm text-body">
                {{ translate('Price') }} : {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['unit_price']), currencyCode: getCurrencyCode()) }}
            </span>
        </div>
    </a>
</div>
<div class="card-body bg-soft-secondary rounded mb-4">
    <input name="product_id" value="{{$product['id']}}" class="d-none">
    <div id="quantity" class="mb-3">
        <label class="form-label text-dark">{{ translate('Main stock') }}</label>
        <input type="number" min="0" value={{ $product->current_stock }} step="1" placeholder="{{ translate('Main stock') }}" name="current_stock" class="form-control bg-white"@if (!empty($product->retailDetail?->variation) && count(json_decode($product->retailDetail?->variation ?? '[]', true)) > 0)  readonly @endif required>
    </div>
    @if($product->retailDetail?->variation && count(json_decode($product->retailDetail?->variation ?? '[]', true)) > 0)
        <div>
            <label class="form-label text-dark">{{ translate('Variations Stock') }}</label>
            <div class="bg-white p-2 rounded">
                <div class="sku_combination py-2" id="sku_combination">
                    @if($restockId)
                        @include('vendor-views.product.partials._edit-restock-combinations', ['combinations'=>json_decode($product->retailDetail?->variation ?? '[]', true)])
                        <input type="hidden" name="restock_id" id="" value="{{ $restockId }}">
                    @else
                        @include('vendor-views.product.partials._edit-sku-combinations', ['combinations'=>json_decode($product->retailDetail?->variation ?? '[]', true)])
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
