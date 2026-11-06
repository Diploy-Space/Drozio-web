<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate($data['type'].'_product_List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Filter Criteria').' - ' }}</th>
                <th></th>
                <th>
                    @if(isset($data['vendor']))
                        {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
                        <br>
                    @endif
                    {{translate('Category').' - '. ($data['category'] != 'all' ? $data['category']['name'] : $data['category'])  }}
                    <br>
                    {{translate('Subcategory').' - '. ($data['sub_category'] != 'all' ? $data['sub_category']['name'] : $data['sub_category'])  }}
                    <br>
                    {{translate('Sub Sub Category').' - '. ($data['sub_sub_category'] != 'all' ? $data['sub_sub_category']['name'] : $data['sub_sub_category'])  }}
                    <br>
                    {{translate('Brand').' - '. ($data['brand'] != 'all' ? $data['brand']['defaultName'] : $data['brand'])  }}

                    @if($data['type']=='seller')
                        <br>
                        {{translate('Store').' - '. ($data['seller']?->shop->name ?? translate('All'))}}
                        <br>
                        {{translate('Status').' - '. ($data['status']==0 ? translate('Pending') : ($data['status'] == 1 ? translate('Approved') : translate('Denied')) )}}
                    @endif
                    <br>
                    {{translate('Search Bar Content').' - '. (!empty($data['searchValue']) ?  ucwords($data['searchValue']) : 'N/A') }}
                </th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}</td>
                <td> {{translate('Product Image')}}	</td>
                <td> {{translate('Image URL')}}	</td>
                <td> {{translate('Product Name')}}	</td>
                <td> {{translate('Product SKU')}}</td>
                <td> {{translate('Description')}}</td>

                @if($data['type']=='seller')
                    <td> {{translate('Store Name')}}</td>
                @endif
                <td> {{translate('Category Name')}}</td>
                <td> {{translate('Sub Category Name')}}</td>
                <td> {{translate('Sub Sub Category Name')}}</td>
                <td> {{translate('Brand')}}</td>
                <td> {{translate('Product Type')}}</td>
                <td> {{translate('Price')}}</td>
                <td> {{translate('Discount')}}</td>
                <td> {{translate('Discount Type')}}</td>
                <td> {{translate('Rating')}}</td>
                <td> {{translate('Product Tags')}}</td>
                <td> {{translate('Status')}}</td>
                @if ($data['productWiseTax'])
                    <th>{{ translate('VAT/TAX') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($data['products'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td style="height: 200px"></td>
                    <td>{{dynamicStorage(path: 'storage/app/public/product/thumbnail/'.$item->thumbnail)}}</td>
                    <td> {{$item->name}}</td>
                    <td>{{$item->code}}</td>
                    <td>{{strip_tags(str_replace('&nbsp;', ' ', $item->details))}}</td>
                    @if($data['type']=='seller')
                        <td>{{ucwords($item?->seller?->shop->name ?? translate('Not found'))}}</td>
                    @endif
                    <td>{{ $item?->category->name ?? 'N/A'}}</td>
                    <td>{{ $item?->subCategory->name ?? 'N/A'}}</td>
                    <td>{{ $item?->subSubCategory->name ?? 'N/A'}}</td>
                    <td>{{ $item?->brand?->name ?? 'N/A'}}</td>
                    <td>{{ $item?->product_type}}</td>
                    <td>{{setCurrencySymbol(amount: usdToDefaultCurrency($item['unit_price'] ?? 0), currencyCode: getCurrencyCode())}}</td>
                    <td>{{setCurrencySymbol(amount: usdToDefaultCurrency($item['discount'] ?? 0), currencyCode: getCurrencyCode())}}</td>
                    <td>{{$item->discount_type}}</td>
                    <td>{{$item?->rating && count($item->rating) > 0 ?  number_format($item->rating[0]->average,2) : 'N/A'}}</td>
                    <td>
                        @if($item->tags)
                            @foreach ($item->tags as $tag)
                                {{$tag->tag}},
                            @endforeach
                        @endif
                    </td>
                    <td> {{translate($item->status == 1 ? 'active' : 'inactive')}}</td>
                    @if ($data['productWiseTax'])
                        <td>
                            <span class="d-block font-size-sm text-body">
                                @forelse ($item?->taxVats as $key => $taxVat)
                                    <br>
                                    <span> {{ $taxVat?->tax?->name }} :
                                        <span class="font-bold">
                                            ({{ $taxVat?->tax?->tax_rate }}%)
                                        </span>
                                    </span>
                                    <br>
                                @empty
                                    <span> {{ translate('N/A') }} </span>
                                @endforelse
                            </span>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
