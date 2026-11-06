<div class="col-lg-3 profile-aside-column">
    <div class="profile-aside-overlay d-lg-none"></div>
    <div class="__customer-sidebar user-profile-aside" id="shop-sidebar">
        <div class="d-flex justify-content-end d-lg-none">
            <button class="profile-aside-close-btn btn"><i class="fi fi-rr-cross-small fs-5" aria-hidden="true"></i></button>
        </div>

        <div>
            @foreach ($profileMenu as $item)
                <div>
                    <div class="widget-title">
                        <a class="{{ $item['is_active'] ? 'active-menu' : '' }}" href="{{ $item['url'] }}">
                            <i class="fi {{ $item['icon_class'] }} aside-icon" aria-hidden="true"></i>
                            <span class="aside-link">{{ $item['label'] }}</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-lg-none">
            <div class="widget-title">
                <a class="d-flex align-items-center gap-2 customer-account-delete-by-route" href="javascript:"
                    data-route="{{ route('account-delete',[auth('customer')->id()]) }}"
                    data-message="{{translate('Want to delete this account')}}?">
                    <i class="fi fi-rr-trash aside-icon text-danger" aria-hidden="true"></i>
                    <span class="text-capitalize aside-link">{{translate('Delete account')}}</span>
                </a>
            </div>
        </div>

         <div class="border-bottom mt-3 mb-4"></div>

        @if(function_exists('shouldShowAuctionMenu') && shouldShowAuctionMenu())
        <div class="light-box rounded p-10px">
            <h6 class="fs-14 mb-10px title-semidark fw-normal">{{ translate('Auction') }}</h6>
            <div class="d-flex flex-column gap-10px">
                <a href="{{ route('auction.bids.list') }}" class="aside-items-profile aside-items-auction {{ Route::is('auction.bids.list', 'auction.bids-product-details*', 'auction.profile-view.product-details') ? 'active' : '' }} rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal">
                    <i class="fi fi-rr-gavel aside-icon" aria-hidden="true"></i>
                    {{ translate('My Bids') }}
                </a>
                @php($my_auctions_active = Route::is('auction.acution-create-product', 'auction.list', 'auction.auctions-request-list', 'auction.auction-update-product', 'auction.auction-recreate-product'))
                <div class="menu--caret-accordion">
                    <div class="card-header border-0 aside-items-profile aside-items-auction {{ $my_auctions_active ? 'active' : '' }} rounded menu--caret-box p-10px sub-header">
                        <div class="menu--caret w-100 cursor-pointer d-flex  align-items-center gap-1 justify-content-between {{ $my_auctions_active ? 'active' : '' }}">
                            <a href="javascript:void(0)" class="names cursor--pointer d-flex gap-10px align-items-center title-semidark">
                                <i class="fi fi-rr-boxes aside-icon" aria-hidden="true"></i>
                                <span class="line--limit-1 fs-14 fw-normal">
                                   {{ translate('My Auctions') }}
                                </span>
                            </a>
                            <div class="cursor--pointer arrow_rot_icon">
                                <strong><i class="fi fi-rr-angle-right fs-12 title-clr"></i></strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0 pb-2 ps-2 {{ $my_auctions_active ? '' : 'd--none' }} bg-white">
                        @if(getWebConfig(name: 'auction_feature_status') && getWebConfig(name: 'active_auction_for_customer') == 1)
                        <a href="{{ route('auction.acution-create-product') }}" class="py-2 fs-13 {{ Route::is('auction.acution-create-product') ? 'text-primary' : 'title-clr' }} d-flex align-items-center gap-2 acount-items">
                            <i class="fi fi-rr-square-plus aside-sub-icon" aria-hidden="true"></i> {{ translate('Create Auction') }}
                        </a>
                        @endif
                        <a href="{{ route('auction.list') }}" class="py-2 fs-13 {{ Route::is('auction.list', 'auction.auction-update-product', 'auction.auction-recreate-product') ? 'text-primary' : 'title-clr' }} d-flex align-items-center gap-2 acount-items">
                            <i class="fi fi-rr-list aside-sub-icon" aria-hidden="true"></i> {{ translate('Auction List') }}
                        </a>
                        <a href="{{ route('auction.auctions-request-list') }}" class="py-2 fs-13 {{ Route::is('auction.auctions-request-list') ? 'text-primary' : 'title-clr' }} d-flex align-items-center gap-2 acount-items">
                            <i class="fi fi-rr-list-check aside-sub-icon" aria-hidden="true"></i> {{ translate('Auctions Request List') }}
                        </a>
                    </div>
                </div>
                <a href="{{ route('auction.saved-products-list') }}" class="aside-items-profile aside-items-auction {{ Route::is('auction.saved-products-list') ? 'active' : '' }} rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal">
                    <i class="fi fi-rr-bookmark aside-icon" aria-hidden="true"></i>
                    {{ translate('Saved Auction') }}
                </a>
                <a href="{{ route('auction.sales-report') }}" class="aside-items-profile aside-items-auction {{ Route::is('auction.sales-report') ? 'active' : '' }} rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal">
                    <i class="fi fi-rr-chart-histogram aside-icon" aria-hidden="true"></i>
                    {{ translate('Auction Sales Report') }}
                </a>
                <a href="{{ route('auction.transaction-history') }}" class="aside-items-profile aside-items-auction {{ Route::is('auction.transaction-history') ? 'active' : '' }} rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal">
                    <i class="fi fi-rr-exchange aside-icon" aria-hidden="true"></i>
                    {{ translate('Transaction with Admin') }}
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
