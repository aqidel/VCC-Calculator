<?php

declare(strict_types=1);

namespace Tests;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleAgeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;
use Aqidel\VCCCalculator\VCCCalculator;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    private const OWNER_TYPE = VehicleOwnerTypeEnum::COMPANY;

    private VCCCalculator $calculator;

    public function setUp(): void
    {
        $this->calculator = new VCCCalculator(
            EnginePowerUnitOfMeasurementEnum::HORSEPOWER,
            103.3773,
        );
    }

    public function testLessThanThreeYearsDiesel(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::LESS_THAN_3,
            EngineTypeEnum::DIESEL,
            320,
            4500,
            1310000.00,
        );

        $this->assertEquals(2338650.04, $result);
    }

    public function testFromThreeToFiveYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_3_TO_5,
            EngineTypeEnum::GASOLINE,
            200,
            2800,
            890000.00,
        );

        $this->assertEquals(1807380.00, $result);
    }

    public function testFromFiveToSevenYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_5_TO_7,
            EngineTypeEnum::GASOLINE,
            144,
            1980,
            260000.00,
        );

        $this->assertEquals(700447.16, $result);
    }

    public function testOlderThanSevenYearsGasoline(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::MORE_THAN_7,
            EngineTypeEnum::GASOLINE,
            72,
            1540,
            80000.00,
        );

        $this->assertEquals(851241.00, $result);
    }

    public function testFromThreeToFiveYearsElectric(): void
    {
        $result = $this->calculator->calculate(
            self::OWNER_TYPE,
            VehicleAgeEnum::FROM_3_TO_5,
            EngineTypeEnum::ELECTRIC,
            310,
            0,
            1005000.00,
        );

        $this->assertEquals(1085460.00, $result);
    }
}
