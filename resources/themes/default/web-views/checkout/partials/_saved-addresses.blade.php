<div class="dropdown co-saved">
    <button class="btn co-saved__toggle dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="tio-bookmarks"></i>
        {{ translate('Saved Address') }}
    </button>

    <div class="dropdown-menu dropdown-menu-right co-saved__menu">
        @foreach($addresses as $key => $address)
            <div class="dropdown-item co-saved__item {{ $key == 0 ? 'active' : '' }} {{ $itemClass }}" id="{{ $itemIdPrefix }}{{ $key }}">
                <input type="hidden" class="selected_{{ $itemIdPrefix }}{{ $key }}" value="{{ $address }}">
                <input type="hidden" name="{{ $methodField }}" value="{{ $address['id'] }}">
                <i class="tio-briefcase"></i>
                <div>
                    <div class="co-saved__type">{{ $address->address_type }}</div>
                    <div class="co-saved__text">{{ $address->address }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
