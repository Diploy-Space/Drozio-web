<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\Product\FaqService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\ReviewService;
use Modules\Wholesale\app\Services\ShopService;
use Tests\Feature\Api\Support\ApiTestData;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductDetailsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const QUOTE_KEYS = [
        'variant_key', 'variation_value', 'quantity', 'minimum_quantity', 'maximum_quantity', 'available_stock', 'tier_index',
        'unit_price', 'discount_per_unit', 'discounted_unit_price', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'tax_model', 'reason',
    ];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_details_answer_the_v6_envelope_with_purchase_options(): void
    {
        $product = $this->createProduct([
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            ...$this->quantityTiers([[10, 49, 50], [50, 99, 40], [100, null, 30]]),
        ]);
        app(FaqService::class)->replaceProductFaqs($product->id, [
            ['lang' => ['en'], 'question' => ['Active question'], 'answer' => ['Active answer'], 'status' => 1],
            ['lang' => ['en'], 'question' => ['Inactive question'], 'answer' => ['Inactive answer'], 'status' => 0],
        ]);

        $response = $this->getJson('api/v6/wholesale/products/'.$product->slug);

        $response->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonStructure([
                'identical_code',
                'message',
                'content' => [
                    'data' => [
                        'id', 'name', 'slug', 'details', 'unit', 'minimum_order_qty', 'current_stock', 'thumbnail_full_url', 'images_full_url', 'video', 'tax_model',
                        'shop' => ['id', 'name', 'slug', 'image_full_url', 'is_verified', 'product_count', 'review_count', 'is_closed'],
                        'rating' => ['average', 'count'],
                        'sold_quantity',
                        'purchase_options' => [
                            'pricing_mode', 'price_axis', 'unit', 'minimum_order_qty', 'axes',
                            'price_rows' => ['*' => ['variation_value', 'unit_price', 'discount', 'tiers' => ['*' => ['min_qty', 'max_qty', 'unit_price', 'discount', 'discounted_unit_price']]]],
                            'discount_highlights' => ['kind', 'axis_key', 'items'],
                            'stock', 'images', 'default_selection',
                        ],
                        'default_quote' => [...self::QUOTE_KEYS, 'message', 'is_purchasable'],
                        'faqs' => ['*' => ['question', 'answer']],
                        'trust_policy' => ['*' => ['title', 'icon']],
                        'is_wishlisted',
                    ],
                ],
                'errors',
            ])
            ->assertJsonPath('content.data.id', $product->id)
            ->assertJsonPath('content.data.purchase_options.pricing_mode', ProductWholesaleService::PRICING_QUANTITY)
            ->assertJsonPath('content.data.purchase_options.minimum_order_qty', 10)
            ->assertJsonPath('content.data.default_quote.quantity', 10)
            ->assertJsonPath('content.data.is_wishlisted', false)
            ->assertJsonPath('content.data.shop.is_verified', fn (mixed $isVerified): bool => is_bool($isVerified))
            ->assertJsonPath('content.data.shop.product_count', fn (mixed $count): bool => is_int($count) && $count >= 1)
            ->assertJsonPath('content.data.shop.review_count', fn (mixed $count): bool => is_int($count))
            ->assertJsonPath('content.data.faqs', [['question' => 'Active question', 'answer' => 'Active answer']]);

        $this->assertQuoteEquals(
            $this->service()->getPriceQuote($product, [], 10),
            $response->json('content.data.default_quote'),
        );
    }

    public function test_details_return_404_for_unknown_inactive_or_unapproved_products(): void
    {
        $this->getJson('api/v6/wholesale/products/no-such-wholesale-product-slug')
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'product_not_found_404');

        $inactive = $this->createProduct($this->quantityTiers([[1, null, 100]]), ['status' => 0]);
        $unapproved = $this->createProduct($this->quantityTiers([[1, null, 100]]), ['request_status' => 0]);

        foreach ([$inactive, $unapproved] as $product) {
            $this->getJson('api/v6/wholesale/products/'.$product->slug)
                ->assertNotFound()
                ->assertJsonPath('identical_code', 'product_not_found_404');

            $this->postJson('api/v6/wholesale/products/'.$product->id.'/price-quote', ['quantity' => 1])
                ->assertNotFound()
                ->assertJsonPath('identical_code', 'product_not_found_404');

            foreach (['reviews', 'seller-products', 'similar'] as $list) {
                $this->getJson('api/v6/wholesale/products/'.$product->slug.'/'.$list)->assertNotFound();
            }
        }
    }

    public function test_retail_product_slug_is_not_found(): void
    {
        $retailProduct = $this->requireFixture(
            Product::query()->where('business_module', BusinessModule::RETAIL)->where('status', 1)->where('request_status', 1)->first(),
            'active retail product',
        );

        $this->getJson('api/v6/wholesale/products/'.$retailProduct->slug)
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'product_not_found_404');

        $this->postJson('api/v6/wholesale/products/'.$retailProduct->id.'/price-quote', ['quantity' => 1])
            ->assertNotFound();
    }

    public function test_price_quote_matches_the_service_quote(): void
    {
        $simple = $this->createProduct([
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            ...$this->quantityTiers([[10, 49, 50], [50, 99, 40], [100, null, 30]]),
        ]);
        $isInHouseShopClosed = app(ShopService::class)->isClosedForAuthor(ProductWholesaleService::ADDED_BY_ADMIN, getInHouseShopConfig());

        $response = $this->postJson('api/v6/wholesale/products/'.$simple->id.'/price-quote', ['quantity' => 60]);

        $response->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonPath('content.data.tier_index', 1)
            ->assertJsonPath('content.data.reason', null)
            ->assertJsonPath('content.data.is_purchasable', ! $isInHouseShopClosed);
        $this->assertQuoteEquals($this->service()->getPriceQuote($simple, [], 60), $response->json('content.data'));

        $this->postJson('api/v6/wholesale/products/'.$simple->id.'/price-quote', ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('content.data.reason', ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER)
            ->assertJsonPath('content.data.message', 'Minimum order quantity is 10')
            ->assertJsonPath('content.data.is_purchasable', false);

        $attributeId = $this->attributeId();
        $variationTiered = $this->createProduct([
            'choice_attributes' => [$attributeId],
            'choice_no' => [$attributeId],
            'choice' => ['Size'],
            'choice_options_'.$attributeId => ['S,M'],
            'qty_S' => 100,
            'qty_M' => 100,
            'variation_based_price' => 1,
            'variation_type' => (string) $attributeId,
            ...$this->quantityTiers([[1, 9, null], [10, null, null]]),
            'variation_tier_price' => ['S' => [100, 90], 'M' => [110, 95]],
            'variation_tier_discount' => ['M' => [1 => 10]],
        ]);
        $selection = ['choice_'.$attributeId => 'M'];

        $response = $this->postJson('api/v6/wholesale/products/'.$variationTiered->id.'/price-quote', ['quantity' => 20, 'variation' => $selection]);

        $response->assertOk()
            ->assertJsonPath('content.data.variant_key', 'M')
            ->assertJsonPath('content.data.tier_index', 1);
        $this->assertQuoteEquals($this->service()->getPriceQuote($variationTiered, $selection, 20), $response->json('content.data'));

        $details = $this->getJson('api/v6/wholesale/products/'.$variationTiered->slug)->assertOk();

        $details->assertJsonPath('content.data.purchase_options.pricing_mode', ProductWholesaleService::PRICING_VARIATION_QUANTITY)
            ->assertJsonPath('content.data.purchase_options.discount_highlights.kind', ProductWholesaleService::HIGHLIGHT_COMBINATION)
            ->assertJsonPath('content.data.purchase_options.discount_highlights.items.0.label', 'Size M (≥10)');
    }

    public function test_price_quote_validation_errors_use_the_v6_error_body(): void
    {
        $product = $this->createProduct($this->quantityTiers([[1, null, 100]]));

        foreach ([[], ['quantity' => 0], ['quantity' => 'many'], ['quantity' => 1, 'variation' => 'red']] as $payload) {
            $response = $this->postJson('api/v6/wholesale/products/'.$product->id.'/price-quote', $payload);

            $response->assertStatus(422)
                ->assertJsonPath('identical_code', 'unprocessable_entity_422')
                ->assertJsonPath('content', null)
                ->assertJsonStructure(['identical_code', 'message', 'content', 'errors' => ['*' => ['error_code', 'message']]]);
        }

        $this->postJson('api/v6/wholesale/products/'.$product->id.'/price-quote', ['quantity' => 0])
            ->assertJsonPath('errors.0.error_code', 'quantity')
            ->assertJsonPath('errors.0.message', 'The quantity must be at least 1.');
    }

    public function test_reviews_seller_products_and_similar_paginate_and_exclude_the_product(): void
    {
        $tiers = $this->quantityTiers([[1, null, 100]]);
        $product = $this->createProduct($tiers);
        $sibling = $this->createProduct($tiers);
        $secondSibling = $this->createProduct($tiers);
        $customer = $this->requireFixture(ApiTestData::customer(), 'customer');

        foreach ([5, 4] as $rating) {
            app(ReviewService::class)->create([
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'comment' => 'Wholesale review fixture '.$rating,
                'rating' => $rating,
                'status' => 1,
            ]);
        }

        $this->getJson('api/v6/wholesale/products/'.$product->slug.'/reviews?limit=1&offset=1')
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonCount(1, 'content.data')
            ->assertJsonPath('content.params', ['limit' => 1, 'offset' => 1])
            ->assertJsonPath('content.pagination.per_page', 1)
            ->assertJsonPath('content.pagination.total', 2)
            ->assertJsonStructure(['content' => ['data' => ['*' => ['id', 'product_id', 'comment', 'rating', 'customer']]]]);

        $this->getJson('api/v6/wholesale/products/'.$sibling->slug.'/reviews')
            ->assertOk()
            ->assertJsonPath('content.pagination.total', 0);

        foreach (['seller-products', 'similar'] as $list) {
            $response = $this->getJson('api/v6/wholesale/products/'.$product->slug.'/'.$list.'?limit=2&offset=1');

            $response->assertOk()
                ->assertJsonPath('identical_code', 'data_fetched_200')
                ->assertJsonCount(2, 'content.data')
                ->assertJsonPath('content.pagination.per_page', 2)
                ->assertJsonStructure(['content' => [
                    'data' => ['*' => ['id', 'name', 'slug', 'thumbnail', 'thumbnail_full_url', 'price_range' => ['min', 'max'], 'minimum_order_qty', 'current_stock', 'discount', 'discount_type', 'unit']],
                    'params',
                    'pagination',
                ]]);

            $ids = collect($response->json('content.data'))->pluck('id');

            $this->assertEqualsCanonicalizing([$secondSibling->id, $sibling->id], $ids->all(), $list);
            $this->assertFalse($ids->contains($product->id), $list);
            $this->assertEquals(['min' => 100, 'max' => 100], $response->json('content.data.0.price_range'));
        }
    }

    private function assertQuoteEquals(array $expected, array $actual): void
    {
        foreach (self::QUOTE_KEYS as $key) {
            $this->assertEquals($expected[$key], $actual[$key], $key);
        }
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function createProduct(array $overrides, array $state = []): Model
    {
        $product = $this->service()->create(data: [...$this->basePayload(), ...$overrides]);

        $product->forceFill(['status' => 1, 'request_status' => 1, ...$state])->save();

        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'taxVats.tax']);
    }

    private function basePayload(): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Wholesale details fixture'],
            'description' => ['Wholesale details fixture'],
            'code' => 'WSDTL'.random_int(10000, 99999),
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'shipping_cost' => 0,
            'minimum_order_qty' => 1,
            'current_stock' => 100,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
        ];
    }

    private function quantityTiers(array $tiers): array
    {
        return [
            'quantity_based_price' => 1,
            'tier_min_qty' => array_column($tiers, 0),
            'tier_max_qty' => array_map(static fn (?int $maximum): string => $maximum === null ? '' : (string) $maximum, array_column($tiers, 1)),
            'tier_unit_price' => array_column($tiers, 2),
        ];
    }

    private function attributeId(): int
    {
        $ids = app(AttributeService::class)->getIds();

        if ($ids === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        return (int) $ids[0];
    }

    private function categoryId(): int
    {
        static $categoryId = null;

        return $categoryId ??= (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }
}
