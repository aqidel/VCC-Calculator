<?php

declare(strict_types=1);

namespace Tests;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleAgeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;
use Aqidel\VCCCalculator\VCCCalculator;
use PHPUnit\Framework\TestCase;

final class PersonalUsageTest extends TestCase
{
    private const OWNER_TYPE = VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE;
    private VCCCalculator $calculator;

    public function setUp(): void
    {
        $this->calculator = new VCCCalculator(
            EnginePowerUnitOfMeasurementEnum::HORSEPOWER,
            103.3773,
        );
    }

    public function testLessThanThreeYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::LESS_THAN_3,
            EngineTypeEnum::GASOLINE,
            120,
            2000,
            700000.00,
        );

        $this->assertEquals(523386.50, $result);
    }

    public function testFromThreeToFiveYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_3_TO_5,
            EngineTypeEnum::GASOLINE,
            200,
            4000,
            1000000.00,
        );

        $this->assertEquals(3115533.12, $result);
    }

    public function testFromFiveToSevenYearsDiesel(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_5_TO_7,
            EngineTypeEnum::DIESEL,
            160,
            3000,
            600000.00,
        );

        $this->assertEquals(1558959.50, $result);
    }

    public function testOlderThanSevenYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::MORE_THAN_7,
            EngineTypeEnum::GASOLINE,
            80,
            1600,
            80000.00,
        );

        $this->assertEquals(584887.88, $result);
    }

    public function testLessThanThreeYearsElectric(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::LESS_THAN_3,
            EngineTypeEnum::ELECTRIC,
            360,
            0,
            1200000.00,
        );

        $this->assertEquals(1139690.00, $result);
    }
}
