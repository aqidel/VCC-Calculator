<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Exceptions\IncorrectHorsePowersException;
use Aqidel\VCCCalculator\Exceptions\IncorrectVehiclePriceException;
use Aqidel\VCCCalculator\Exceptions\IncorrectRecyclingFeeParam;

/**
 * Ставки пошлин и налогов
 */
class Tariffs
{
    /**
     * Сбор за таможенное оформление, RUB
     * @param int $vehiclePriceRUB
     * @return int
     * @throws IncorrectVehiclePriceException
     */
    public static function getCustomsClearanceTax(int $vehiclePriceRUB): int
    {
        if ($vehiclePriceRUB <= 0) {
            throw new IncorrectVehiclePriceException('Vehicle price must be greater than 0!');
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
            throw new IncorrectHorsePowersException('Engine power must be greater than zero!');
        }

        return match (true) {
            $horsePowers <= 90 => 0,
            $horsePowers <= 150 => 55,
            $horsePowers <= 200 => 531,
            $horsePowers <= 300 => 869,
            $horsePowers <= 400 => 1482,
            $horsePowers <= 500 => 1534,
            default => 1584,
        };
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
            throw new IncorrectRecyclingFeeParam('Engine capacity can\'t be less or equal to zero!');
        }

        if ($vehicleAge < 0) {
            throw new IncorrectRecyclingFeeParam('Vehicle age can\'t be negative!');
        }

        if ($isElectric) {
            return $vehicleAge < 3 ? 0.17 : 0.26;
        }

        if ($vehicleAge < 3) {
            return match (true) {
                $engineCapacity <= 3000 => 0.17,
                $engineCapacity <= 3500 => 48.5,
                default => 61.67,
            };
        } else {
            return match (true) {
                $engineCapacity <= 3000 => 0.26,
                $engineCapacity <= 3500 => 74.25,
                default => 81.19,
            };
        }
    }

    /**
     * Коэффициент утилизационного сбора для юридических лиц
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @param bool $isElectric
     * @return float
     * @throws IncorrectRecyclingFeeParam
     */
    public static function getRecyclingFeeCompanyCoefficient(
        int $engineCapacity,
        int $vehicleAge,
        bool $isElectric = false
    ): float {
        if ($engineCapacity <= 0) {
            throw new IncorrectRecyclingFeeParam('Engine capacity can\'t be less or equal to zero!');
        }

        if ($vehicleAge < 0) {
            throw new IncorrectRecyclingFeeParam('Vehicle age can\'t be negative!');
        }

        if ($isElectric) {
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
        } else {
            return match (true) {
                $engineCapacity <= 1000 => 10.36,
                $engineCapacity <= 2000 => 26.44,
                $engineCapacity <= 3000 => 63.95,
                $engineCapacity <= 3500 => 74.25,
                default => 81.19,
            };
        }
    }

    /**
     * Таможенная пошлина для физических лиц, EUR
     * @param int $engineCapacity
     * @param int $vehicleAge
     * @param int $vehiclePriceEUR
     * @return float
     */
    public static function getCustomsFeeIndividual(
        int $engineCapacity,
        int $vehicleAge,
        int $vehiclePriceEUR,
    ): float {
        if ($vehicleAge < 3) {
            return match (true) {
                $vehiclePriceEUR < 8500 => max($vehiclePriceEUR * 0.54, $engineCapacity * 2.5),
                $vehiclePriceEUR < 16700 => max($vehiclePriceEUR * 0.48, $engineCapacity * 3.5),
                $vehiclePriceEUR < 42300 => max($vehiclePriceEUR * 0.48, $engineCapacity * 5.5),
                $vehiclePriceEUR < 84500 => max($vehiclePriceEUR * 0.48, $engineCapacity * 7.5),
                $vehiclePriceEUR < 169000 => max($vehiclePriceEUR * 0.48, $engineCapacity * 15),
                default => max($vehiclePriceEUR * 0.48, $engineCapacity * 20),
            };
        }
    }
}
