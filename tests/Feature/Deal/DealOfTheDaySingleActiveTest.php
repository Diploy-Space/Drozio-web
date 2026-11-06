<?php

namespace Tests\Feature\Deal;

use App\Models\Product;
use App\Services\New\Deal\DealOfTheDayService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class DealOfTheDaySingleActiveTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private DealOfTheDayService $service;

    private Product $product;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        $this->service = app(DealOfTheDayService::class);
        $this->product = $this->requireFixture(Product::query()->where('status', 1)->orderBy('id')->first(), 'active product');
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_new_deal_is_created_switched_off(): void
    {
        $running = $this->createDeal('Running deal');
        $this->service->updateStatus(id: $running->id, status: true);

        $added = $this->createDeal('Added deal');

        $this->assertSame(0, (int) $added->refresh()->status);
        $this->assertSame(1, (int) $running->refresh()->status);
    }

    public function test_turning_a_deal_on_switches_every_other_deal_off(): void
    {
        $first = $this->createDeal('First deal');
        $second = $this->createDeal('Second deal');

        $this->service->updateStatus(id: $first->id, status: true);
        $this->service->updateStatus(id: $second->id, status: true);

        $this->assertSame(0, (int) $first->refresh()->status);
        $this->assertSame(1, (int) $second->refresh()->status);
    }

    public function test_turning_the_active_deal_off_leaves_the_others_untouched(): void
    {
        $inactive = $this->createDeal('Inactive deal');
        $active = $this->createDeal('Active deal');

        $this->service->updateStatus(id: $active->id, status: true);
        $this->service->updateStatus(id: $active->id, status: false);

        $this->assertSame(0, (int) $active->refresh()->status);
        $this->assertSame(0, (int) $inactive->refresh()->status);
    }

    private function createDeal(string $title): Model
    {
        return $this->service->create(
            data: ['title' => [$title], 'lang' => ['en']],
            product: $this->product,
        );
    }
}
