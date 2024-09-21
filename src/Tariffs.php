<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleAgeEnum;

/**
 * Ставки пошлин и налогов
 */
final class Tariffs
{
    /**
     * Базовая ставка НДС
     * @var float
     */
    public const BASE_VAT = 0.2;

    /**
     * Базовая ставка утилизационного сбора на некоммерческие авто
     * @var int
     */
    public const RECYCLING_FEE_BASE_RATE = 20000;

    /**
     * Сбор за таможенное оформление, RUB
     * @param float $vehiclePriceRUB
     * @return int
     */
    public static function getCustomsClearanceTax(float $vehiclePriceRUB): int
    {
        return match (true) {
            $vehiclePriceRUB < 200000 => 775,
            $vehiclePriceRUB < 450000 => 1550,
            $vehiclePriceRUB < 1200000 => 3100,
            $vehiclePriceRUB < 2700000 => 8530,
            $vehiclePriceRUB < 4200000 => 12000,
            $vehiclePriceRUB < 5500000 => 15500,
            $vehiclePriceRUB < 7000000 => 20000,
            $vehiclePriceRUB < 8000000 => 23000,
            $vehiclePriceRUB < 9000000 => 25000,
            $vehiclePriceRUB < 10000000 => 27000,
            default => 30000,
        };
    }

    /**
     * Акциз, RUB за л.с.
     * @param EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement
     * @param int $enginePower
     * @return int
     */
    public static function getExciseDuty(
        EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement,
        int $enginePower,
    ): int {
        if ($enginePowerUnitOfMeasurement === EnginePowerUnitOfMeasurementEnum::KILOWATT) {
            $enginePower = (int)ceil($enginePower * 1.3596);
        }

        $exciseRate = match (true) {
            $enginePower <= 90 => 0,
            $enginePower <= 150 => 58,
            $enginePower <= 200 => 557,
            $enginePower <= 300 => 912,
            $enginePower <= 400 => 1555,
            $enginePower <= 500 => 1609,
            default => 1662,
        };

        return $exciseRate * $enginePower;
    }

    /**
     * Коэффициент утилизационного сбора для физических лиц (личное пользование)
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @return float
     */
    public static function getRecyclingFeeCoefficientForPersonalUsage(
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $engineCapacity,
    ): float {
        if ($vehicleAge === VehicleAgeEnum::LESS_THAN_3) {
            return match (true) {
                $engineCapacity <= 3000 || $engineType === EngineTypeEnum::ELECTRIC => 0.17,
                $engineCapacity <= 3500 => 48.5,
                default => 61.76,
            };
        }

        return match (true) {
            $engineCapacity <= 3000 || $engineType === EngineTypeEnum::ELECTRIC => 0.26,
            $engineCapacity <= 3500 => 74.275,
            default => 81.19,
        };
    }

    /**
     * Коэффициент утилизационного сбора для физических лиц (перепродажа)
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @return float
     */
    public static function getRecyclingFeeCoefficientForIndividual(
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $engineCapacity,
    ): float {
        if ($vehicleAge === VehicleAgeEnum::LESS_THAN_3) {
            return match (true) {
                $engineType === EngineTypeEnum::ELECTRIC => 1.63,
                $engineCapacity <= 1000 => 4.06,
                $engineCapacity <= 2000 => 15.3,
                $engineCapacity <= 3000 => 42.24,
                $engineCapacity <= 3500 => 48.5,
                default => 61.76,
            };
        }

        return match (true) {
            $engineType === EngineTypeEnum::ELECTRIC => 6.1,
            $engineCapacity <= 1000 => 10.36,
            $engineCapacity <= 2000 => 26.44,
            $engineCapacity <= 3000 => 63.95,
            $engineCapacity <= 3500 => 74.25,
            default => 81.19,
        };
    }

    /**
     * Коэффициент утилизационного сбора для юридических лиц
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @return float
     */
    public static function getRecyclingFeeCoefficientForCompany(
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $engineCapacity,
    ): float {
        if ($vehicleAge === VehicleAgeEnum::LESS_THAN_3) {
            return match (true) {
                $engineType === EngineTypeEnum::ELECTRIC => 1.63,
                $engineCapacity <= 1000 => 4.06,
                $engineCapacity <= 2000 => 15.03,
                $engineCapacity <= 3000 => 42.24,
                $engineCapacity <= 3500 => 48.5,
                default => 61.76,
            };
        }

        return match (true) {
            $engineType === EngineTypeEnum::ELECTRIC => 6.1,
            $engineCapacity <= 1000 => 10.36,
            $engineCapacity <= 2000 => 26.44,
            $engineCapacity <= 3000 => 63.95,
            $engineCapacity <= 3500 => 74.25,
            default => 81.19,
        };
    }

