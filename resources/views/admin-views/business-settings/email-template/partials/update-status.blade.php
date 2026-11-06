<div class="card mb-3">
    <div class="card-body">
        <form
            action="{{route('admin.settings.system-setup.email-templates.update-status', [$template['template_name'], $template['user_type']])}}"
            method="post" id="email-template-status-form" enctype="multipart/form-data"
            class="no-reload-form"
        >
            @csrf
            <div class="row align-items-center">
                <div class="col-md-8 col-xl-9">
                    <h2>{{translate('Get email on').translate(str_replace('-','_',$template['template_name'])).' ?'}}</h2>
                    <p class="mb-0 fs-12">
                        {{ translate('Enable this option to send email notifications.') }}
                    </p>
                </div>
                <div class="col-md-4 col-xl-3">
                    <div class="mt-3 mt-md-0">
                        <div
                            class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-3 user-select-none">
                            <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                            <label class="switcher " for="email-template-status">
                                <input
                                    class="switcher_input custom-modal-plugin"
                                    type="checkbox" value="1" name="status"
                                    id="email-template-status"
                                    {{ $template['status'] == 1 ? 'checked':'' }}
                                    data-modal-type="input-change-form"
                                    data-modal-form="#email-template-status-form"
                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/mail-status-on.png') }}"
                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/mail-status-off.png') }}"
                                    data-on-title="{{translate('Want to Turn ON :item', ['item' => translate('Mail')]).'?'}}"
                                    data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('Mail')]).'?'}}"
                                    data-on-message="<p>{{translate('If enabled users will receive this mail').'.'}}</p>"
                                    data-off-message="<p>{{translate('If disabled users would not receive this mail').'.'}}</p>"
                                    data-on-button-text="{{ translate('Turn On') }}"
                                    data-off-button-text="{{ translate('Turn Off') }}">
                                <span class="switcher_control"></span>
                            </label>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
