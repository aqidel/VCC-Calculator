<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\IncorrectRecyclingFeeParamEnum;
use Aqidel\VCCCalculator\Exceptions\IncorrectHorsePowersException;
use Aqidel\VCCCalculator\Exceptions\IncorrectVehiclePriceException;
use Aqidel\VCCCalculator\Exceptions\IncorrectRecyclingFeeParam;

/**
 * Ставки пошлин и налогов
 */
class Tariffs
{
    /**
     * Сбор за таможенное оформление, в RUB
     * @param int $vehiclePriceRUB
     * @return int
     * @throws IncorrectVehiclePriceException
     */
    public static function getCustomsClearanceTax(int $vehiclePriceRUB): int
    {
        if ($vehiclePriceRUB <= 0) {
            throw new IncorrectVehiclePriceException();
        }

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
     * @param int $horsePowers
     * @return int
     * @throws IncorrectHorsePowersException
     */
    public static function getExciseDuty(int $horsePowers): int
    {
        if ($horsePowers <= 0) {
            throw new IncorrectHorsePowersException();
        }

        return match (true) {
            $horsePowers < 90 => 0,
            $horsePowers < 150 => 55,
            $horsePowers < 200 => 531,
            $horsePowers < 300 => 869,
            $horsePowers < 400 => 1482,
            $horsePowers < 500 => 1534,
            default => 1584,
        };
    }

    /**
     * Базовая ставка утилизационного сбора
     * @param bool $isCommercialVehicle
     * @return int
     */
    public static function getRecyclingFeeBaseRate(bool $isCommercialVehicle = false): int
    {
        return $isCommercialVehicle ? 150000 : 20000;
    }

    /**
     * Коэффициент утилизационного сбора для физических лиц
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @param bool $isElectric
     * @return float
     * @throws IncorrectRecyclingFeeParam
     */
    public static function getRecyclingFeeIndividualCoefficient(
        int $engineCapacity,
        int $vehicleAge,
        bool $isElectric = false
    ): float {
        if ($engineCapacity <= 0) {
            throw new IncorrectRecyclingFeeParam(IncorrectRecyclingFeeParamEnum::IncorrectEngineCapacity);
        }

        if ($vehicleAge < 0) {
            throw new IncorrectRecyclingFeeParam(IncorrectRecyclingFeeParamEnum::IncorrectVehicleAge);
        }

        if ($isElectric) {
            return $vehicleAge < 3 ? 0.17 : 0.26;
        }

        if ($vehicleAge < 3) {
            return match (true) {
                $engineCapacity < 3000 => 0.17,
                $engineCapacity < 3500 => 48.5,
                default => 61.67,
            };
        } else {
            return match (true) {
                $engineCapacity < 3000 => 0.26,
                $engineCapacity < 3500 => 74.25,
                default => 81.19,
            };
        }
    }
}
