<?php

namespace Tests\Feature\Contract\Auction;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\Auction\Concerns\RequiresAuctionAddon;
use Tests\Feature\Contract\ContractTestCase;

class AuctionRouteMapTest extends ContractTestCase
{
    use RequiresAuctionAddon;

    private const CUSTOMER_NS = 'Modules\Auction\app\Http\Controllers\RestAPI\v6\Customer\\';

    private const VENDOR_NS = 'Modules\Auction\app\Http\Controllers\RestAPI\v6\Vendor\\';

    public static function customerRoutes(): array
    {
        return [
            'browse list' => ['GET', 'api/v6/auction/products', 'ProductController@getList', false],
            'suggestions' => ['GET', 'api/v6/auction/products/suggestions', 'ProductController@getSuggestions', false],
            'filter ranges' => ['GET', 'api/v6/auction/products/filter-ranges', 'ProductController@getFilterRanges', false],
            'product by slug' => ['GET', 'api/v6/auction/products/some-slug', 'ProductController@show', false],
            'categories' => ['GET', 'api/v6/auction/categories', 'CategoryController@getList', false],
            'brands' => ['GET', 'api/v6/auction/brands', 'BrandController@getList', false],
            'constants' => ['GET', 'api/v6/auction/constants', 'ConstantController@getList', false],
            'tags' => ['GET', 'api/v6/auction/tags', 'TagController@getList', false],

            'my bids' => ['GET', 'api/v6/auction/products/my-bids', 'ProductController@getMyBidList', true],
            'my auctions' => ['GET', 'api/v6/auction/products/my-auctions', 'ProductController@getMyAuctionList', true],
            'pending approval' => ['GET', 'api/v6/auction/products/pending-approval', 'ProductController@getPendingApprovalList', true],
            'saved products' => ['GET', 'api/v6/auction/saved-products', 'SavedProductController@getList', true],
            'recent views' => ['GET', 'api/v6/auction/recent-views', 'RecentViewController@getList', true],
            'withdraw list' => ['GET', 'api/v6/auction/withdraws', 'WithdrawController@getList', true],
            'withdraw preview' => ['GET', 'api/v6/auction/withdraws/preview/9', 'WithdrawController@preview', true],
            'commission status' => ['GET', 'api/v6/auction/products/9/commission', 'CommissionController@status', true],
            'dashboard summary' => ['GET', 'api/v6/auction/dashboard-summary', 'DashboardController@getSummary', true],
            'notifications' => ['GET', 'api/v6/auction/notifications', 'NotificationController@getList', true],
            'create auction' => ['POST', 'api/v6/auction/products', 'ProductController@store', true],
            'update auction' => ['POST', 'api/v6/auction/products/9', 'ProductController@update', true],
        ];
    }

    public static function vendorRoutes(): array
    {
        return [
            'product list' => ['GET', 'api/v6/auction/seller/products', 'ProductController@getList'],
            'pending approval' => ['GET', 'api/v6/auction/seller/products/pending-approval', 'ProductController@getPendingApprovalList'],
            'product details' => ['GET', 'api/v6/auction/seller/products/9', 'ProductController@details'],
            'commission status' => ['GET', 'api/v6/auction/seller/products/9/commission', 'CommissionController@status'],
            'withdraw list' => ['GET', 'api/v6/auction/seller/withdraws', 'WithdrawController@getList'],
            'withdraw preview' => ['GET', 'api/v6/auction/seller/withdraws/preview/9', 'WithdrawController@preview'],
            'vat report' => ['GET', 'api/v6/auction/seller/vat-report', 'VatReportController@getList'],
            'create auction' => ['POST', 'api/v6/auction/seller/products', 'ProductController@store'],
            'update auction' => ['POST', 'api/v6/auction/seller/products/9', 'ProductController@update'],
            'update status' => ['PUT', 'api/v6/auction/seller/products/9/status', 'ProductController@updateStatus'],
        ];
    }

    #[Test]
    #[DataProvider('customerRoutes')]
    public function customer_uri_resolves_to_its_own_handler(string $verb, string $uri, string $action, bool $authenticated): void
    {
        $route = $this->matchRoute($verb, $uri);

        $this->assertSame(self::CUSTOMER_NS.$action, $route->getActionName(), "[{$verb} {$uri}] resolved to the wrong handler");

        $authenticated
            ? $this->assertContains('auth:api', $route->gatherMiddleware(), "[{$verb} {$uri}] must require customer authentication")
            : $this->assertNotContains('auth:api', $route->gatherMiddleware(), "[{$verb} {$uri}] must stay public");
    }

    #[Test]
    #[DataProvider('vendorRoutes')]
    public function vendor_uri_resolves_to_its_own_handler(string $verb, string $uri, string $action): void
    {
        $route = $this->matchRoute($verb, $uri);

        $this->assertSame(self::VENDOR_NS.$action, $route->getActionName(), "[{$verb} {$uri}] resolved to the wrong handler");
        $this->assertContains('v6_seller_auth', $route->gatherMiddleware(), "[{$verb} {$uri}] must require seller authentication");
    }

    #[Test]
    public function no_auction_module_controller_answers_on_a_retired_api_version(): void
    {
        $retired = [];

        foreach (app('router')->getRoutes() as $route) {
            $handledByAuctionModule = str_starts_with((string) $route->getActionName(), 'Modules\\Auction\\');

            if ($handledByAuctionModule && preg_match('#^api/v(1|2|3)/#', $route->uri())) {
                $retired[] = $route->uri();
            }
        }

        $this->assertSame([], $retired, 'the Auction module still answers on a retired API version');
    }

    #[Test]
    public function no_auction_route_survives_from_the_retired_storefront(): void
    {
        $retired = [];

        foreach (app('router')->getRoutes() as $route) {
            if (str_starts_with((string) $route->getName(), 'auction.old.')) {
                $retired[] = $route->getName();
            }
        }

        $this->assertSame([], $retired, 'legacy auction.old.* routes are still registered');
    }

    private function matchRoute(string $verb, string $uri): Route
    {
        return app('router')->getRoutes()->match(Request::create($uri, $verb));
    }
}
