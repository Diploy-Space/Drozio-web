<option value="0" disabled selected>---{{ translate('Select') }}---</option>
@foreach ($categories as $category)
    <option value="{{ $category['id'] }}" @selected($category['id'] == $selectedId)>{{ $category['defaultName'] }}</option>
@endforeach
