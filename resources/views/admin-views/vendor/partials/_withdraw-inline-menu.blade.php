@php($auctionWithdrawEnabled = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction'))
<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/people/vendors/withdraw-list') ? 'active' : '' }}"
               href="{{ route('admin.people.vendors.withdraw-list') }}">
                {{ translate('Withdraw Request') }}
            </a>
        </li>
        @if($auctionWithdrawEnabled)
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/people/vendors/withdraw-list/auction*') ? 'active' : '' }}"
                   href="{{ route('admin.people.vendors.withdraw-list.auction') }}">
                    {{ translate('Auction Withdraw') }}
                </a>
            </li>
        @endif
    </ul>
    <div class="nav--tab__prev">
        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-left"></i>
        </button>
    </div>
    <div class="nav--tab__next">
        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>
</div>
