<option value="" disabled selected>{{ translate('Select Vendor') }}</option>
@foreach($vendors as $vendorId => $vendorName)
    <option value="{{ $vendorId }}" @selected(($selectedVendor ?? null) !== null && (string) $selectedVendor === (string) $vendorId)>{{ $vendorName }}</option>
@endforeach
