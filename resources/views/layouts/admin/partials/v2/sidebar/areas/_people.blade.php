@php
    use App\Utils\Helpers;
    $v2PeopleAuction = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction');
@endphp

@if (Helpers::module_permission_check('people.customers') && businessModuleSectionVisible('people-customer'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'people-customer' ? 'v2-is-on' : '' }}" data-section="people-customer">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Customer') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Customer Management') }}</span></div>
            <a class="v2-nav-item {{ Request::is('admin/people/customer/list') || Request::is('admin/people/customer/view*') ? 'v2-is-active' : '' }}" data-item="customers" href="{{ route('admin.people.customer.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Customer List') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="customers" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/*/reviews*') ? 'v2-is-active' : '' }}" data-item="reviews" href="{{ route('admin.people.reviews.list', ['business_module' => \App\Enums\BusinessModule::RETAIL]) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Customer Reviews') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="reviews" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/customer/wallet/report') ? 'v2-is-active' : '' }}" data-item="wallet" href="{{ route('admin.people.customer.wallet.report') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Wallet') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wallet" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('admin/people/customer/wallet/bonus-setup') || Request::is('admin/people/customer/wallet/bonus-setup/edit/*')) ? 'v2-is-active' : '' }}" data-item="wallet-bonus" href="{{ route('admin.people.customer.wallet.bonus-setup') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Wallet Bonus Setup') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wallet-bonus" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/customer/loyalty/report') ? 'v2-is-active' : '' }}" data-item="loyalty" href="{{ route('admin.people.customer.loyalty.report') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Loyalty Points') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="loyalty" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/customer/subscriber-list') ? 'v2-is-active' : '' }}" data-item="subscribers" href="{{ route('admin.people.customer.subscriber-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Subscriber') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="subscribers" aria-label="Pin"></button></div>
            </a>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Withdraw') }}</span></div>
            @if ($v2PeopleAuction)
                <a class="v2-nav-item {{ Request::is('admin/people/customer/withdraw/list') ? 'v2-is-active' : '' }}" data-item="customer-withdraw" href="{{ route('admin.people.customer.withdraw.list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraws') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="customer-withdraw" aria-label="Pin"></button></div>
                </a>
            @endif
            <a class="v2-nav-item {{ Request::is('admin/people/customer/withdraw/method-*') ? 'v2-is-active' : '' }}" data-item="customer-withdraw-method" href="{{ route('admin.people.customer.withdraw-method.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraw Methods') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="customer-withdraw-method" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('people.vendors') && businessModuleSectionVisible('people-vendors'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'people-vendors' ? 'v2-is-on' : '' }}" data-section="people-vendors">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Vendors') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Vendors Management') }}</span></div>
            <a class="v2-nav-item {{ Request::is('admin/people/vendors/add') ? 'v2-is-active' : '' }}" data-item="vendor-add" href="{{ route('admin.people.vendors.add') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Vendor') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="vendor-add" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('admin/people/vendors/list') || Request::is('admin/people/vendors/view*')) ? 'v2-is-active' : '' }}" data-item="vendor-list" href="{{ route('admin.people.vendors.vendor-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Vendor List') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="vendor-list" aria-label="Pin"></button></div>
            </a>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Withdraw') }}</span></div>
            <a class="v2-nav-item {{ (Request::is('admin/people/vendors/withdraw-list') || Request::is('admin/people/vendors/withdraw-list/auction*') || Request::is('admin/people/vendors/withdraw-view/*')) ? 'v2-is-active' : '' }}" data-item="vendor-withdraw" href="{{ route('admin.people.vendors.withdraw-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraws') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="vendor-withdraw" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/vendors/withdraw-method/*') ? 'v2-is-active' : '' }}" data-item="vendor-withdraw-method" href="{{ route('admin.people.vendors.withdraw-method.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraw Methods') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="vendor-withdraw-method" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('people.delivery_men') && businessModuleSectionVisible('people-deliveryman'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'people-deliveryman' ? 'v2-is-on' : '' }}" data-section="people-deliveryman">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Deliveryman') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <a class="v2-nav-item {{ (Request::is('admin/people/delivery-man/list') || Request::is('admin/people/delivery-man/update*') || Request::is('admin/people/delivery-man/order-history-log*') || Request::is('admin/people/delivery-man/order-wise-earning*')) ? 'v2-is-active' : '' }}" data-item="delivery" href="{{ route('admin.people.delivery-man.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Deliveryman') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="delivery" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/delivery-man/add') ? 'v2-is-active' : '' }}" data-item="dm-add" href="{{ route('admin.people.delivery-man.add') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Deliveryman') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="dm-add" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('admin/people/delivery-man/withdraw-list') || Request::is('admin/people/delivery-man/withdraw-view*')) ? 'v2-is-active' : '' }}" data-item="dm-withdraw" href="{{ route('admin.people.delivery-man.withdraw-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraws') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="dm-withdraw" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/delivery-man/emergency-contact') ? 'v2-is-active' : '' }}" data-item="dm-emergency" href="{{ route('admin.people.delivery-man.emergency-contact.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Emergency Contact') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="dm-emergency" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('people.team') && businessModuleSectionVisible('people-team'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'people-team' ? 'v2-is-on' : '' }}" data-section="people-team">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Team') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <a class="v2-nav-item {{ (Request::is('admin/people/employee/list') || Request::is('admin/people/employee/add') || Request::is('admin/people/employee/update*')) ? 'v2-is-active' : '' }}" data-item="employees" href="{{ route('admin.people.employee.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Employees') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="employees" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/custom-role*') ? 'v2-is-active' : '' }}" data-item="roles" href="{{ route('admin.people.custom-role.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Roles & Permission') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="roles" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('people.support') && businessModuleSectionVisible('people-support'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'people-support' ? 'v2-is-on' : '' }}" data-section="people-support">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Support') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <a class="v2-nav-item {{ Request::is('admin/people/messages*') ? 'v2-is-active' : '' }}" data-item="inbox" href="{{ route('admin.people.messages.index', ['type' => 'customer']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Inbox') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="inbox" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/support-ticket*') ? 'v2-is-active' : '' }}" data-item="support-ticket" href="{{ route('admin.people.support-ticket.view') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Support Ticket') }}</span></span>
                <div class="v2-nav-right">
                    <button class="v2-pin-btn" type="button" data-pin="support-ticket" aria-label="Pin"></button>
                </div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/people/contact*') ? 'v2-is-active' : '' }}" data-item="contact-form" href="{{ route('admin.people.contact.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Contact Form') }}</span></span>
                <div class="v2-nav-right">
                    @if (($sidebarUnseenMessages ?? 0) > 0)
                        <span data-v2-tag="danger">{{ $sidebarUnseenMessages }}</span>
                    @endif
                    <button class="v2-pin-btn" type="button" data-pin="contact-form" aria-label="Pin"></button>
                </div>
            </a>
        </div>
    </div>
@endif