    /**
     * Таможенная пошлина для физических лиц, EUR
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @param float $vehiclePriceRUB
     * @param float $euroExchangeRate
     * @return float
     */
    public static function getCustomsFeeForIndividual(
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $engineCapacity,
        float $vehiclePriceRUB,
        float $euroExchangeRate,
    ): float {
        $vehiclePriceEUR = round($vehiclePriceRUB / $euroExchangeRate, 2);

        if ($engineType === EngineTypeEnum::ELECTRIC) {
            return $vehiclePriceRUB * 0.15;
        }

        if ($vehicleAge === VehicleAgeEnum::LESS_THAN_3) {
            $customsFeeEUR = match (true) {
                $vehiclePriceEUR < 8500 => max($vehiclePriceEUR * 0.54, $engineCapacity * 2.5),
                $vehiclePriceEUR < 16700 => max($vehiclePriceEUR * 0.48, $engineCapacity * 3.5),
                $vehiclePriceEUR < 42300 => max($vehiclePriceEUR * 0.48, $engineCapacity * 5.5),
                $vehiclePriceEUR < 84500 => max($vehiclePriceEUR * 0.48, $engineCapacity * 7.5),
                $vehiclePriceEUR < 169000 => max($vehiclePriceEUR * 0.48, $engineCapacity * 15),
                default => max($vehiclePriceEUR * 0.48, $engineCapacity * 20),
            };
        } elseif ($vehicleAge === VehicleAgeEnum::FROM_3_TO_5) {
            $customsFeeEUR = match (true) {
                $engineCapacity <= 1000 => $engineCapacity * 1.5,
                $engineCapacity <= 1500 => $engineCapacity * 1.7,
                $engineCapacity <= 1800 => $engineCapacity * 2.5,
                $engineCapacity <= 2300 => $engineCapacity * 2.7,
                $engineCapacity <= 3000 => $engineCapacity * 3,
                default => $engineCapacity * 3.6,
            };
        } else {
            $customsFeeEUR = match (true) {
                $engineCapacity <= 1000 => $engineCapacity * 3,
                $engineCapacity <= 1500 => $engineCapacity * 3.2,
                $engineCapacity <= 1800 => $engineCapacity * 3.5,
                $engineCapacity <= 2300 => $engineCapacity * 4.8,
                $engineCapacity <= 3000 => $engineCapacity * 5,
                default => $engineCapacity * 5.7,
            };
        }

        return $customsFeeEUR * $euroExchangeRate;
    }

    /**
     * Таможенная пошлина для юридических лиц по типу двигателя, EUR
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @param float $vehiclePriceRUB
     * @param float $euroExchangeRate
     * @return float|null
     */
    public static function getCustomsFeeForCompanyByEngineType(
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $engineCapacity,
        float $vehiclePriceRUB,
        float $euroExchangeRate,
    ): ?float {
        $vehiclePriceEUR = round($vehiclePriceRUB / $euroExchangeRate, 2);

        if ($engineType === EngineTypeEnum::ELECTRIC) {
            return $vehiclePriceRUB * 0.15;
        } elseif (
            $engineType === EngineTypeEnum::GASOLINE
            || $engineType === EngineTypeEnum::HYBRID
        ) {
            $customsFeeEUR = self::getCustomsFeeGasolineOrHybridEngine($vehicleAge, $engineCapacity, $vehiclePriceEUR);
        } else {
            $customsFeeEUR = self::getCustomsFeeDieselEngine($vehicleAge, $engineCapacity, $vehiclePriceEUR);
        }

        return $customsFeeEUR * $euroExchangeRate;
    }

    /**
     * Таможенная пошлина для юр. лиц, бензиновый или гибридный двигатель, EUR
     * @param VehicleAgeEnum $vehicleAge
     * @param int $engineCapacity
     * @param float $vehiclePriceEUR
     * @return float
     */
    private static function getCustomsFeeGasolineOrHybridEngine(
        VehicleAgeEnum $vehicleAge,
        int $engineCapacity,
        float $vehiclePriceEUR,
    ): float {
        if ($vehicleAge === VehicleAgeEnum::LESS_THAN_3) {
            return match (true) {
                $engineCapacity <= 3000 => $vehiclePriceEUR * 0.15,
                default => $vehiclePriceEUR * 0.125,
            };
        } elseif (
            $vehicleAge === VehicleAgeEnum::FROM_3_TO_5
            || $vehicleAge === VehicleAgeEnum::FROM_5_TO_7
        ) {
            return match (true) {
                $engineCapacity <= 1000 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.36),
                $engineCapacity <= 1500 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.4),
                $engineCapacity <= 1800 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.36),
                $engineCapacity <= 3000 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.44),
                default => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.8),
            };
        }

        return match (true) {
            $engineCapacity <= 1000 => $engineCapacity * 1.4,
            $engineCapacity <= 1500 => $engineCapacity * 1.5,
            $engineCapacity <= 1800 => $engineCapacity * 1.6,
            $engineCapacity <= 3000 => $engineCapacity * 2.2,
            default => $engineCapacity * 3.2,
        };
    }

    /**
     * Таможенная пошлина для юр. лиц, дизельный двигатель, EUR
     * @param VehicleAgeEnum $vehicleAge
     * @param int $engineCapacity
     * @param float $vehiclePriceEUR
     * @return float
     */
    private static function getCustomsFeeDieselEngine(
        VehicleAgeEnum $vehicleAge,
        int $engineCapacity,
        float $vehiclePriceEUR,
    ): float {
        if ($vehicleAge === VehicleAgeEnum::LESS_THAN_3) {
            return $vehiclePriceEUR * 0.15;
        } elseif (
            $vehicleAge === VehicleAgeEnum::FROM_3_TO_5
            || $vehicleAge === VehicleAgeEnum::FROM_5_TO_7
        ) {
            return match (true) {
                $engineCapacity <= 1500 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.32),
                $engineCapacity <= 2500 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.4),
                default => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.8),
            };
        }

        return match (true) {
            $engineCapacity <= 1500 => $engineCapacity * 1.5,
            $engineCapacity <= 2500 => $engineCapacity * 2.2,
            default => $engineCapacity * 3.2,
        };
    }
}
