<?php

namespace App\Traits;

use App\Enums\BusinessModule;
use App\Models\AnalyticScript;
use App\Models\Banner;
use App\Models\BusinessPage;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\DealOfTheDay;
use App\Models\HelpTopic;
use App\Models\MostDemanded;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\RobotsMetaContent;
use App\Models\Seller;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Models\StockClearanceProduct;
use App\Models\StockClearanceSetup;
use App\Models\Tag;
use App\Services\Cache\AppCache;
use App\Utils\BrandManager;
use App\Utils\ProductManager;
use App\Utils\SaleCacheManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

trait CacheManagerTrait
{
    private const HOME_CATEGORY_PRODUCT_LIMIT = 10;

    public function cacheBusinessSettingsTable()
    {
        return AppCache::rememberInGroup('settings:business_settings:all', AppCache::GROUP_SETTINGS, function () {
            return BusinessSetting::all();
        });
    }

    public function cacheBannerTable($bannerType = null, $dataLimit = null, string $businessModule = BusinessModule::RETAIL)
    {
        // Banner rows are filtered by active theme and business module, so the key is
        // scoped by both (the legacy CACHE_BANNER_TABLE key was global — a cross-theme leak,
        // and a theme-only key let a wholesale banner render on the retail storefront).
        $banners = AppCache::rememberInGroup('content:banners:'.$businessModule.':'.theme_root_path(), AppCache::GROUP_CONTENT, function () use ($businessModule) {
            return Banner::with(['storage'])->where(['published' => 1, 'theme' => theme_root_path(), 'business_module' => $businessModule])->orderBy('id', 'desc')->latest('created_at')->get();
        });
        if ($bannerType && $dataLimit) {
            return $banners?->where('banner_type', $bannerType)->take($dataLimit);
        } elseif ($bannerType) {
            return $banners?->firstWhere('banner_type', $bannerType);
        }

        return $banners;
    }

    public function cacheCurrencyTable()
    {
        return AppCache::rememberInGroup('locale:currency:all', AppCache::GROUP_LOCALE, function () {
            return Currency::all();
        });
    }

    public function cacheColorsList()
    {
        return Cache::remember(CACHE_FOR_ALL_COLOR_LIST, CACHE_FOR_3_HOURS, function () {
            return Color::all();
        });
    }

    public function cacheProductsColorsArray()
    {
        return Cache::remember(CACHE_FOR_ALL_PRODUCTS_COLOR_LIST, CACHE_FOR_3_HOURS, function () {
            return ProductManager::getProductsColorsArray();
        });
    }

    public function cacheProductsReviews()
    {
        return Cache::remember(CACHE_FOR_ALL_PRODUCTS_REVIEW_LIST, CACHE_FOR_3_HOURS, function () {
            return Review::active()->where('status', 1)->whereNull('delivery_man_id')->get();
        });
    }

