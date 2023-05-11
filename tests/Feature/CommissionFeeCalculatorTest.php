<?php

namespace Tests\Feature;

use App\Services\CommissionFeeCalculateService;
use Tests\TestCase;

class CommissionFeeCalculatorTest extends TestCase
{
    /**
     * A basic feature test example.
     * @throws \Exception
     */
    public function test_commission(): void
    {
        $filePath = storage_path("input-example.csv");

        $result = (new CommissionFeeCalculateService($filePath))->calculate();

        $this->assertEquals([
            0.6,
            3,
            0,
            0.06,
            1.5,
            0,
            0.69,
            0.3,
            0.3,
            3,
            0,
            0,
            8607.4,
        ], $result);
    }
}