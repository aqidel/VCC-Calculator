<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;

final class VCCCalculator
{
    /**
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param EngineTypeEnum $engineType
     * @param EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @param float $euroExchangeRate
     * @return float
     */
    public function calculate(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement,
        int $enginePower,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
        float $euroExchangeRate,
    ): float {
        $customsFee = $this->calculateCustomsFee(
            $vehicleOwnerType,
            $engineType,
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceRUB,
            $euroExchangeRate,
        );
    }

    /**
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @param float $euroExchangeRate
     * @return float|null
     */
    private static function calculateCustomsFee(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
        float $euroExchangeRate,
    ): ?float {
        $vehiclePriceEUR = $vehiclePriceRUB * $euroExchangeRate;

        if (
            $vehicleOwnerType === VehicleOwnerTypeEnum::PERSON
            || $vehicleOwnerType === VehicleOwnerTypeEnum::PERSON_RESALE
        ) {
            return Tariffs::getCustomsFeeForIndividual($engineCapacityKubCm, $vehicleAge, $vehiclePriceEUR);
        } elseif (
            $vehicleOwnerType === VehicleOwnerTypeEnum::COMPANY
            && $engineType === EngineTypeEnum::GASOLINE
        ) {
            return Tariffs::getCustomsFeeGasEngineForCompany($engineCapacityKubCm, $vehicleAge, $vehiclePriceEUR);
        } elseif (
            $vehicleOwnerType === VehicleOwnerTypeEnum::COMPANY
            && $engineType === EngineTypeEnum::DIESEL
        ) {
            return Tariffs::getCustomsFeeDieselEngineForCompany($engineCapacityKubCm, $vehicleAge, $vehiclePriceEUR);
        }

        return null;
    }
}
