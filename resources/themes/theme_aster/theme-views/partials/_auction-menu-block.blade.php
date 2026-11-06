@php
    $isAuctionMenuActive = request()->routeIs(
        'auction.list',
        'auction.auctions-request-list',
        'auction.acution-create-product',
        'auction.auction-recreate-product',
        'auction.auction-update-product',
        'auction.profile-view.author-product-details',
    );
    $isAuctionListActive = request()->routeIs('auction.list')
        || (request()->routeIs('auction.profile-view.author-product-details') && empty($isAuctionInactiveState));
    $isMyBidsActive = request()->routeIs(
        'auction.bids.list',
        'auction.profile-view.product-details',
        'auction.bids-product-details',
        'auction.bids-product-details-after-place-order',
    );
    $isSavedAuctionActive = request()->routeIs('auction.saved-products-list');
    $isSalesReportActive = request()->routeIs('auction.sales-report');
    $isTransactionHistoryActive = request()->routeIs('auction.transaction-history');
    $auctionMenuInPanel = ($variant ?? 'sidebar') === 'panel';
    $auctionMenuWrapperClass = $auctionMenuInPanel
        ? 'bg--light rounded p-10px'
        : 'light-box bg--light rounded p-10px mt-3';
@endphp

<div class="{{ $auctionMenuWrapperClass }}">
    <h6 class="fs-14 mb-10px title-clr fw-normal">{{ translate('Auction') }}</h6>
    <div class="d-flex flex-column gap-10px">
        <a href="{{ route('auction.bids.list') }}" class="aside-items-profile aside-items-auction rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal {{ $isMyBidsActive ? 'active' : '' }}">
            @if($auctionMenuInPanel)
                <i class="fi fi-rr-gavel" aria-hidden="true"></i>
            @else
                <img width="20" height="20" src="{{ theme_asset('assets/img/auction/my-bids.svg') }}" alt="" class="w-20px h-20px object-contain svg">
            @endif
            {{ translate('My Bids') }}
        </a>
        <div class="menu--caret-accordion {{ $isAuctionMenuActive ? 'open' : '' }}">
            <div class="card-header cursor-pointer menu--caret aside-items-profile aside-items-auction rounded menu--caret-box p-10px d-flex align-items-center gap-1 justify-content-between sub-header">
                <a href="javascript:void(0)" class="names cursor--pointer d-flex gap-10px align-items-center title-semidark">
                    @if($auctionMenuInPanel)
                        <i class="fi fi-rr-box-open" aria-hidden="true"></i>
                    @else
                        <img width="20" height="20" src="{{ theme_asset('assets/img/auction/my-auctions.svg') }}" alt="" class="w-20px h-20px svg">
                    @endif
                    <span class="line--limit-1 fs-14 fw-normal">
                        {{ translate('My Auctions') }}
                    </span>
                </a>
                <div class="cursor--pointer arrow_rot_icon">
                    <i class="bi bi-chevron-right fs-12 title-clr" aria-hidden="true"></i>
                </div>
            </div>
            <div class="card-body bg-light p-0 py-2 ps-2 bg-white {{ $isAuctionMenuActive ? '' : 'd--none' }}">
                @if(getWebConfig(name: 'auction_feature_status') && getWebConfig(name: 'active_auction_for_customer') == 1)
                    <a href="{{ route('auction.acution-create-product') }}" class="py-2 fs-13 title-clr d-flex align-items-center gap-1 acount-items {{ request()->routeIs('auction.acution-create-product', 'auction.auction-recreate-product', 'auction.auction-update-product') ? 'active' : '' }}">
                        <i class="fi fi-rr-square-plus fs-14" aria-hidden="true"></i> {{ translate('Create Auction') }}
                    </a>
                @endif
                <a href="{{ route('auction.list') }}" class="py-2 fs-13 title-clr d-flex align-items-center gap-1 acount-items {{ $isAuctionListActive ? 'active' : '' }}">
                    <i class="fi fi-rr-list fs-14" aria-hidden="true"></i> {{ translate('Auction List') }}
                </a>
                <a href="{{ route('auction.auctions-request-list') }}" class="py-2 fs-13 title-clr d-flex align-items-center gap-1 acount-items {{ request()->routeIs('auction.auctions-request-list', 'auction.auction-update-product', 'auction.auction-recreate-product') || (request()->routeIs('auction.profile-view.author-product-details') && !empty($isAuctionInactiveState)) ? 'active' : '' }}">
                    <i class="fi fi-rr-list-check fs-14" aria-hidden="true"></i> {{ translate('Auctions Request List') }}
                </a>
            </div>
        </div>
        <a href="{{ route('auction.saved-products-list') }}" class="aside-items-profile aside-items-auction rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal {{ $isSavedAuctionActive ? 'active' : '' }}">
            @if($auctionMenuInPanel)
                <i class="fi fi-rr-bookmark" aria-hidden="true"></i>
            @else
                <img width="20" height="20" src="{{ theme_asset('assets/img/auction/badge.svg') }}" alt="" class="w-20px h-20px object-contain svg">
            @endif
            {{ translate('Saved Auction') }}
        </a>
        <a href="{{ route('auction.sales-report') }}" class="aside-items-profile aside-items-auction rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal {{ $isSalesReportActive ? 'active' : '' }}">
            @if($auctionMenuInPanel)
                <i class="fi fi-rr-chart-histogram" aria-hidden="true"></i>
            @else
                <img width="20" height="20" src="{{ theme_asset('assets/img/auction/display-chartup.svg') }}" alt="" class="w-20px h-20px object-contain svg">
            @endif
            {{ translate('Auction Sales Report') }}
        </a>
        <a href="{{ route('auction.transaction-history') }}" class="aside-items-profile aside-items-auction rounded-1 py-2 px-2 d-flex align-items-center gap-10px text-light-gray fs-14 fw-normal {{ $isTransactionHistoryActive ? 'active' : '' }}">
            <i class="fi fi-rr-exchange fs-14" aria-hidden="true"></i>
            {{ translate('Transaction with Admin') }}
        </a>
    </div>
</div>
