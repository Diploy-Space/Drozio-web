<option value="0" disabled selected>{{ translate('Select') }}</option>
@foreach ($options as $optionId => $optionName)
    <option value="{{ $optionId }}">{{ $optionName }}</option>
@endforeach
