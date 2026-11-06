<form action="{{route('admin.settings.system-setup.currency.status')}}" method="post" data-from="currency"
      id="currency-status{{$currency['id']}}-form" class="currency_status_form no-reload-form">
    @csrf
    <input type="hidden" name="id" value="{{$currency['id']}}">
    <label class="switcher " for="currency-status{{$currency['id']}}">
        <input
            class="switcher_input custom-modal-plugin"
            type="checkbox" value="1" name="status"
            id="currency-status{{$currency['id']}}"
            {{$currency->status?'checked':''}}
            data-modal-type="input-change-form"
            data-modal-form="#currency-status{{$currency['id']}}-form"
            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/currency-on.png') }}"
            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/currency-off.png') }}"
            data-on-title = "{{translate('Want to Turn ON :item', ['item' => translate('Currency Status')]).'?'}}"
            data-off-title = "{{translate('Want to Turn OFF :item', ['item' => translate('Currency Status')]).'?'}}"
            data-on-message = "<p>{{translate('If enabled this currency will be available throughout the entire system')}}</p>"
            data-off-message = "<p>{{translate('If disabled this currency will be hidden from the entire system')}}</p>"
            data-on-button-text="{{ translate('Turn On') }}"
            data-off-button-text="{{ translate('Turn Off') }}">
        <span class="switcher_control"></span>
    </label>
</form>
