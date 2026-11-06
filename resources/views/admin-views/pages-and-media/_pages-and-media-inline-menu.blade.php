@php($isCommitmentsPage = Request::is('admin/settings/pages-and-media/company-reliability'))
@php($isAuctionPage = Request::is('admin/settings/auction/hero-section') || Request::is('admin/settings/auction/company-reliability'))
@php($isPageSetupPage = !$isCommitmentsPage && !$isAuctionPage)

<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">
        @if($isPageSetupPage)
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/settings/pages-and-media/list') ?'active':'' }}"
                   href="{{route('admin.settings.pages-and-media.list') }}">{{ translate('Page Setup') }}</a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/settings/helpTopic/index') ?'active':'' }}"
                   href="{{route('admin.settings.help-topic.list') }}">{{ translate('FAQ') }}</a>
            </li>
        @endif

        @if($isCommitmentsPage && theme_root_path() == 'default')
            <li class="nav-item">
                <a class="nav-link active"
                   href="{{route('admin.settings.pages-and-media.company-reliability') }}">
                    {{ translate('Our Commitments') }}
                </a>
            </li>
        @endif

        @if($isAuctionPage && function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction') && getWebConfig(name: 'auction_feature_status'))
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/settings/auction/hero-section') ? 'active' : '' }}"
                   href="{{route('admin.settings.auction.hero-section') }}">
                    {{ translate('Hero Section Setup') }}
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/settings/auction/company-reliability') ? 'active' : '' }}"
                   href="{{route('admin.settings.auction.company-reliability') }}">
                    {{ translate('Auction Commitments') }}
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
        <button class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>

</div>
