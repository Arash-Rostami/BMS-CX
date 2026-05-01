<?php

namespace Tests\Feature;

use App\Services\InterestCalculationService;
use Tests\TestCase;

class InterestCalculationServiceTest extends TestCase
{
    public function test_perfect_30_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, 0, 30, config('financial.interest_tiers'));

        $this->assertEquals(150.0, $interest);
    }

    public function test_bucket_crosser_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, 0, 35, config('financial.interest_tiers'));

        $this->assertEquals(183.33333333333334, $interest);
    }

    public function test_prepayment_negative_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, 0, -5, config('financial.interest_tiers'));

        $this->assertEquals(-25.0, $interest);
    }

    public function test_later_bucket_interest()
    {
        $service = new InterestCalculationService();
        $interest = $service->calculateInterest(10000, 35, 60, config('financial.interest_tiers'));

        $this->assertEquals(166.66666666666666, $interest);
    }

    public function test_multi_bucket_spanning_interest()
    {
         $service = new InterestCalculationService();
         $interest = $service->calculateInterest(10000, 25, 65, config('financial.interest_tiers'));

         $this->assertEquals(266.6666666666667, $interest);
    }
}
