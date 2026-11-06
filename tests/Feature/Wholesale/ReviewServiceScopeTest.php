<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\ReviewService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class ReviewServiceScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    private int $nextOrderId = 0;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Storage::fake('public');
        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_wholesale_review_image_upload_and_delete_actually_persist_and_remove_it(): void
    {
        $customer = $this->customer();
        $product = $this->createProduct(code: 'RVWSC1');
        $order = $this->createOrder(customer: $customer);
        $this->createDetail(order: $order, product: $product);

        $this->actingAs($customer, 'customer')->post(route('wholesale.review.store'), [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Great wholesale batch',
            'fileUpload' => [UploadedFile::fake()->image('proof.jpg')],
        ])->assertRedirect();

        $review = $this->wholesaleReviewService()->findBy(params: ['order_id' => $order->id, 'product_id' => $product->id]);
        $this->assertNotNull($review, 'the review must be readable through the wholesale-scoped service');
        $this->assertCount(1, $review->attachment);

        $fileName = $review->attachment[0]['file_name'];
        Storage::disk('public')->assertExists(ReviewService::IMAGE_DIRECTORY.$fileName);

        $this->actingAs($customer, 'customer')
            ->delete(route('wholesale.review.image.destroy'), ['id' => $review->id, 'name' => $fileName])
            ->assertOk();

        $review->refresh();
        $this->assertSame([], $review->attachment);
        Storage::disk('public')->assertMissing(ReviewService::IMAGE_DIRECTORY.$fileName);
    }

    public function test_two_reviews_on_the_same_wholesale_order_do_not_collide_on_the_primary_key(): void
    {
        $customer = $this->customer();
        $firstProduct = $this->createProduct(code: 'RVWSC2A');
        $secondProduct = $this->createProduct(code: 'RVWSC2B');
        $order = $this->createOrder(customer: $customer);
        $this->createDetail(order: $order, product: $firstProduct);
        $this->createDetail(order: $order, product: $secondProduct);

        $this->actingAs($customer, 'customer')->post(route('wholesale.review.store'), [
            'product_id' => $firstProduct->id,
            'order_id' => $order->id,
            'rating' => 4,
            'comment' => 'First item review',
        ])->assertRedirect();

        $this->actingAs($customer, 'customer')->post(route('wholesale.review.store'), [
            'product_id' => $secondProduct->id,
            'order_id' => $order->id,
            'rating' => 3,
            'comment' => 'Second item review',
        ])->assertRedirect();

        $reviewIds = Review::query()->withoutGlobalScope('active')->where('order_id', $order->id)->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        $this->assertCount(2, $reviewIds);
        $this->assertSame(array_unique($reviewIds), $reviewIds);
        $this->assertSame([$order->id.'01', $order->id.'02'], $reviewIds);
    }

    private function wholesaleReviewService(): ReviewService
    {
        return app(ReviewService::class);
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->orderBy('id')->first(), 'customer');
    }

    private function createProduct(string $code): Product
    {
        $template = $this->requireFixture(Product::query()->withoutGlobalScopes()->orderBy('id')->first(), 'product');
        $product = $template->replicate();
        $product->forceFill([
            'business_module' => BusinessModule::WHOLESALE,
            'status' => 1,
            'slug' => 'review-scope-'.Str::lower(Str::random(12)),
            'code' => $code.random_int(1000, 9999),
        ])->save();

        return $product;
    }

    private function createOrder(User $customer): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => BusinessModule::WHOLESALE,
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'seller_id' => self::IN_HOUSE_SELLER_ID,
            'seller_is' => 'admin',
            'order_type' => 'default_type',
            'order_group_id' => 'review-scope-'.Str::lower(Str::random(16)),
            'order_status' => 'delivered',
            'created_at' => now(),
        ])->save();

        return $order;
    }

    private function createDetail(Order $order, Product $product): OrderDetail
    {
        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $product->id, 'name' => $product->name ?? 'Review scope fixture', 'product_type' => 'physical']),
            'qty' => 1,
            'price' => 10,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'variant' => null,
            'variation' => [],
            'delivery_status' => 'delivered',
            'payment_status' => 'paid',
            'discount_type' => 'discount_on_product',
        ])->save();

        return $detail;
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
    }
}
