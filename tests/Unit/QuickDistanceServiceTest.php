<?php

namespace Tests\Unit;

use App\Services\New\QuickDelivery\QuickDistanceService;
use PHPUnit\Framework\TestCase;

class QuickDistanceServiceTest extends TestCase
{
    public function test_same_point_is_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, (new QuickDistanceService())->kilometres(23.0, 90.0, 23.0, 90.0), 0.00001);
    }

    public function test_near_point_within_5km_and_far_point_outside(): void
    {
        $service = new QuickDistanceService();
        $this->assertLessThan(5.0, $service->kilometres(23.0, 90.0, 23.01, 90.0));
        $this->assertGreaterThan(5.0, $service->kilometres(23.0, 90.0, 23.10, 90.0));
    }
}
