<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;

final class VCCCalculator
{
    private VehicleOwnerTypeEnum $vehicleOwnerType;
    private EngineTypeEnum $engineType;
    private EnginePowerUnitOfMeasurementEnum $enginePowerUnit;
    private float $euroExchangeRate;

    public function __construct(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        EnginePowerUnitOfMeasurementEnum $enginePowerUnit,
        float $euroExchangeRate,
    ) {
        $this->vehicleOwnerType = $vehicleOwnerType;
        $this->engineType = $engineType;
        $this->enginePowerUnit = $enginePowerUnit;
        $this->euroExchangeRate = $euroExchangeRate;
    }

    public function setVehicleOwnerType(VehicleOwnerTypeEnum $vehicleOwnerType): self
    {
        $this->vehicleOwnerType = $vehicleOwnerType;

        return $this;
    }

    public function getVehicleOwnerType(): VehicleOwnerTypeEnum
    {
        return $this->vehicleOwnerType;
    }

    public function setEngineType(EngineTypeEnum $engineType): self
    {
        $this->engineType = $engineType;

        return $this;
    }

    public function getEngineType(): EngineTypeEnum
    {
        return $this->engineType;
    }

    public function setEnginePowerUnitOfMeasurement(EnginePowerUnitOfMeasurementEnum $engineType): self
    {
        $this->enginePowerUnit = $engineType;

        return $this;
    }

    public function getEnginePowerUnitOfMeasurement(): EnginePowerUnitOfMeasurementEnum
    {
        return $this->enginePowerUnit;
    }

    public function setEuroExchangeRate(float $euroExchangeRate): self
    {
        $this->euroExchangeRate = $euroExchangeRate;

        return $this;
    }

    public function getEuroExchangeRate(): float
    {
        return $this->euroExchangeRate;
    }

    /**
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @return float
     */
    public function calculate(
        int $enginePower,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
    ): float {
        $customsFee = $this->calculateCustomsFee(
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceRUB,
        );
    }

    /**
     * Вычисляем таможенную пошлину
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @return float|null
     */
    private function calculateCustomsFee(
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
    ): ?float {
        $vehiclePriceEUR = $vehiclePriceRUB * $this->euroExchangeRate;

        if (
            $this->vehicleOwnerType === VehicleOwnerTypeEnum::PERSON
            || $this->vehicleOwnerType === VehicleOwnerTypeEnum::PERSON_RESALE
        ) {
            return Tariffs::getCustomsFeeForIndividual(
                $engineCapacityKubCm,
                $vehicleAge,
                $vehiclePriceEUR
            );
        } elseif ($this->vehicleOwnerType === VehicleOwnerTypeEnum::COMPANY) {
            return Tariffs::getCustomsFeeForCompany(
                $this->engineType,
                $engineCapacityKubCm,
                $vehicleAge,
                $vehiclePriceEUR
            );
        }

        return null;
    }
}
