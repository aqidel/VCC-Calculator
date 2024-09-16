<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Exceptions\WrongEngineTypeException;

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
     * Ограничение по объему двигателя для льготного утильсбора
     * @var int
     */
    public const ENGINE_CAPACITY_EXEMPTION = 3000;

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
            $enginePower <= 150 => 55,
            $enginePower <= 200 => 531,
            $enginePower <= 300 => 869,
            $enginePower <= 400 => 1482,
            $enginePower <= 500 => 1534,
            default => 1584,
        };

        return $exciseRate * $enginePower;
    }

    /**
     * Базовая ставка утилизационного сбора, RUB
     * @param bool $isCommercialVehicle
     * @return int
     */
    public static function getRecyclingFeeBaseRate(bool $isCommercialVehicle = false): int
    {
        return $isCommercialVehicle ? 150000 : 20000;
    }

    /**
     * Льготный утилизационный сбор при ввозе для личного пользования
     * @param int $vehicleAge
     * @return int
     */
    public static function getRecyclingFeeForPersonalUsage(int $vehicleAge): int
    {
        return $vehicleAge < 3 ? 3400 : 5200;
    }

    /**
     * Коэффициент утилизационного сбора для физических лиц
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @return float
     */
    public static function getRecyclingFeeCoefficientForIndividual(
        EngineTypeEnum $engineType,
        int $engineCapacity,
        int $vehicleAge,
    ): float {
        if ($engineType === EngineTypeEnum::ELECTRIC) {
            return $vehicleAge < 3 ? 0.17 : 0.26;
        }

        if ($vehicleAge < 3) {
            return match (true) {
                $engineCapacity <= 3000 => 0.17,
                $engineCapacity <= 3500 => 48.5,
                default => 61.67,
            };
        }

        return match (true) {
            $engineCapacity <= 3000 => 0.26,
            $engineCapacity <= 3500 => 74.25,
            default => 81.19,
        };
    }

    /**
     * Коэффициент утилизационного сбора для юридических лиц
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @return float
     */
    public static function getRecyclingFeeCoefficientForCompany(
        EngineTypeEnum $engineType,
        int $engineCapacity,
        int $vehicleAge,
    ): float {
        if ($engineType === EngineTypeEnum::ELECTRIC) {
            return $vehicleAge < 3 ? 18 : 67.34;
        }

        if ($vehicleAge < 3) {
            return match (true) {
                $engineCapacity <= 1000 => 4.06,
                $engineCapacity <= 2000 => 15.03,
                $engineCapacity <= 3000 => 42.24,
                $engineCapacity <= 3500 => 48.5,
                default => 61.76,
            };
        }

        return match (true) {
            $engineCapacity <= 1000 => 10.36,
            $engineCapacity <= 2000 => 26.44,
            $engineCapacity <= 3000 => 63.95,
            $engineCapacity <= 3500 => 74.25,
            default => 81.19,
        };
    }

    /**
     * Таможенная пошлина для физических лиц, EUR
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @param float $euroExchangeRate
     * @return float
     */
    public static function getCustomsFeeForIndividual(
        EngineTypeEnum $engineType,
        int $engineCapacity,
        int $vehicleAge,
        float $vehiclePriceRUB,
        float $euroExchangeRate,
    ): float {
        $vehiclePriceEUR = $vehiclePriceRUB / $euroExchangeRate;

        if ($engineType === EngineTypeEnum::ELECTRIC) {

            return round($vehiclePriceRUB * 0.15, 2);
        }

        if ($vehicleAge < 3) {
            $customsFeeEUR = match (true) {
                $vehiclePriceEUR < 8500 => max($vehiclePriceEUR * 0.54, $engineCapacity * 2.5),
                $vehiclePriceEUR < 16700 => max($vehiclePriceEUR * 0.48, $engineCapacity * 3.5),
                $vehiclePriceEUR < 42300 => max($vehiclePriceEUR * 0.48, $engineCapacity * 5.5),
                $vehiclePriceEUR < 84500 => max($vehiclePriceEUR * 0.48, $engineCapacity * 7.5),
                $vehiclePriceEUR < 169000 => max($vehiclePriceEUR * 0.48, $engineCapacity * 15),
                default => max($vehiclePriceEUR * 0.48, $engineCapacity * 20),
            };
        } elseif ($vehicleAge < 5) {
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

        return round($customsFeeEUR * $euroExchangeRate, 2);
    }

    /**
     * Таможенная пошлина для юридических лиц, EUR
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @param float $euroExchangeRate
     * @return float|null
     * @throws WrongEngineTypeException
     */
    public static function getCustomsFeeForCompany(
        EngineTypeEnum $engineType,
        int $engineCapacity,
        int $vehicleAge,
        float $vehiclePriceRUB,
        float $euroExchangeRate,
    ): ?float {
        $vehiclePriceEUR = $vehiclePriceRUB / $euroExchangeRate;

        if ($engineType === EngineTypeEnum::ELECTRIC) {

            return round($vehiclePriceRUB * 0.15, 2);
        } elseif ($engineType === EngineTypeEnum::GASOLINE || $engineType === EngineTypeEnum::HYBRID) {
            if ($vehicleAge < 3) {
                $customsFeeEUR = match (true) {
                    $engineCapacity <= 3000 => $vehiclePriceEUR * 0.15,
                    default => $vehiclePriceEUR * 0.125,
                };
            } elseif ($vehicleAge < 7) {
                $customsFeeEUR = match (true) {
                    $engineCapacity <= 1000 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.36),
                    $engineCapacity <= 1500 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.4),
                    $engineCapacity <= 1800 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.36),
                    $engineCapacity <= 3000 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.44),
                    default => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.8),
                };
            } else {
                $customsFeeEUR = match (true) {
                    $engineCapacity <= 1000 => $engineCapacity * 1.4,
                    $engineCapacity <= 1500 => $engineCapacity * 1.5,
                    $engineCapacity <= 1800 => $engineCapacity * 1.6,
                    $engineCapacity <= 3000 => $engineCapacity * 2.2,
                    default => $engineCapacity * 3.2,
                };
            }
        } elseif ($engineType === EngineTypeEnum::DIESEL) {
            if ($vehicleAge < 3) {

                $customsFeeEUR = $vehiclePriceEUR * 0.15;
            } elseif ($vehicleAge < 7) {
                $customsFeeEUR = match (true) {
                    $engineCapacity <= 1500 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.32),
                    $engineCapacity <= 2500 => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.4),
                    default => max($vehiclePriceEUR * 0.2, $engineCapacity * 0.8),
                };
            } else {
                $customsFeeEUR = match (true) {
                    $engineCapacity <= 1500 => $engineCapacity * 1.5,
                    $engineCapacity <= 2500 => $engineCapacity * 2.2,
                    default => $engineCapacity * 3.2,
                };
            }
        } else {
            throw new WrongEngineTypeException('Engine type not supported!');
        }

        return round($customsFeeEUR * $euroExchangeRate, 2);
    }
}
