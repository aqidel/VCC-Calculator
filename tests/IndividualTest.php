<?php

declare(strict_types=1);

namespace Tests;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleAgeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;
use Aqidel\VCCCalculator\VCCCalculator;
use PHPUnit\Framework\TestCase;

final class IndividualTest extends TestCase
{
    private const OWNER_TYPE = VehicleOwnerTypeEnum::INDIVIDUAL;

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
            200,
            3000,
            1000000.00,
        );

        $this->assertEquals(1933361.65, $result);
    }

    public function testFromThreeToFiveYearsDiesel(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_3_TO_5,
            EngineTypeEnum::DIESEL,
            350,
            4200,
            1100000.00,
        );

        $this->assertEquals(3189964.78, $result);
    }

    public function testFromFiveToSevenYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_5_TO_7,
            EngineTypeEnum::GASOLINE,
            180,
            2200,
            450000.00,
        );

        $this->assertEquals(2373764.29, $result);
    }

    public function testOlderThanSevenYearsDiesel(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::MORE_THAN_7,
            EngineTypeEnum::DIESEL,
            450,
            6000,
            560000.00,
        );

        $this->assertEquals(5162403.66, $result);
    }

    public function testLessThanThreeYearsElectric(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::LESS_THAN_3,
            EngineTypeEnum::ELECTRIC,
            200,
            0,
            980000.00,
        );

        $this->assertEquals(541780.00, $result);
    }
}