    public function cacheShopTable()
    {
        $productReviews = $this->cacheProductsReviews();

        return Cache::remember(CACHE_SHOP_TABLE, CACHE_FOR_3_HOURS, function () use ($productReviews) {
            return Shop::active()
                ->withCount(['products' => function ($query) {
                    $query->active();
                }])
                ->with('seller', function ($query) {
                    $query->with('product', function ($query) {
                        $query->active()->withoutGlobalScopes();
                    })->withCount(['orders']);
                })
                ->get()
                ->each(function ($shop) use ($productReviews) {
                    $shop->orders_count = $shop->seller->orders_count;
                    $productIds = $shop->seller->product->pluck('id')->toArray();
                    $productReviews = $productReviews->whereIn('product_id', $productIds);
                    $productReviews = $productReviews->where('status', 1);
                    $shop->average_rating = $productReviews->avg('rating');
                    $shop->review_count = $productReviews->count();
                    $shop->total_rating = $productReviews->sum('rating');

                    $positiveReviewsCount = $productReviews->where('rating', '>=', 4)->count();
                    $shop->positive_review = ($shop->review_count !== 0) ? ($positiveReviewsCount * 100) / $shop->review_count : 0;
                    $shop->is_vacation_mode_now = checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $shop);

                    return $shop;
                });
        });
    }

    public function cacheHelpTopicTable()
    {
        return Cache::remember(CACHE_HELP_TOPICS_TABLE, CACHE_FOR_3_HOURS, function () {
            return HelpTopic::where(['type' => 'default', 'status' => 1])->get();
        });
    }

    public function cacheTagTable()
    {
        return Cache::remember(CACHE_TAGS_TABLE, CACHE_FOR_3_HOURS, function () {
            return Tag::orderBy('visit_count', 'desc')->take(15)->get();
        });
    }

    public function cacheInHouseShippingType()
    {
        return Cache::remember(CACHE_FOR_IN_HOUSE_SHIPPING_TYPE, CACHE_FOR_3_HOURS, function () {
            $adminShipping = ShippingType::where(['seller_id' => 0])->first();

            return isset($adminShipping) ? $adminShipping->shipping_type : 'order_wise';
        });
    }

    public function cacheMainCategoriesList()
    {
        // Category names are translated → key MUST include the language, else the
        // first locale to warm the cache is served to every locale (legacy bug).
        return AppCache::rememberInGroup('catalog:categories:main:'.(getDefaultLanguage() ?? 'en'), AppCache::GROUP_CATALOG, function () {
            return Category::with(['product' => function ($query) {
                return $query->active()->withCount(['orderDetails']);
            }])->withCount(['product' => function ($query) {
                $query->active();
            }])->with(['childes' => function ($query) {
                $query->with(['childes' => function ($query) {
                    $query->withCount(['subSubCategoryProduct' => function ($query) {
                        $query->active();
                    }])->where('position', 2);
                }])->withCount(['subCategoryProduct' => function ($query) {
                    $query->active();
                }])->where('position', 1);
            }, 'childes.childes'])->where('position', 0)->businessModule()->get();
        });
    }

    public function cacheHomeCategoriesList()
    {
        $homeCategories = AppCache::rememberInGroup('catalog:categories:home:'.(getDefaultLanguage() ?? 'en'), AppCache::GROUP_CATALOG, function () {
            $homeCategories = Category::whereHas('product', function ($query) {
                return $query->active();
            })->with(['product' => function ($query) {
                // reviews_count comes from applyCategoryWiseProductPriority — adding it here too
                // duplicates the alias, which the derived table the limit below builds rejects.
                $products = $query->active()
                    ->with([
                        'flashDealProducts' => function ($query) {
                            return $query->with(['flashDeal']);
                        },
                        'clearanceSale' => function ($query) {
                            return $query->activeIgnoringTimeWindow();
                        },
                    ]);

                // limit() on a HasMany eager load is a per-parent LIMIT, so each category costs
                // the rows it renders instead of its whole catalogue.
                ProductManager::applyCategoryWiseProductPriority(query: $products)
                    ->limit(self::HOME_CATEGORY_PRODUCT_LIMIT);
            }])->where('home_status', true)->businessModule()->get();

            return $homeCategories;
        });

        // Clearance active-state and flash_deal_status are time-relational, so they are
        // resolved per request on the cached set rather than frozen inside the cache.
        $homeCategories->each(function ($data) {
            $products = $data['product'];
            if ($products) {
                SaleCacheManager::normalizeClearanceState($products);
                $products->each(function ($product) {
                    $flashDeal = count($product->flashDealProducts) > 0 ? $product->flashDealProducts[0]->flashDeal : null;
                    $product['flash_deal_status'] = $flashDeal && $flashDeal->isCurrentlyActive() ? 1 : 0;
                });
            }
            $data['products'] = $products?->take(self::HOME_CATEGORY_PRODUCT_LIMIT);
            $data->setRelation('product', $products?->take(self::HOME_CATEGORY_PRODUCT_LIMIT));
        });

        return $homeCategories;
    }

    public function cachePriorityWiseBrandList()
    {
        // Brand names are translated → language-scope the key (the legacy global
        // key wrapped a language-scoped inner accessor, leaking one locale to all).
        return AppCache::rememberInGroup('catalog:brands:priority:'.(getDefaultLanguage() ?? 'en'), AppCache::GROUP_CATALOG, function () {
            return BrandManager::getActiveBrandWithCountingAndPriorityWiseSorting();
        });
    }

    public function cacheClearanceSaleSetupTable($shopId)
    {
        $config = null;
        $settings = Cache::remember(CACHE_CLEARANCE_SALE_SETUP_TABLE, CACHE_FOR_3_HOURS, function () {
            return StockClearanceSetup::all();
        });
        $data = $settings?->firstWhere('shop_id', $shopId);

        return $data ?? $config;
    }

    public function cacheClearanceSaleProductsCount()
    {
        return SaleCacheManager::rememberUntilBoundary(CACHE_FOR_CLEARANCE_SALE_PRODUCTS_COUNT, fn () => SaleCacheManager::nextClearanceBoundary(), function () {
            return StockClearanceProduct::active()->whereHas('product', function ($query) {
                return $query->active();
            })->count();
        });
    }

    public function cacheHomePageClearanceSaleProducts($dataLimit = 10)
    {
        return SaleCacheManager::rememberUntilBoundary(CACHE_FOR_HOME_PAGE_CLEARANCE_SALE_PRODUCTS, fn () => SaleCacheManager::nextClearanceBoundary(), function () use ($dataLimit) {
            $productIds = StockClearanceProduct::active()->whereHas('setup', function ($query) {
                $addedBy = getWebConfig(name: 'stock_clearance_vendor_offer_in_homepage') ? ['admin', 'vendor'] : ['admin'];

                return $query->where('show_in_homepage', 1)->whereIn('setup_by', $addedBy);
            })->whereHas('product', function ($query) {
                return $query->active()->with(['reviews', 'rating'])->withCount('reviews');
            })->with('product', function ($query) {
                return $query->active();
            })->pluck('product_id')->toArray();

            $products = Product::active()->with(['reviews', 'rating', 'seller.shop', 'clearanceSale' => function ($query) {
                return $query->active();
            }])->withCount('reviews')->whereIn('id', $productIds);

            return ProductManager::getPriorityWiseClearanceSaleProductsQuery(query: $products, dataLimit: $dataLimit);
        });
    }

    public function cacheHomePageFeaturedProductList($dataLimit = 12)
    {
        return SaleCacheManager::normalizeClearanceState(Cache::remember(CACHE_FOR_FEATURED_PRODUCTS_LIST, CACHE_FOR_3_HOURS, function () use ($dataLimit) {
            return ProductManager::getPriorityWiseFeaturedProductsQuery(
                // reviews + seller.shop eager-loaded: the product cards call
                // count($product->reviews) and $product->seller->shop per item (N+1).
                query: Product::active()->with(['reviews', 'seller.shop', 'clearanceSale' => function ($query) {
                    return $query->activeIgnoringTimeWindow();
                }]),
                dataLimit: $dataLimit
            );
        }));
    }

    public function cacheHomePageNewArrivalProductList($dataLimit = 8)
    {
        return SaleCacheManager::normalizeClearanceState(Cache::remember(CACHE_FOR_HOME_PAGE_NEW_ARRIVAL_PRODUCT_LIST, CACHE_FOR_3_HOURS, function () use ($dataLimit) {
            return ProductManager::getPriorityWiseNewArrivalProductsQuery(
                // reviews + seller.shop eager-loaded: cards call count($product->reviews)
                // and $product->seller->shop per item (N+1).
                query: Product::active()->with(['reviews', 'seller.shop', 'clearanceSale' => function ($query) {
                    return $query->activeIgnoringTimeWindow();
                }]),
                dataLimit: $dataLimit
            );
        }));
    }

    public function cacheHomePageDealOfTheDay()
    {
        $dealOfTheDay = Cache::remember(CACHE_FOR_HOME_PAGE_DEAL_OF_THE_DAY, CACHE_FOR_3_HOURS, function () {
            return DealOfTheDay::with(['product' => function ($query) {
                // reviews eager-loaded: the deal-of-the-day card counts $product->reviews.
                return $query->active()->with(['reviews', 'clearanceSale' => function ($query) {
                    return $query->activeIgnoringTimeWindow();
                }]);
            }])
                ->join('products', 'products.id', '=', 'deal_of_the_days.product_id')
                ->select('deal_of_the_days.*', 'products.unit_price')
                ->where('products.status', 1)
                ->where('deal_of_the_days.status', 1)
                ->first();
        });
        SaleCacheManager::normalizeClearanceState($dealOfTheDay?->product);

        return $dealOfTheDay;
    }

    public function cacheRobotsMetaContent(string $page)
    {
        $config = null;
        $settings = Cache::remember(CACHE_ROBOTS_META_CONTENT_TABLE, CACHE_FOR_3_HOURS, function () {
            return RobotsMetaContent::all();
        });
        $data = $settings?->firstWhere('page_name', $page);
        if (! $data) {
            $data = $settings?->firstWhere('page_name', 'default');
        }

        return $data ?? $config;
    }

    public function cacheHomePageTopVendorsList()
    {
        $productReviews = $this->cacheProductsReviews();

        return Cache::remember(CACHE_FOR_HOME_PAGE_TOP_VENDORS_LIST, CACHE_FOR_3_HOURS, function () use ($productReviews) {
            $inHouseProductIds = Product::active()->where('added_by', 'admin')->pluck('id');
            $topVendorsList = Shop::active()
                ->withCount(['products' => function ($query) {
                    $query->active();
                }])
                ->with(['products' => function ($query) {
                    $query->active()->with(['seller.shop', 'seller.storage', 'storage', 'reviews' => function ($query) {
                        return $query->active();
                    }]);
                }])
                ->with('seller', function ($query) {
                    $query->with('product', function ($query) {
                        $query->active()->with('reviews', function ($query) {
                            $query->active();
                        });
                    }, 'storage')->with('coupon')->withCount(['orders']);
                })
                ->get()
                ->each(function ($shop) use ($productReviews) {
                    $productIds = $shop?->products?->pluck('id')->toArray() ?? [];
                    $productReviews = $productReviews->whereIn('product_id', $productIds);
                    $productReviews = $productReviews->where('status', 1);
                    $shop->average_rating = $productReviews->avg('rating');
                    $shop->review_count = $productReviews->count();
                    $shop->total_rating = $productReviews->sum('rating');

                    $shop->products = Arr::random($shop->products->toArray(), count($shop->products) < 3 ? count($shop->products) : 3);
                    $shop->orders_count = $shop->seller->orders_count;
                    $shop->coupon_list = $shop?->seller?->coupon ?? null;

                    $positiveReviewsCount = $productReviews->where('rating', '>=', 4)->count();
                    $shop->positive_review = ($shop->review_count !== 0) ? ($positiveReviewsCount * 100) / $shop->review_count : 0;
                    $shop->is_vacation_mode_now = checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $shop);
                });

            $inHouseCoupon = Coupon::where(['added_by' => 'admin', 'coupon_bearer' => 'inhouse', 'status' => 1])
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->whereDate('expire_date', '>=', date('Y-m-d'))->get();

            $inHouseProductCount = $inHouseProductIds->count();

            $inHouseReviewData = Review::active()->whereIn('product_id', $inHouseProductIds);
            $inHouseReviewDataCount = $inHouseReviewData->count();
            $inHouseRattingStatusPositive = 0;
            foreach ($inHouseReviewData->pluck('rating') as $singleRating) {
                ($singleRating >= 4 ? ($inHouseRattingStatusPositive++) : '');
            }

            $inHouseShop = getInHouseShopConfig();
            $inHouseShop->products_count = $inHouseProductCount;
            $inHouseShop->coupon_list = $inHouseCoupon;
            $inHouseShop->total_rating = $inHouseReviewDataCount;
            $inHouseShop->review_count = $inHouseReviewDataCount;
            $inHouseShop->average_rating = $inHouseReviewData->avg('rating');
            $inHouseShop->positive_review = $inHouseReviewDataCount != 0 ? ($inHouseRattingStatusPositive * 100) / $inHouseReviewDataCount : 0;
            $inHouseShop->orders_count = Order::where(['seller_is' => 'admin'])->count();
            $inHouseShop->products = $inHouseProductCount > 0
                ? Product::active()->whereIn('id', $inHouseProductIds->random(min(3, $inHouseProductCount))->all())->get()->toArray()
                : [];

            return $topVendorsList->prepend($inHouseShop);
        });
    }

    public function cacheHomePageMoreVendorsList()
    {
        return Cache::remember(CACHE_FOR_HOME_PAGE_MORE_VENDORS_LIST, CACHE_FOR_3_HOURS, function () {
            $productReviews = $this->cacheProductsReviews();

            return Seller::approved()
                ->with(['shop' => function ($query) {
                    return $query->with(['seller' => function ($query) {
                        return $query->withCount(['orders']);
                    }]);
                }, 'product' => function ($query) {
                    $query->active();
                }])
                ->whereHas('shop')
                ->withCount(['product' => function ($query) {
                    $query->active();
                }])
                ->inRandomOrder()
                ->take(8)
                ->get()
                ->each(function ($seller) use ($productReviews) {
                    $productIds = $seller->product?->pluck('id')->toArray() ?? [];
                    $filteredReviews = $productReviews->whereIn('product_id', $productIds)->where('status', 1);
                    $seller->average_rating = $filteredReviews->avg('rating');
                    $seller->review_count = $filteredReviews->count();
                    $seller->total_rating = $filteredReviews->sum('rating');
                });
        });

    }

    public function cacheHomePageJustForYouProductList()
    {
        return SaleCacheManager::normalizeClearanceState(Cache::remember(CACHE_FOR_HOME_PAGE_JUST_FOR_YOU_PRODUCT_LIST, CACHE_FOR_3_HOURS, function () {
            return Product::active()->with(['clearanceSale' => function ($query) {
                $query->activeIgnoringTimeWindow();
            },
                'reviews' => function ($query) {
                    $query->active();
                },
            ])->inRandomOrder()->take(8)->get()->each(function ($product) {
                $product->reviews_count = $product->reviews->count();
                $product->average_rating = $product->reviews->avg('rating');
                $product->total_rating = $product->reviews->sum('rating');
            });
        }));
    }

    public function cacheHomePageRandomSingleProductItem()
    {
        // Wrap in array so a null result is still cached — Cache::remember re-runs the
        // closure forever when it resolves to null, re-firing this ORDER BY RAND() every call.
        $product = Cache::remember(CACHE_FOR_RANDOM_SINGLE_PRODUCT, now()->addMinutes(10), function () {
            return [$this->product->active()->with(['clearanceSale' => function ($query) {
                return $query->activeIgnoringTimeWindow();
            }])->where('discount', '>', 0)->inRandomOrder()->first()];
        })[0] ?? null;

        return SaleCacheManager::normalizeClearanceState($product);
    }

    public function cacheMostDemandedProductItem()
    {
        return Cache::remember(CACHE_FOR_MOST_DEMANDED_PRODUCT_ITEM, CACHE_FOR_3_HOURS, function () {
            return MostDemanded::where('status', 1)->with(['product' => function ($query) {
                $query->withCount('wishList', 'orderDetails', 'orderDelivered', 'reviews');
            }])->whereHas('product', function ($query) {
                return $query->active();
            })->first();
        });
    }

    public function cacheTopRatedProductList()
    {
        return SaleCacheManager::normalizeClearanceState(Cache::remember(CACHE_FOR_HOME_PAGE_TOP_RATED_PRODUCT_LIST, CACHE_FOR_3_HOURS, function () {
            return Product::active()->with(['reviews', 'seller.shop', 'clearanceSale' => function ($query) {
                return $query->activeIgnoringTimeWindow();
            }])
                ->whereHas('reviews')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->paginate(10);
        }));
    }

    public function cacheBestSellProductList()
    {
        return SaleCacheManager::normalizeClearanceState(Cache::remember(CACHE_FOR_HOME_PAGE_BEST_SELL_PRODUCT_LIST, CACHE_FOR_3_HOURS, function () {
            return Product::active()
                ->with(['reviews', 'seller.shop', 'clearanceSale' => function ($query) {
                    return $query->activeIgnoringTimeWindow();
                }])
                ->whereHas('orderDetails')
                ->withCount('orderDetails')
                ->orderByDesc('order_details_count')
                ->paginate(10);
        }));
    }

    public function cacheHomePageLatestProductList()
    {
        $latestProductsList = Cache::remember(CACHE_FOR_HOME_PAGE_LATEST_PRODUCT_LIST, CACHE_FOR_3_HOURS, function () {
            return Product::active()->with(['reviews', 'seller.shop', 'flashDealProducts.flashDeal', 'clearanceSale' => function ($query) {
                return $query->activeIgnoringTimeWindow();
            }])->orderBy('id', 'desc')->take(10)->get();
        });
        SaleCacheManager::normalizeClearanceState($latestProductsList);

        return $this->getUpdateLatestProductWithFlashDeal(latestProducts: $latestProductsList);
    }

    public function cacheBannerAllTypeKeys($cacheKey): void
    {
        $cacheKeys = Cache::get(CACHE_BANNER_ALL_CACHE_KEYS, []);
        if (! in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put(CACHE_BANNER_ALL_CACHE_KEYS, $cacheKeys, CACHE_FOR_3_HOURS);
        }
    }

    public function cacheBannerForTypeMainBanner()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_main_banner_'.($themeName).'_'.BusinessModule::RETAIL;
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::with(['storage'])->where(['banner_type' => 'Main Banner', 'published' => 1, 'theme' => $themeName, 'business_module' => BusinessModule::RETAIL])->latest()->get();
        });
    }

    public function cacheBannerForTypeSidebarBanner()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_sidebar_banner_'.($themeName).'_'.BusinessModule::RETAIL;
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Sidebar Banner', 'published' => 1, 'theme' => $themeName, 'business_module' => BusinessModule::RETAIL])->latest()->first();
        });
    }

    public function cacheBannerForTypeTopSideBanner()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_top_side_banner_'.($themeName).'_'.BusinessModule::RETAIL;
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Top Side Banner', 'published' => 1, 'theme' => $themeName, 'business_module' => BusinessModule::RETAIL])->orderBy('id', 'desc')->latest()->first();
        });
    }

    public function cacheBannerForTypePromoBannerLeft()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_top_side_banner_'.($themeName).'_'.BusinessModule::RETAIL;
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Promo Banner Left', 'published' => 1, 'theme' => $themeName, 'business_module' => BusinessModule::RETAIL])->first();
        });
    }

    public function cacheBannerForTypePromoBanner($bannerType)
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_'.strtolower(str_replace(' ', '_', $bannerType)).'_'.($themeName).'_'.BusinessModule::RETAIL;
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName, $bannerType) {
            return Banner::where(['banner_type' => $bannerType, 'published' => 1, 'theme' => $themeName, 'business_module' => BusinessModule::RETAIL])->first();
        });
    }

    public function cacheInHouseShopInTemporaryStatus(): void
    {
        Cache::forget(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS);
        $inHouseShopInTemporaryClose = Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS);
        if ($inHouseShopInTemporaryClose === null) {
            $inHouseShopInTemporaryClose = getWebConfig(name: 'temporary_close');
            $inHouseShopInTemporaryClose = $inHouseShopInTemporaryClose['status'] ?? 0;
            Cache::put(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS, $inHouseShopInTemporaryClose, (60 * 24));
        }
    }

    public function cacheActiveAnalyticScript()
    {
        return Cache::remember(CACHE_FOR_ANALYTIC_SCRIPT_ACTIVE_LIST, CACHE_FOR_3_HOURS, function () {
            return AnalyticScript::where(['is_active' => 1])->get();
        });
    }

    private function getUpdateLatestProductWithFlashDeal($latestProducts)
    {
        return $latestProducts?->map(function ($product) {
            $flashDealStatus = 0;
            $flashDealEndDate = 0;
            if (count($product->flashDealProducts) > 0) {
                $flashDeal = $product->flashDealProducts[0]->flashDeal;
                if ($flashDeal) {
                    $flashDealStatus = $flashDeal->isCurrentlyActive() ? 1 : 0;
                    $flashDealEndDate = $flashDeal->end_date;
                }
            }
            $product['flash_deal_status'] = $flashDealStatus;
            $product['flash_deal_end_date'] = $flashDealEndDate;

            return $product;
        });
    }

    public function cacheBusinessPagesTable()
    {
        return Cache::remember(CACHE_FOR_BUSINESS_PAGES_LIST, CACHE_FOR_3_HOURS, function () {
            return BusinessPage::where('status', 1)->with(['banner'])->get();
        });
    }

    public function checkCustomerSocialMediaLoginAbility(): array
    {
        $configStatus = [
            'google' => 0,
            'facebook' => 0,
            'apple' => 0,
        ];
        foreach (getWebConfig(name: 'social_login') as $singleItem) {
            if (isset($singleItem['client_id']) && $singleItem['client_id'] && isset($singleItem['client_secret']) && $singleItem['client_secret']) {
                $configStatus[$singleItem['login_medium']] = 1;
            }
        }
        foreach (getWebConfig(name: 'apple_login') as $singleItem) {
            if (
                isset($singleItem['client_id']) && $singleItem['client_id'] &&
                isset($singleItem['team_id']) && $singleItem['team_id'] &&
                isset($singleItem['key_id']) && $singleItem['key_id'] &&
                isset($singleItem['service_file']) && $singleItem['service_file']
            ) {
                $configStatus[$singleItem['login_medium']] = 1;
            }
        }

        return $configStatus;
    }
}
