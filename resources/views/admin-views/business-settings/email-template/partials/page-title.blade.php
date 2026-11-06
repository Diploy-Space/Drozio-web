@php
    use App\Enums\EmailTemplateKey;
@endphp
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
        <i class="fi fi-sr-template text-primary d-flex"></i>
        {{translate('Email template')}}
    </h2>
    <div>
        <div class="dropdown">
            <button class="dropdown-toggle form-control pe-4" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                {{translate($template['user_type'].'_mail_template')}}
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item {{$template['user_type']=='admin' ? 'active' : ''}}" href="{{route('admin.settings.system-setup.email-templates.view',['admin',EmailTemplateKey::ADMIN_EMAIL_LIST[0]])}}">{{translate('Admin mail template')}}</a>
                <a class="dropdown-item {{$template['user_type']=='vendor' ? 'active' : ''}}" href="{{route('admin.settings.system-setup.email-templates.view',['vendor',EmailTemplateKey::VENDOR_EMAIL_LIST[0]])}}">{{translate('Vendor mail template')}}</a>
                <a class="dropdown-item {{$template['user_type']=='customer' ? 'active' : ''}}" href="{{route('admin.settings.system-setup.email-templates.view',['customer',EmailTemplateKey::CUSTOMER_EMAIL_LIST[0]])}}">{{translate('Customer mail template')}}</a>
                <a class="dropdown-item {{$template['user_type']=='delivery-man' ? 'active' : ''}}" href="{{route('admin.settings.system-setup.email-templates.view',['delivery-man',EmailTemplateKey::DELIVERY_MAN_EMAIL_LIST[0]])}}">{{translate('Delivery Man mail template')}}</a>
            </div>
        </div>
    </div>
</div>
