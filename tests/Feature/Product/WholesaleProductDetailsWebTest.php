<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use App\Models\Wishlist;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Wholesale\app\Services\Product\FaqService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductDetailsWebTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const VIEW_CONTRACT = [
        'product',
        'gallery',
        'ratingSummary',
        'soldQuantity',
        'purchaseOptions',
        'initialQuote',
        'purchaseUrls',
        'faqs',
        'reviews',
        'reviewsUrl',
        'video',
        'shop',
        'trustPolicyCards',
        'isWishlisted',
        'sellerProducts',
        'recommendedProducts',
        'similarProducts',
        'wholesalePriceRanges',
    ];

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-details-views';

    private const DETAILS_VIEW_STUB = '{{ $product->id }}';

    private array $stubbedViewPaths = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        foreach ($this->stubbedViewPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_unknown_or_inactive_slug_returns_404(): void
    {
        $this->get(route('wholesale.products.details', ['slug' => 'missing-wholesale-product-'.random_int(10000, 99999)]))->assertNotFound();

        $inactive = $this->createProduct();
        $this->service()->updateStatus($inactive->id, false);
        $this->get(route('wholesale.products.details', ['slug' => $inactive->slug]))->assertNotFound();

        $unapproved = $this->createProduct();
        $unapproved->forceFill(['request_status' => ProductWholesaleService::REQUEST_STATUS_PENDING])->save();
        $this->get(route('wholesale.products.details', ['slug' => $unapproved->slug]))->assertNotFound();

        $this->postJson(route('wholesale.products.price-quote', ['id' => $unapproved->id]), ['quantity' => 10])->assertNotFound();
        $this->getJson(route('wholesale.products.reviews', ['id' => $inactive->id]))->assertNotFound();
    }

    public function test_details_view_receives_the_full_contract(): void
    {
        $product = $this->createProduct();
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);

        $response = $this->get(route('wholesale.products.details', ['slug' => $product->slug]))
            ->assertOk()
            ->assertViewHasAll(self::VIEW_CONTRACT);

        $this->assertSame($product->id, $response->viewData('product')->id);
        $this->assertSame([5, 4, 3, 2, 1], array_keys($response->viewData('ratingSummary')['star_counts']));
        $this->assertSame(route('wholesale.products.price-quote', ['id' => $product->id]), $response->viewData('purchaseUrls')['quote']);
        $this->assertSame(route('wholesale.products.reviews', ['id' => $product->id]), $response->viewData('reviewsUrl'));
        $this->assertNotNull($response->viewData('shop'));

        $options = $response->viewData('purchaseOptions');
        $tier = $options['price_rows'][0]['tiers'][0];
        $this->assertSame('10 - 49 pc', $tier['range_label']);
        $this->assertSame(webCurrencyConverter(amount: 50.0), $tier['unit_price_label']);

        $initialQuote = $response->viewData('initialQuote');
        $serviceQuote = $this->service()->getPriceQuote($this->reload($product), [], 10);
        $this->assertSame($serviceQuote['total'], $initialQuote['total']);
        $this->assertSame(webCurrencyConverter(amount: $serviceQuote['total']), $initialQuote['total_label']);
        $this->assertTrue($initialQuote['is_purchasable']);
    }

    public function test_details_view_drops_the_unused_most_in_demand_list(): void
    {
        $product = $this->createProduct();
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);

        $this->get(route('wholesale.products.details', ['slug' => $product->slug]))
            ->assertOk()
            ->assertViewMissing('mostInDemandProducts');
    }

    public function test_guest_details_view_is_not_wishlisted(): void
    {
        $product = $this->createProduct();
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);

        $this->assertFalse(
            $this->get(route('wholesale.products.details', ['slug' => $product->slug]))->assertOk()->viewData('isWishlisted'),
        );
    }

    public function test_customer_holding_the_wishlist_row_is_flagged(): void
    {
        $this->requireFixture(Schema::hasTable('product_recent_views') ?: null, 'product_recent_views table');

        $product = $this->createProduct();
        $customer = $this->customer();
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);
        $url = route('wholesale.products.details', ['slug' => $product->slug]);

        $this->assertFalse($this->actingAs($customer, 'customer')->get($url)->assertOk()->viewData('isWishlisted'));

        Wishlist::query()->forceCreate(['customer_id' => $customer->id, 'product_id' => $product->id]);

        $this->assertTrue($this->actingAs($customer, 'customer')->get($url)->assertOk()->viewData('isWishlisted'));
    }

    public function test_reviews_carry_the_eager_loaded_vendor_reply(): void
    {
        $product = $this->createProduct();
        $review = Review::query()->forceCreate([
            'product_id' => $product->id,
            'customer_id' => $this->customer()->id,
            'comment' => 'Bulk order arrived on time',
            'rating' => 5,
            'status' => 1,
        ]);
        ReviewReply::query()->forceCreate([
            'review_id' => $review->id,
            'added_by' => 'seller',
            'reply_text' => 'Thank you for ordering with us',
        ]);
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);
        $this->stubThemeView(view: 'products.partials.product-details.review-items', contents: '{{ $reviews->first()?->reply?->reply_text }}');

        $pageReview = $this->get(route('wholesale.products.details', ['slug' => $product->slug]))
            ->assertOk()
            ->viewData('reviews')
            ->first();

        $this->assertTrue($pageReview->relationLoaded('reply'));
        $this->assertSame('Thank you for ordering with us', $pageReview->reply->reply_text);

        $endpoint = $this->getJson(route('wholesale.products.reviews', ['id' => $product->id]))->assertOk();
        $this->assertSame('Thank you for ordering with us', trim($endpoint->json('html')));
    }

    public function test_seller_similar_and_recommended_lists_exclude_the_current_product(): void
    {
        $product = $this->createProduct();
        $sibling = $this->createProduct();
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);

        $response = $this->get(route('wholesale.products.details', ['slug' => $product->slug]))->assertOk();

        foreach (['sellerProducts', 'similarProducts', 'recommendedProducts'] as $list) {
            $ids = $response->viewData($list)->pluck('id')->all();

            $this->assertNotContains($product->id, $ids, $list.' contains the current product');
            $this->assertContains($sibling->id, $ids, $list.' misses the sibling product');
        }

        $this->assertArrayHasKey($sibling->id, $response->viewData('wholesalePriceRanges'));
    }

    public function test_price_quote_endpoint_returns_formatted_quote(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson(route('wholesale.products.price-quote', ['id' => $product->id]), ['quantity' => 60])->assertOk();

        $serviceQuote = $this->service()->getPriceQuote($this->reload($product), [], 60);

        $response->assertJson([
            'quantity' => 60,
            'tier_index' => 1,
            'total' => $serviceQuote['total'],
            'total_label' => webCurrencyConverter(amount: $serviceQuote['total']),
            'unit_price_label' => webCurrencyConverter(amount: 40.0),
            'reason' => null,
            'message' => null,
            'is_purchasable' => true,
        ]);

        foreach (['discount_per_unit', 'discounted_unit_price', 'subtotal', 'discount_amount', 'tax_amount'] as $key) {
            $response->assertJsonPath($key.'_label', webCurrencyConverter(amount: $serviceQuote[$key]));
        }
    }

    public function test_price_quote_endpoint_returns_a_translated_reason(): void
    {
        $product = $this->createProduct();

        $this->postJson(route('wholesale.products.price-quote', ['id' => $product->id]), ['quantity' => 5])
            ->assertOk()
            ->assertJson([
                'reason' => ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER,
                'message' => 'Minimum order quantity is 10',
                'is_purchasable' => false,
            ]);
    }

    public function test_price_quote_endpoint_validates_quantity(): void
    {
        $product = $this->createProduct();
        $url = route('wholesale.products.price-quote', ['id' => $product->id]);

        foreach ([[], ['quantity' => 0], ['quantity' => 'many']] as $payload) {
            $this->postJson($url, $payload)
                ->assertUnprocessable()
                ->assertJsonPath('errors.0.error_code', 'quantity');
        }
    }

    public function test_inactive_faqs_are_not_passed_to_the_view(): void
    {
        $product = $this->createProduct();

        app(FaqService::class)->replaceProductFaqs($product->id, [
            ['question' => 'Visible question', 'answer' => 'Visible answer', 'status' => 1],
            ['question' => 'Hidden question', 'answer' => 'Hidden answer', 'status' => 0],
        ]);
        $this->stubThemeView(view: 'products.product-details', contents: self::DETAILS_VIEW_STUB);

        $faqs = $this->get(route('wholesale.products.details', ['slug' => $product->slug]))
            ->assertOk()
            ->viewData('faqs');

        $this->assertSame(['Visible question'], $faqs->pluck('question')->all());
    }

    public function test_reviews_endpoint_paginates_active_reviews_of_the_product(): void
    {
        $product = $this->createProduct();
        $customerId = (int) $this->requireFixture(User::query()->where('id', '>', 0)->value('id'), 'customer');

        foreach (range(1, 6) as $rating) {
            Review::query()->forceCreate([
                'product_id' => $product->id,
                'customer_id' => $customerId,
                'comment' => 'Review '.$rating,
                'rating' => min($rating, 5),
                'status' => 1,
            ]);
        }

        Review::query()->forceCreate([
            'product_id' => $product->id,
            'customer_id' => $customerId,
            'comment' => 'Hidden review',
            'rating' => 1,
            'status' => 0,
        ]);

        $this->stubThemeView(view: 'products.partials.product-details.review-items', contents: '{{ $reviews->count() }}');
        $url = route('wholesale.products.reviews', ['id' => $product->id]);

        $firstPage = $this->getJson($url)->assertOk();
        $this->assertSame('5', trim($firstPage->json('html')));
        $this->assertStringContainsString('page=2', (string) $firstPage->json('next_page_url'));

        $secondPage = $this->getJson($url.'?page=2')->assertOk();
        $this->assertSame('1', trim($secondPage->json('html')));
        $this->assertNull($secondPage->json('next_page_url'));
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->first(), 'customer');
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function createProduct(): Model
    {
        $product = $this->service()->create(data: [
            'lang' => ['en'],
            'name' => ['Wholesale details fixture'],
            'description' => ['Wholesale details fixture'],
            'code' => 'WSDTL'.random_int(10000, 99999),
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'shipping_cost' => 0,
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
            'quantity_based_price' => 1,
            'tier_min_qty' => [10, 50],
            'tier_max_qty' => ['49', ''],
            'tier_unit_price' => [50, 40],
        ]);

        $this->service()->updateStatus($product->id, true);

        return $this->reload($product);
    }

    private function reload(Model $product): Model
    {
        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function stubThemeView(string $view, string $contents): void
    {
        $path = self::STUB_VIEW_ROOT.'/themes/'.theme_root_path().'/'.str_replace('.', '/', $view).'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
        $this->stubbedViewPaths[] = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }
}
