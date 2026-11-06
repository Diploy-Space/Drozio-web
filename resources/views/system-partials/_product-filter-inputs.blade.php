@php
    $activeFilterKeys = ['filter_sort_by', 'filter_product_types', 'product_status', 'filter_brand_ids', 'filter_shop_ids', 'filter_category_ids', 'filter_sub_category_ids', 'filter_sub_sub_category_ids'];
@endphp

@foreach($activeFilterKeys as $activeFilterKey)
    @foreach((array) request($activeFilterKey, []) as $activeFilterValue)
        <input type="hidden" name="{{ $activeFilterKey }}{{ is_array(request($activeFilterKey)) ? '[]' : '' }}" value="{{ $activeFilterValue }}">
    @endforeach
@endforeach
