<?php

namespace Tests\Feature;

use App\Services\InterestCalculationService;
use Tests\TestCase;

class InterestCalculationServiceTest extends TestCase
{
    public function test_perfect_30_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, 30, config('financial.interest_tiers'));

        $this->assertEquals(150.0, $interest);
    }

    public function test_bucket_crosser_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, 35, config('financial.interest_tiers'));

        // 30 days at 1.5% = 150
        // 5 days at 2.0% = 5 * 10000 * 0.02 / 30 = 33.33333
        // Total = 183.33333

        $this->assertEquals(183.33333333333334, $interest);
    }

    public function test_prepayment_negative_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, -5, config('financial.interest_tiers'));

        // -5 days at 1.5%
        // -5 * 10000 * 0.015 / 30 = -25

        $this->assertEquals(-25.0, $interest);
    }
}
