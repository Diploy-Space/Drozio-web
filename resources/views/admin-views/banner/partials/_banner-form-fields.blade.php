@php($banner = $banner ?? null)
@php($selectedBannerType = $banner ? $banner['banner_type'] : old('banner_type'))
@php($selectedResource = $banner ? $banner['resource_type'] : old('resource_type'))
@php($selectedResourceId = $banner ? $banner['resource_id'] : null)
@php($selectedProduct = $banner ? ($selectedResource === 'product' ? $selectedResourceId : null) : old('product_id'))
@php($selectedCategory = $banner ? ($selectedResource === 'category' ? $selectedResourceId : null) : old('category_id'))
@php($selectedShop = $banner ? ($selectedResource === 'shop' ? $selectedResourceId : null) : old('shop_id'))
@php($selectedBrand = $banner ? ($selectedResource === 'brand' ? $selectedResourceId : null) : old('brand_id'))
@php($selectedUrl = $banner ? ($selectedResource === 'custom' ? $banner['url'] : '') : old('url'))

<div class="form-group">
    <label class="form-label">{{ translate('Banner type') }} <span class="text-danger">*</span></label>
    <select class="custom-select banner-type-select" name="banner_type"
            data-placeholder="{{ translate('Select banner type') }}">
        <option value="">{{ translate('Select banner type') }}</option>
        @foreach($bannerTypes as $key => $bannerType)
            <option value="{{ $key }}" {{ $selectedBannerType === $key ? 'selected' : '' }}>{{ $bannerType }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label class="form-label">{{ translate('Resource type') }} <span class="text-danger">*</span></label>
    <select class="custom-select action-display-data" name="resource_type"
            data-placeholder="{{ translate('Select resource type') }}">
        <option value="">{{ translate('Select resource type') }}</option>
        @foreach($resourceTypes as $key => $resourceType)
            <option value="{{ $key }}" {{ $selectedResource === $key ? 'selected' : '' }}>{{ $resourceType }}</option>
        @endforeach
    </select>
</div>

<div class="form-group mb-0 resource-field {{ $selectedResource === 'product' ? '' : 'd--none' }}" data-resource="product">
    <label class="form-label">{{ translate('Product') }} <span class="text-danger">*</span></label>
    <select class="custom-select" name="product_id" data-placeholder="{{ translate('Select product') }}">
        <option value="">{{ translate('Select product') }}</option>
        @foreach($products as $product)
            <option value="{{ $product['id'] }}" {{ $selectedProduct == $product['id'] ? 'selected' : '' }}>
                {{ $product['name'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group mb-0 resource-field {{ $selectedResource === 'category' ? '' : 'd--none' }}" data-resource="category">
    <label class="form-label">{{ translate('Category') }} <span class="text-danger">*</span></label>
    <select class="custom-select" name="category_id" data-placeholder="{{ translate('Select category') }}">
        <option value="">{{ translate('Select category') }}</option>
        @foreach($categories as $category)
            <option value="{{ $category['id'] }}" {{ $selectedCategory == $category['id'] ? 'selected' : '' }}>
                {{ $category['name'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group mb-0 resource-field {{ $selectedResource === 'shop' ? '' : 'd--none' }}" data-resource="shop">
    <label class="form-label">{{ translate('Shop') }} <span class="text-danger">*</span></label>
    <select class="custom-select" name="shop_id" data-placeholder="{{ translate('Select shop') }}">
        <option value="">{{ translate('Select shop') }}</option>
        @foreach($shops as $shop)
            <option value="{{ $shop['id'] }}" {{ $selectedShop == $shop['id'] ? 'selected' : '' }}>
                {{ $shop['name'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group mb-0 resource-field {{ $selectedResource === 'brand' ? '' : 'd--none' }}" data-resource="brand">
    <label class="form-label">{{ translate('Brand') }} <span class="text-danger">*</span></label>
    <select class="custom-select" name="brand_id" data-placeholder="{{ translate('Select brand') }}">
        <option value="">{{ translate('Select brand') }}</option>
        @foreach($brands as $brand)
            <option value="{{ $brand['id'] }}" {{ $selectedBrand == $brand['id'] ? 'selected' : '' }}>
                {{ $brand['name'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group mb-0 resource-field {{ $selectedResource === 'custom' ? '' : 'd--none' }}" data-resource="custom">
    <label class="form-label">{{ translate('Banner URL') }} <span class="text-danger">*</span></label>
    <input type="url" name="url" class="form-control" placeholder="{{ translate('Enter url') }}"
           value="{{ $selectedUrl }}">
</div>
