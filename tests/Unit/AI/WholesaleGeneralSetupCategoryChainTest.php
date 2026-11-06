<?php

namespace Tests\Unit\AI;

use Modules\AI\app\Response\WholesaleAIResponse;
use Modules\AI\app\Services\Wholesale\WholesaleResourceService;
use PHPUnit\Framework\TestCase;

class WholesaleGeneralSetupCategoryChainTest extends TestCase
{
    private function response(): WholesaleAIResponse
    {
        return new WholesaleAIResponse(new class extends WholesaleResourceService
        {
            public function __construct() {}

            public function generalSetupData(): array
            {
                return [
                    'categories' => ['c2' => 428, 'men’s fashion' => 431, 'electronics' => 433],
                    'sub_categories' => [
                        428 => ['sub cat 2' => 429, 'sub cat 3' => 438],
                        431 => ['men’s clothing' => 434, 'sub cat 2' => 441],
                    ],
                    'sub_sub_categories' => [
                        429 => ['sub sub 3' => 430, 'sub sub 4' => 439],
                        434 => ['t-shirts' => 436],
                    ],
                    'brands' => ['apple' => 7],
                    'units' => ['pc', 'kg'],
                ];
            }
        });
    }

    private function generalSetup(array $payload): array
    {
        return $this->response()->generalSetup(json_encode($payload));
    }

    public function test_sub_category_outside_the_chosen_category_is_dropped(): void
    {
        $data = $this->generalSetup([
            'category_name' => 'electronics',
            'sub_category_name' => 'sub cat 3',
            'sub_sub_category_name' => '',
            'unit_name' => 'pc',
        ]);

        $this->assertSame(433, $data['category_id']);
        $this->assertNull($data['sub_category_name']);
        $this->assertNull($data['sub_category_id']);
        $this->assertNull($data['sub_sub_category_name']);
        $this->assertNull($data['sub_sub_category_id']);
    }

    public function test_matching_chain_resolves_every_level(): void
    {
        $data = $this->generalSetup([
            'category_name' => 'C2',
            'sub_category_name' => 'Sub Cat 2',
            'sub_sub_category_name' => 'Sub Sub 4',
            'unit_name' => 'pc',
        ]);

        $this->assertSame(428, $data['category_id']);
        $this->assertSame(429, $data['sub_category_id']);
        $this->assertSame(439, $data['sub_sub_category_id']);
    }

    public function test_duplicate_child_name_resolves_within_the_chosen_category(): void
    {
        $data = $this->generalSetup([
            'category_name' => 'men’s fashion',
            'sub_category_name' => 'sub cat 2',
            'unit_name' => 'pc',
        ]);

        $this->assertSame(441, $data['sub_category_id']);
    }

    public function test_sub_sub_category_outside_the_chosen_sub_category_is_dropped(): void
    {
        $data = $this->generalSetup([
            'category_name' => 'c2',
            'sub_category_name' => 'sub cat 2',
            'sub_sub_category_name' => 't-shirts',
            'unit_name' => 'pc',
        ]);

        $this->assertSame(429, $data['sub_category_id']);
        $this->assertNull($data['sub_sub_category_name']);
        $this->assertNull($data['sub_sub_category_id']);
    }

    public function test_dropped_sub_category_drops_the_sub_sub_category_with_it(): void
    {
        $data = $this->generalSetup([
            'category_name' => 'electronics',
            'sub_category_name' => 'men’s clothing',
            'sub_sub_category_name' => 't-shirts',
            'unit_name' => 'pc',
        ]);

        $this->assertNull($data['sub_category_id']);
        $this->assertNull($data['sub_sub_category_id']);
    }

    public function test_unknown_brand_is_dropped(): void
    {
        $data = $this->generalSetup([
            'category_name' => 'electronics',
            'brand_name' => 'invented brand',
            'unit_name' => 'pc',
        ]);

        $this->assertNull($data['brand_name']);
        $this->assertNull($data['brand_id']);
    }
}
