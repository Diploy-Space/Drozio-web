@foreach ($v2NotifItems as $v2NotifItem)
    <button type="button" class="v2-dropdown-item notification-data-view w-100" data-id="{{ $v2NotifItem->id }}"
            data-url="{{ $v2NotifViewUrl ?? route('vendor.retail.notification.index') }}">
        <span class="v2-notif-title">{{ translate($v2NotifItem->title) }}</span>
        <span class="v2-notif-time">{{ $v2NotifItem->created_at->diffforHumans() }}</span>
        @if ($v2NotifItem->notificationSeenBy === null)
            <span class="v2-notif-new notification_data_new_badge{{ $v2NotifItem->id }}">
                {{ translate('New') }}
            </span>
        @endif
    </button>
@endforeach
