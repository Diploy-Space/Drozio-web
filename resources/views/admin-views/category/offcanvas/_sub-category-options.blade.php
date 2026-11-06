<option value="" disabled selected>{{ translate('Select sub category') }}</option>
@foreach ($subCategories as $subCategory)
    <option value="{{ $subCategory['id'] }}">{{ $subCategory['defaultname'] }}</option>
@endforeach
