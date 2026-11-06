<div class="sh-account__group">
    <span class="sh-account__group-title">{{ translate('Auction') }}</span>
    <a class="sh-account__link" href="{{ route('auction.bids.list') }}">
        <span class="sh-account__link-icon"><i class="fi fi-rr-gavel"></i></span>
        <span>{{ translate('My Bids') }}</span>
    </a>
    <button class="sh-account__link sh-account__link--toggle" type="button"
            data-sh-tree="#shAccountMyAuctions{{ $scope }}" aria-expanded="false">
        <span class="sh-account__link-icon"><i class="fi fi-rr-boxes"></i></span>
        <span>{{ translate('My Auctions') }}</span>
        <i class="fi fi-rr-angle-small-right sh-account__link-caret" aria-hidden="true"></i>
    </button>
    <div class="sh-account__sublinks" id="shAccountMyAuctions{{ $scope }}">
        @if(getWebConfig(name: 'auction_feature_status') && getWebConfig(name: 'active_auction_for_customer') == 1)
            <a class="sh-account__sublink" href="{{ route('auction.acution-create-product') }}">{{ translate('Create Auction') }}</a>
        @endif
        <a class="sh-account__sublink" href="{{ route('auction.list') }}">{{ translate('Auction List') }}</a>
        <a class="sh-account__sublink" href="{{ route('auction.auctions-request-list') }}">{{ translate('Auctions Request List') }}</a>
    </div>
    <a class="sh-account__link" href="{{ route('auction.saved-products-list') }}">
        <span class="sh-account__link-icon"><i class="fi fi-rr-bookmark"></i></span>
        <span>{{ translate('Saved Auction') }}</span>
    </a>
    <a class="sh-account__link" href="{{ route('auction.sales-report') }}">
        <span class="sh-account__link-icon"><i class="fi fi-rr-chart-histogram"></i></span>
        <span>{{ translate('Auction Sales Report') }}</span>
    </a>
    <a class="sh-account__link" href="{{ route('auction.transaction-history') }}">
        <span class="sh-account__link-icon"><i class="fi fi-rr-exchange"></i></span>
        <span>{{ translate('Transaction with Admin') }}</span>
    </a>
    @if(filled($auctionNotificationTarget ?? null))
        <a class="sh-account__link" href="javascript:" data-sh-drawer-close
           data-bs-toggle="offcanvas" data-bs-target="{{ $auctionNotificationTarget }}">
            <span class="sh-account__link-icon">
                <i class="fi fi-rr-bell"></i>
                @if($hasUnreadAuctionNotification ?? false)
                    <span class="sh-account__link-dot"></span>
                @endif
            </span>
            <span>{{ translate('Notification') }}</span>
        </a>
    @endif
</div>
